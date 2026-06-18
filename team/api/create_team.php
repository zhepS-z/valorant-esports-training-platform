<?php
// dev: แสดง error ชั่วคราว (ปิดก่อนขึ้น production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// โหลด DB connection
require_once __DIR__ . '/../../utils/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$manager_id = (int)$_SESSION['user_id'];

// ป้องกันฝั่งเซิร์ฟเวอร์: ถ้าผู้ใช้มีทีมอยู่แล้ว ให้บล็อกการสร้างทีม
$checkRes = $conn->query("SELECT team_id FROM users WHERE user_id = $manager_id LIMIT 1");
if ($checkRes && $checkRes->num_rows > 0) {
    $row = $checkRes->fetch_assoc();
    if (!empty($row['team_id'])) {
        echo json_encode(['success' => false, 'message' => 'คุณมีทีมอยู่แล้ว ไม่สามารถสร้างทีมใหม่ได้']);
        exit;
    }
}

$team_name = trim($_POST['team_name'] ?? '');
$description = trim($_POST['description'] ?? ''); // ok to be empty now
$abbr = trim($_POST['abbr'] ?? '');
$abbr = strtoupper($abbr); // normalize to uppercase

// validate
if ($team_name === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill team name.']);
    exit;
}
if ($abbr === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill team abbreviation.']);
    exit;
}
// new: enforce 2-4 alnum characters
if (!preg_match('/^[A-Z0-9]{2,4}$/', $abbr)) {
    echo json_encode(['success' => false, 'message' => 'Abbreviation must be 2–4 characters (A-Z, 0-9).']);
    exit;
}

$max_size = (int)($_POST['max_size'] ?? 7);

// enforce 5-7
if ($max_size < 5) $max_size = 5;
if ($max_size > 7) $max_size = 7;

// determine region fallback (use user's region if available)
$region = 'ap';
$res = $conn->query("SELECT region FROM users WHERE user_id = $manager_id LIMIT 1");
if ($res && ($row = $res->fetch_assoc()) && !empty($row['region'])) {
    $region = $row['region'];
}

// helper: check column exists
function column_exists($conn, $col) {
    $col_e = $conn->real_escape_string($col);
    $r = $conn->query("SHOW COLUMNS FROM teams LIKE '$col_e'");
    return ($r && $r->num_rows > 0);
}

// handle uploaded logo (optional)
$logo_path_db = null;
if (!empty($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Logo upload error']);
        exit;
    }
    $file = $_FILES['logo'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        echo json_encode(['success' => false, 'message' => 'Unsupported logo file type']);
        exit;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Logo file too large (max 2MB)']);
        exit;
    }
    $ext = $allowed[$mime];
    $uploadDir = __DIR__ . '/../../uploads/team_logos';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload dir']);
            exit;
        }
    }
    try {
        $basename = bin2hex(random_bytes(8)) . '.' . $ext;
    } catch (Exception $e) {
        $basename = uniqid('logo_', true) . '.' . $ext;
    }
    $target = $uploadDir . '/' . $basename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
        exit;
    }
    $logo_path_db = '/VALPROJECT/uploads/team_logos/' . $basename;
}

// prepare insert columns dynamically depending on schema
$cols = [];
$placeholders = [];
$types = '';
$values = [];

// required/basic fields
if (column_exists($conn, 'team_name')) { $cols[] = 'team_name'; $placeholders[] = '?'; $types .= 's'; $values[] = $team_name; }
if (column_exists($conn, 'manager_id'))  { $cols[] = 'manager_id';  $placeholders[] = '?'; $types .= 'i'; $values[] = $manager_id; }
if (column_exists($conn, 'region'))      { $cols[] = 'region';      $placeholders[] = '?'; $types .= 's'; $values[] = $region; }
if (column_exists($conn, 'rank'))        { $cols[] = 'rank';        $placeholders[] = '?'; $types .= 's'; $values[] = 'Unranked'; }
if (column_exists($conn, 'current_size')){ $cols[] = 'current_size';$placeholders[] = '?'; $types .= 'i'; $values[] = 1; }
if (column_exists($conn, 'max_size'))    { $cols[] = 'max_size';    $placeholders[] = '?'; $types .= 'i'; $values[] = $max_size; }
if (column_exists($conn, 'description')) { $cols[] = 'description'; $placeholders[] = '?'; $types .= 's'; $values[] = $description; }
// abbreviation column support (abbr, abbreviation, short_name)
$abbr_col = null;
foreach (['abbreviation','abbr','short_name'] as $c) {
    if (column_exists($conn, $c)) { $abbr_col = $c; break; }
}
if ($abbr_col) { $cols[] = $abbr_col; $placeholders[] = '?'; $types .= 's'; $values[] = $abbr; }
// logo column support (team_logo, logo, logo_path)
$logo_col = null;
foreach (['team_logo','logo','logo_path'] as $c) {
    if (column_exists($conn, $c)) { $logo_col = $c; break; }
}
if ($logo_col && $logo_path_db !== null) { $cols[] = $logo_col; $placeholders[] = '?'; $types .= 's'; $values[] = $logo_path_db; }

// is_published default to 0 (draft) if exists
if (column_exists($conn, 'is_published')) { $cols[] = 'is_published'; $placeholders[] = '?'; $types .= 'i'; $values[] = 0; }

// fallback: ensure we have at least team_name and manager_id
if (count($cols) < 2) {
    echo json_encode(['success' => false, 'message' => 'Database schema not compatible.']);
    exit;
}

// build prepared statement
$sql = 'INSERT INTO teams (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

// bind params dynamically
$bind_params = [];
$bind_params[] = $types;
for ($i = 0; $i < count($values); $i++) {
    // bind_param requires references
    $bind_params[] = &$values[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bind_params);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$team_id = $stmt->insert_id;
$stmt->close();

// add manager to team_members with error handling
$tm_ok = ($conn->query("SHOW TABLES LIKE 'team_members'")->num_rows > 0);
if ($tm_ok) {
    $ins = $conn->prepare("INSERT INTO team_members (team_id, user_id, role_in_team) VALUES (?, ?, ?)");
    if ($ins) {
        $role = 'Manager';
        $ins->bind_param('iis', $team_id, $manager_id, $role);
        if (!$ins->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to add manager to team: ' . $ins->error]);
            $ins->close();
            $conn->close();
            exit;
        }
        $ins->close();
    }
}

// update users.team_id using prepared statement
$updateStmt = $conn->prepare("UPDATE users SET team_id = ? WHERE user_id = ?");
if (!$updateStmt) {
    echo json_encode(['success' => false, 'message' => 'Update prepare failed: ' . $conn->error]);
    $conn->close();
    exit;
}
$updateStmt->bind_param('ii', $team_id, $manager_id);
if (!$updateStmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $updateStmt->error]);
    $updateStmt->close();
    $conn->close();
    exit;
}
$updateStmt->close();

echo json_encode(['success' => true, 'team_id' => $team_id]);
$conn->close();
exit;
?>