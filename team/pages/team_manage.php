<?php
session_start();
define('ACCESS', true);
require_once '../../utils/apikey.php';
require_once '../../auth/auth_check.php';
include '../../utils/db.php';

// Require login
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../../auth/login.php');
    exit;
}

// Fetch team where current user is manager
$stmt = $conn->prepare("SELECT * FROM teams WHERE manager_id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$teamRes = $stmt->get_result();
if ($teamRes->num_rows === 0) {
    // Not a manager of any team -> deny
    header("Location: ../error/403.php");
    exit;
}
$team = $teamRes->fetch_assoc();
$team_id = (int)$team['team_id'];
$stmt->close();

// ป้องกัน Warning หากไม่มีการเซ็ต $flash
$flash = null;

// NEW: handle logo upload (AJAX or standard POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_logo') {
        header('Content-Type: application/json; charset=utf-8');
        // require file + manager authorization (we already ensured user is manager when loading)
        if (empty($_FILES['logo']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            exit;
        }
        $file = $_FILES['logo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Upload error']);
            exit;
        }
        // validate mime
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            echo json_encode(['success' => false, 'message' => 'Unsupported file type']);
            exit;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File too large (max 2MB)']);
            exit;
        }
        $ext = $allowed[$mime];
        $uploadDir = __DIR__ . '/../../uploads/team_logos';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            echo json_encode(['success' => false, 'message' => 'Failed creating upload dir']);
            exit;
        }
        try {
            $basename = bin2hex(random_bytes(8)) . '.' . $ext;
        } catch (Exception $e) {
            $basename = uniqid('logo_', true) . '.' . $ext;
        }
        $target = $uploadDir . '/' . $basename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            echo json_encode(['success' => false, 'message' => 'Failed moving uploaded file']);
            exit;
        }
        // optional: remove old file if exists and is in uploads/team_logos/
        if (!empty($team['team_logo']) && strpos($team['team_logo'], 'uploads/team_logos/') === 0) {
            $old = __DIR__ . '/../../' . $team['team_logo'];
            if (is_file($old)) @unlink($old);
        }
        $dbPath = '/VALPROJECT/uploads/team_logos/' . $basename;
        $u = $conn->prepare("UPDATE teams SET team_logo = ? WHERE team_id = ? AND manager_id = ?");
        $u->bind_param('sii', $dbPath, $team_id, $user_id);
        if (!$u->execute()) {
            echo json_encode(['success' => false, 'message' => 'DB update failed: ' . $conn->error]);
            exit;
        }
        $u->close();
        // return web-accessible url
        $publicUrl = $dbPath;
        // update $team for immediate display if page reloads later
        $team['team_logo'] = $dbPath;
        echo json_encode(['success' => true, 'url' => $publicUrl]);
        exit;
    }

    // AJAX kick handler -> return JSON and exit
    if ($action === 'kick') {
        header('Content-Type: application/json; charset=utf-8');
        $kickUserId = intval($_POST['kick_user_id'] ?? 0);
        if (!$kickUserId) {
            echo json_encode(['success'=>false,'message'=>'Invalid user']);
            exit;
        }
        if ($kickUserId === $user_id) {
            echo json_encode(['success'=>false,'message'=>'ไม่สามารถเตะตัวเองได้']);
            exit;
        }
        // Ensure current user is manager of this team (already checked earlier when loading page)
        try {
            $conn->begin_transaction();

            // remove from team_members (prevent deleting a Manager)
            $stmt = $conn->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ? AND role_in_team != 'Manager'");
            $stmt->bind_param('ii', $team_id, $kickUserId);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                throw new Exception('Member not found or cannot remove manager');
            }
            $stmt->close();

            // clear users.team_id
            $stmt = $conn->prepare("UPDATE users SET team_id = NULL WHERE user_id = ?");
            $stmt->bind_param('i', $kickUserId);
            $stmt->execute();
            $stmt->close();

            // decrement current_size safely
            $stmt = $conn->prepare("UPDATE teams SET current_size = GREATEST(current_size - 1, 0) WHERE team_id = ?");
            $stmt->bind_param('i', $team_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            echo json_encode(['success'=>true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        exit;
    }

    if ($action === 'update') {
        // Only allow editing basic fields: team_name, description, practice_schedule
        $team_name = trim($_POST['team_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $practice = trim($_POST['practice_schedule'] ?? '');

        if ($team_name === '') {
            $flash = ['type'=>'danger','msg'=>'ชื่อทีมไม่สามารถเป็นค่าว่างได้'];
        } else {
            $u = $conn->prepare("UPDATE teams SET team_name = ?, description = ?, practice_schedule = ? WHERE team_id = ? AND manager_id = ?");
            $u->bind_param('sssii', $team_name, $description, $practice, $team_id, $user_id);
            if ($u->execute()) {
                $flash = ['type'=>'success','msg'=>'บันทึกข้อมูลทีมเรียบร้อย'];
                // refresh $team
                $stmt = $conn->prepare("SELECT * FROM teams WHERE team_id = ? LIMIT 1");
                $stmt->bind_param('i', $team_id);
                $stmt->execute();
                $team = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $flash = ['type'=>'danger','msg'=>'เกิดข้อผิดพลาดในการอัปเดต: '.$conn->error];
            }
            $u->close();
        }
    } elseif ($action === 'delete') {
        // Delete team (only by manager). Must clear users.team_id first (FK without cascade).
        // Use transaction for safety.
        try {
            $conn->begin_transaction();

            // Set users.team_id = NULL for all users in this team
            $updUsers = $conn->prepare("UPDATE users SET team_id = NULL WHERE team_id = ?");
            $updUsers->bind_param('i', $team_id);
            $updUsers->execute();
            $updUsers->close();

            // If current user was manager, set their role to 'player' (optional)
            $updRole = $conn->prepare("UPDATE users SET role = 'player' WHERE user_id = ? AND role = 'manager'");
            $updRole->bind_param('i', $user_id);
            $updRole->execute();
            $updRole->close();

            // Delete the team (team_members, team_open_roles, team_join_requests use ON DELETE CASCADE)
            $del = $conn->prepare("DELETE FROM teams WHERE team_id = ? AND manager_id = ?");
            $del->bind_param('ii', $team_id, $user_id);
            $del->execute();
            if ($del->affected_rows === 0) {
                throw new Exception('ไม่พบทีมหรือไม่มีสิทธิ์ลบ');
            }
            $del->close();

            $conn->commit();

            // Redirect to team listing or dashboard
            header('Location: ./LFT.php?deleted=1');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $flash = ['type'=>'danger','msg'=>'ลบทีมไม่สำเร็จ: '.$e->getMessage()];
        }
    }
}

// Fetch current members
$members = [];
$mstmt = $conn->prepare("SELECT u.user_id, u.first_name, u.last_name, u.profile_img, tm.role_in_team 
                        FROM team_members tm
                        JOIN users u ON tm.user_id = u.user_id
                        WHERE tm.team_id = ?");
$mstmt->bind_param('i', $team_id);
$mstmt->execute();
$mres = $mstmt->get_result();
while ($r = $mres->fetch_assoc()) $members[] = $r;
$mstmt->close();

// Fetch open roles (table may have been removed). Check existence first.
$open_roles = [];
$check = $conn->query("SHOW TABLES LIKE 'team_open_roles'");
if ($check && $check->num_rows) {
    $rstmt = $conn->prepare("SELECT role FROM team_open_roles WHERE team_id = ?");
    if ($rstmt) {
        $rstmt->bind_param('i', $team_id);
        $rstmt->execute();
        $rres = $rstmt->get_result();
        while ($r = $rres->fetch_assoc()) $open_roles[] = $r['role'];
        $rstmt->close();
    }
} else {
    // table missing -> no open roles (safe fallback)
    $open_roles = [];
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>จัดการทีม - <?= htmlspecialchars($team['team_name']) ?></title>
<?php include '../../utils/link.php'; ?>
<link rel="stylesheet" href="../css/admin.css">
<style>
/* Modern Minimal Design */
:root {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --bg-card: rgba(30, 41, 59, 0.7);
    --text-primary: #f8fafc;
    --text-secondary: #94a3b8;
    --accent: #3b82f6;
    --accent-hover: #2563eb;
    --danger: #ef4444;
    --success: #10b981;
    --border: rgba(255, 255, 255, 0.1);
    --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
}

* {
    box-sizing: border-box;
}

body {
    background-color: var(--bg-primary);
    color: var(--text-primary);
    margin: 0;
    padding: 0;
}


/* Header Section */
.team-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border);
}

.team-logo-manage {
    width: 100px;
    height: 100px;
    border-radius: 16px;
    object-fit: contain;
    background: var(--bg-secondary);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.team-title {
    flex: 1;
}

.team-title h1 {
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    font-size: 2rem;
}

.team-meta {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

/* Logo Uploader */
.logo-uploader {
    position: relative;
    display: inline-block;
}

.logo-uploader input[type="file"] {
    display: none;
}

.logo-upload-btn {
    position: absolute;
    bottom: -8px;
    right: -8px;
    background: var(--accent);
    border: none;
    color: white;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow);
    cursor: pointer;
    transition: all 0.2s ease;
}

.logo-upload-btn:hover {
    background: var(--accent-hover);
    transform: scale(1.05);
}

/* Cards */
.card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    backdrop-filter: blur(10px);
    box-shadow: var(--shadow);
}

.card-title {
    margin-top: 0;
    margin-bottom: 1.5rem;
    font-weight: 600;
    font-size: 1.25rem;
    color: var(--text-primary);
}

/* Forms */
.tm-form-group {
    margin-bottom: 1.5rem;
}

.tm-form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-primary);
}

.tm-form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.tm-form-control:focus {
    outline: none;
    border-color: var(--accent);
}

.tm-form-text {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    gap: 0.5rem;
}

.btn-primary {
    background: var(--accent);
    color: white;
}

.btn-primary:hover {
    background: var(--accent-hover);
    transform: translateY(-1px);
}

.btn-outline {
    background: transparent;
    color: var(--text-primary);
    border: 1px solid var(--border);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.05);
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
}

.btn-group {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* Members Grid */
.members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.member-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 10px;
    border: 1px solid var(--border);
    transition: transform 0.2s ease;
}

.member-card:hover {
    transform: translateY(-2px);
}

.member-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border);
}

