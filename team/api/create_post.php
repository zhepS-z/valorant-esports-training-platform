<?php
// dev: แสดง error ชั่วคราว (ปิดก่อนขึ้น production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../utils/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// read description (support both 'description' and legacy 'experience')
$description_raw = isset($_POST['description']) ? $_POST['description'] : (isset($_POST['experience']) ? $_POST['experience'] : '');
$description = trim((string)$description_raw);

// position fallback (hidden in form)
$position = trim($_POST['position'] ?? 'Any');
$position_e = $conn->real_escape_string($position);

// rank (optional)
$rank = trim($_POST['rank'] ?? '');
$rank_sql = ($rank === '') ? "NULL" : ("'" . $conn->real_escape_string($rank) . "'");

// validate minimal
if ($description === '') {
    echo json_encode(['success' => false, 'message' => 'Description is required']);
    exit;
}

$desc_e = $conn->real_escape_string($description);

// insert LFP post (store description in experience column to keep compatibility)
$sql = "INSERT INTO lfp_posts (user_id, position, rank, experience, created_at)
        VALUES ($user_id, '$position_e', $rank_sql, '$desc_e', NOW())";

if (!$conn->query($sql)) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

$post_id = $conn->insert_id;

// publish a specific team: prefer team_id from POST, otherwise publish the manager's first team
$team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
if (!$team_id) {
    $r = $conn->query("SELECT team_id FROM teams WHERE manager_id = $user_id LIMIT 1");
    if ($r && ($row = $r->fetch_assoc())) {
        $team_id = (int)$row['team_id'];
    }
}

// If we have a team, update its description (and publish if desired)
if ($team_id) {
    // ensure manager owns the team before updating
    $chk = $conn->query("SELECT manager_id FROM teams WHERE team_id = $team_id LIMIT 1");
    if ($chk && $chk->num_rows > 0) {
        $owner = (int)$chk->fetch_assoc()['manager_id'];
        if ($owner === $user_id) {
            // update description
            $upd = "UPDATE teams SET description = '$desc_e' WHERE team_id = $team_id AND manager_id = $user_id";
            $conn->query($upd);
            // optional: publish the team when posting
            $conn->query("UPDATE teams SET is_published = 1 WHERE team_id = $team_id AND manager_id = $user_id");
        }
    }
}

echo json_encode(['success' => true, 'post_id' => $post_id, 'team_id' => $team_id]);
$conn->close();
exit;