.member-info {
    flex: 1;
}

.member-name {
    font-weight: 600;
    margin: 0 0 0.25rem 0;
}

.member-role {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin: 0;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: var(--accent);
    color: white;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .team-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .members-grid {
        grid-template-columns: 1fr;
    }
    
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Animation for logo upload */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.fade-in {
    animation: fadeIn 0.3s ease;
}
</style>
</head>
<body>
<div class="container">
    <br>
    <div class="team-header">
        <div class="team-title">
            <h1>จัดการทีม: <?= htmlspecialchars($team['team_name']) ?></h1>
            <div class="team-meta">Team ID: <?= (int)$team['team_id'] ?> • Region: <?= htmlspecialchars($team['region'] ?? '') ?></div>
        </div>
        
        <div class="logo-uploader">
            <?php
                // normalize logo path: remove team/ prefix if present
                $logoPath = $team['team_logo'] ?? '';
                $logoPath = preg_replace('#^team/#i', '', $logoPath);
                // If path already starts with /, use it directly (new format)
                // Otherwise build the full URL (legacy format)
                if (!empty($logoPath)) {
                    $logoSrc = (strpos($logoPath, '/') === 0) ? $logoPath : '/' . trim('VALPROJECT/' . $logoPath, '/');
                } else {
                    $logoSrc = '/img/default_team_logo.png';
                }
            ?>
            <img id="teamLogoImg" src="<?= htmlspecialchars($logoSrc) ?>" alt="Team logo" class="team-logo-manage">
            <form id="logoForm" method="post" enctype="multipart/form-data" style="display:inline;">
                <input type="hidden" name="action" value="upload_logo">
                <input id="logoInput" type="file" name="logo" accept="image/png,image/jpeg,image/webp">
                <button type="button" class="logo-upload-btn" id="triggerLogo" title="เปลี่ยนโลโก้ทีม">
                    <i class="fa fa-pencil"></i>
                </button>
            </form>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 class="card-title">ข้อมูลทีม</h3>
        <form method="post" onsubmit="return confirmSave();">
            <input type="hidden" name="action" value="update">
            
            <div class="tm-form-group">
                <label class="tm-form-label">ชื่อทีม</label>
                <input type="text" name="team_name" class="tm-form-control" value="<?= htmlspecialchars($team['team_name']) ?>" required>
            </div>

            <div class="tm-form-group">
                <label class="tm-form-label">ชื่อย่อทีม (Abbreviation)</label>
                <input type="text"
                       name="abbreviation"
                       class="tm-form-control"
                       value="<?= htmlspecialchars($team['abbreviation'] ?? '') ?>"
                       readonly>
                <div class="tm-form-text">ตัวอย่าง: TLN (ไม่สามารถแก้ไขได้)</div>
            </div>

            <div class="tm-form-group">
                <label class="tm-form-label">คำอธิบายทีม</label>
                <textarea name="description" class="tm-form-control" rows="3"><?= htmlspecialchars($team['description'] ?? '') ?></textarea>
            </div>

            <div class="tm-form-group">
                <label class="tm-form-label">ตารางฝึกซ้อม</label>
                <input type="text" name="practice_schedule" class="tm-form-control" value="<?= htmlspecialchars($team['practice_schedule'] ?? '') ?>">
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> บันทึกการเปลี่ยนแปลง
                </button>
                <a href="../api/team_request.php" class="btn btn-outline">
                    <i class="fa fa-users"></i> คำขอเข้าร่วมทีม
                </a>
                <button type="button" class="btn btn-danger" id="deleteBtn">
                    <i class="fa fa-trash"></i> ลบทีม
                </button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 class="card-title">สมาชิกทีม <span class="team-meta">(<?= count($members) ?>)</span></h3>
        <div class="members-grid" id="membersList">
            <?php foreach ($members as $m): ?>
                <div class="member-card" id="member-<?= (int)$m['user_id'] ?>">
                    <?php
                      if (empty($m['profile_img'])) {
                        $avatar = null;  // Use icon instead
                      } else if (preg_match('#^https?://#i', $m['profile_img'])) {
                        $avatar = $m['profile_img'];
                      } else {
                        $p = str_replace('\\', '/', $m['profile_img']);
                        $p = str_replace('team/', '', $p);
                        $p = ltrim($p, '/');
                        $avatar = '/VALPROJECT/img/' . $p;
                      }
                    ?>
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="" class="member-avatar">
                    <div class="member-info">
                        <div class="member-name"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></div>
                        <div class="member-role"><?= htmlspecialchars($m['role_in_team'] ?: 'Player') ?></div>
                    </div>
                    <?php if (strtolower($m['role_in_team'] ?? '') !== 'manager'): ?>
                        <button class="btn btn-sm btn-danger kick-btn" data-user-id="<?= (int)$m['user_id'] ?>" title="เตะสมาชิก">
                            <i class="fa fa-user-times"></i>
                        </button>
                    <?php else: ?>
                        <span class="badge">Manager</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title">เชื่อมต่อทีม Premier</h3>
        <p class="tm-form-text mb-3">เชื่อมต่อทีมของคุณกับการแข่งขัน Valorant Premier เพื่อติดตามสถิติและประวัติการเล่น</p>
        <a href="../misc/premier_connect.php" class="btn btn-primary">
            <i class="fas fa-link me-2"></i> จัดการการเชื่อมต่อ Premier
        </a>
    </div>
</div>

<!-- Delete form (submitted via JS) -->
<form id="deleteForm" method="post" style="display:none;">
    <input type="hidden" name="action" value="delete">
</form>

<script>
function confirmSave(){
    return confirm('ยืนยันการบันทึกการเปลี่ยนแปลง?');
}

document.getElementById('deleteBtn').addEventListener('click', function(){
    if (!confirm('ลบทีมนี้จริงหรือไม่? การลบจะไม่สามารถกู้คืนได้')) return;
    document.getElementById('deleteForm').submit();
});

// Kick member via AJAX
document.querySelectorAll('.kick-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
        const uid = this.dataset.userId;
        if (!confirm('ยืนยันการเตะสมาชิกนี้ออกจากทีม?')) return;
        
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=kick&kick_user_id=' + encodeURIComponent(uid)
        })
        .then(r=>r.json())
        .then(j=>{
            if (j.success) {
                const el = document.getElementById('member-' + uid);
                if (el) {
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 300);
                }
                showNotification('เตะสมาชิกเรียบร้อย', 'success');
            } else {
                showNotification('Error: ' + (j.message || 'เกิดข้อผิดพลาด'), 'error');
            }
        })
        .catch(()=> showNotification('เกิดข้อผิดพลาดเชื่อมต่อ', 'error'));
    });
});

// Logo upload UI
const trigger = document.getElementById('triggerLogo');
const input = document.getElementById('logoInput');
const imgEl = document.getElementById('teamLogoImg');

trigger.addEventListener('click', ()=> input.click());

input.addEventListener('change', function(){
    if (!input.files || !input.files[0]) return;
    const f = input.files[0];
    
    // quick client-side validation
    if (!['image/png','image/jpeg','image/webp'].includes(f.type)) { 
        showNotification('Unsupported file type', 'error'); 
        return; 
    }
    
    if (f.size > 2 * 1024 * 1024) { 
        showNotification('ไฟล์ใหญ่เกินไป (max 2MB)', 'error'); 
        return; 
    }

    // preview
    const reader = new FileReader();
    reader.onload = function(e){ 
        imgEl.src = e.target.result;
        imgEl.classList.add('fade-in');
    };
    reader.readAsDataURL(f);

    // upload via fetch
    const data = new FormData();
    data.append('action','upload_logo');
    data.append('logo', f);

    fetch('', { method: 'POST', body: data })
        .then(r => r.json())
        .then(j => {
            if (j.success && j.url) {
                imgEl.src = j.url;
                showNotification('อัปโหลดโลโก้เรียบร้อย', 'success');
            } else {
                showNotification('Upload failed: ' + (j.message || 'Unknown'), 'error');
                // revert preview to previous if needed
                setTimeout(()=> location.reload(), 1200);
            }
        })
        .catch(()=> { 
            showNotification('Upload error', 'error'); 
            setTimeout(()=> location.reload(), 1200); 
        });
});

// Notification function
function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        transition: all 0.3s ease;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    if (type === 'success') {
        notification.style.background = 'var(--success)';
    } else {
        notification.style.background = 'var(--danger)';
    }
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}
</script>
</body>
</html>
<?php