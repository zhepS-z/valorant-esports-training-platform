<?php
session_start();
define('ACCESS', true);
require_once '../../utils/apikey.php';
require_once '../../auth/auth_check.php';
include '../../utils/db.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../../auth/login.php');
    exit;
}

// Only managers can access this page
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$currentUser || $currentUser['role'] !== 'manager') {
    header("Location: ../error/403.php");
    exit;
}

// Fetch the team managed by this user
$stmt = $conn->prepare("SELECT * FROM teams WHERE manager_id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$teamRes = $stmt->get_result();
if ($teamRes->num_rows === 0) {
    header("Location: ../error/403.php");
    exit;
}
$team = $teamRes->fetch_assoc();
$team_id = (int)$team['team_id'];
$stmt->close();

// Fetch team members from team_members, role determined from teams table manager_id only
$stmt = $conn->prepare("
    SELECT u.user_id, u.first_name, u.last_name, u.profile_img, u.riot_id, 
           CASE 
               WHEN t.manager_id = u.user_id THEN 'Manager'
               ELSE 'Player'
           END as role_in_team
    FROM team_members tm
    JOIN users u ON tm.user_id = u.user_id
    JOIN teams t ON tm.team_id = t.team_id
    WHERE tm.team_id = ?
");
$stmt->bind_param('i', $team_id);
$stmt->execute();
$allMembers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check if premier team already linked to this team manager
$stmt = $conn->prepare("SELECT pt.*, GROUP_CONCAT(ptm.user_id) as member_ids FROM premier_teams pt LEFT JOIN premier_team_members ptm ON pt.id = ptm.premier_team_id WHERE pt.created_by = ? GROUP BY pt.id LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$existingPremier = $stmt->get_result()->fetch_assoc();
$stmt->close();

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'connect') {
        $premierName = trim($_POST['premier_team_name'] ?? '');
        $premierTag  = strtoupper(trim($_POST['premier_team_tag'] ?? ''));
        $selectedMembers = $_POST['selected_members'] ?? [];

        if (empty($premierName) || empty($premierTag)) {
            $flash = ['type' => 'danger', 'msg' => 'กรุณากรอกชื่อทีมและ Team Tag'];
        } elseif (strlen($premierTag) < 2 || strlen($premierTag) > 10) {
            $flash = ['type' => 'danger', 'msg' => 'Team Tag ต้องมี 2-10 ตัวอักษร'];
        } elseif (empty($selectedMembers)) {
            $flash = ['type' => 'danger', 'msg' => 'กรุณาเลือกสมาชิกอย่างน้อย 1 คน'];
        } else {
            try {
                $conn->begin_transaction();

                if ($existingPremier) {
                    // Update existing
                    $stmt = $conn->prepare("UPDATE premier_teams SET team_name = ?, team_tag = ? WHERE id = ? AND created_by = ?");
                    $stmt->bind_param('ssii', $premierName, $premierTag, $existingPremier['id'], $user_id);
                    $stmt->execute();
                    $stmt->close();
                    $premierTeamId = $existingPremier['id'];

                    // Delete old members and re-insert
                    $stmt = $conn->prepare("DELETE FROM premier_team_members WHERE premier_team_id = ?");
                    $stmt->bind_param('i', $premierTeamId);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Check tag uniqueness
                    $stmt = $conn->prepare("SELECT id FROM premier_teams WHERE team_tag = ?");
                    $stmt->bind_param('s', $premierTag);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        throw new Exception('Team Tag "' . $premierTag . '" ถูกใช้งานแล้ว');
                    }
                    $stmt->close();

                    $stmt = $conn->prepare("INSERT INTO premier_teams (team_name, team_tag, created_by) VALUES (?, ?, ?)");
                    $stmt->bind_param('ssi', $premierName, $premierTag, $user_id);
                    $stmt->execute();
                    $premierTeamId = $conn->insert_id;
                    $stmt->close();
                }

                // Insert members
                $validMemberIds = array_column($allMembers, 'user_id');
                foreach ($selectedMembers as $memberId) {
                    $memberId = (int)$memberId;
                    if (!in_array($memberId, $validMemberIds)) continue;
                    $role = ($memberId === $user_id) ? 'Manager' : 'Player';
                    $stmt = $conn->prepare("INSERT INTO premier_team_members (premier_team_id, user_id, role_in_team) VALUES (?, ?, ?)");
                    $stmt->bind_param('iis', $premierTeamId, $memberId, $role);
                    $stmt->execute();
                    $stmt->close();
                }

                $conn->commit();
                $flash = ['type' => 'success', 'msg' => $existingPremier ? 'อัปเดตข้อมูล Premier Team เรียบร้อย!' : 'เชื่อมต่อ Premier Team สำเร็จ!'];

                // Refresh
                $stmt = $conn->prepare("SELECT pt.*, GROUP_CONCAT(ptm.user_id) as member_ids FROM premier_teams pt LEFT JOIN premier_team_members ptm ON pt.id = ptm.premier_team_id WHERE pt.created_by = ? GROUP BY pt.id LIMIT 1");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $existingPremier = $stmt->get_result()->fetch_assoc();
                $stmt->close();

            } catch (Exception $e) {
                $conn->rollback();
                $flash = ['type' => 'danger', 'msg' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
            }
        }
    } elseif ($action === 'disconnect') {
        if ($existingPremier) {
            try {
                $conn->begin_transaction();
                $stmt = $conn->prepare("DELETE FROM premier_teams WHERE id = ? AND created_by = ?");
                $stmt->bind_param('ii', $existingPremier['id'], $user_id);
                $stmt->execute();
                $stmt->close();
                $conn->commit();
                $existingPremier = null;
                $flash = ['type' => 'success', 'msg' => 'ยกเลิกการเชื่อมต่อ Premier Team เรียบร้อย'];
            } catch (Exception $e) {
                $conn->rollback();
                $flash = ['type' => 'danger', 'msg' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
            }
        }
    }
}

// Get current premier members for pre-selecting
$currentPremierMemberIds = [];
if ($existingPremier && !empty($existingPremier['member_ids'])) {
    $currentPremierMemberIds = array_map('intval', explode(',', $existingPremier['member_ids']));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Premier Team Connect – <?= htmlspecialchars($team['team_name']) ?></title>
<?php include '../../utils/link.php'; ?>
<link rel="stylesheet" href="../css/admin.css">
<style>
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
    --warning: #f59e0b;
    --border: rgba(255, 255, 255, 0.1);
    --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
    --premier-gold: #f5a623;
    --premier-gold-dim: rgba(245, 166, 35, 0.15);
    --premier-glow: 0 0 20px rgba(245, 166, 35, 0.3);
}

* { box-sizing: border-box; }

body {
    background-color: var(--bg-primary);
    color: var(--text-primary);
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}

/* ── Page Header ── */
.page-header {
    padding: 2rem 0 1.5rem;
    border-bottom: 1px solid var(--border);
    margin-bottom: 2rem;
}

.page-header-inner {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.premier-icon {
    width: 56px;
    height: 56px;
    background: var(--premier-gold-dim);
    border: 1px solid rgba(245, 166, 35, 0.4);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    flex-shrink: 0;
    box-shadow: var(--premier-glow);
}

.page-header h1 {
    margin: 0 0 0.25rem;
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1.2;
}

.page-header .sub {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0;
}

.back-link {
    margin-left: auto;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    transition: color 0.2s;
}

.back-link:hover { color: var(--text-primary); }

/* ── Status Banner ── */
.status-banner {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 1px solid;
}

.status-banner.connected {
    background: rgba(16, 185, 129, 0.08);
    border-color: rgba(16, 185, 129, 0.3);
}

.status-banner.not-connected {
    background: rgba(245, 166, 35, 0.08);
    border-color: rgba(245, 166, 35, 0.3);
}

.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-dot.online {
    background: var(--success);
    box-shadow: 0 0 8px var(--success);
    animation: pulse-green 2s infinite;
}

.status-dot.offline {
    background: var(--premier-gold);
}

@keyframes pulse-green {
    0%, 100% { box-shadow: 0 0 6px var(--success); }
    50%       { box-shadow: 0 0 14px var(--success); }
}

.status-banner .label { font-weight: 600; }
.status-banner .detail { color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.2rem; }

/* ── Cards ── */
.card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    backdrop-filter: blur(10px);
    box-shadow: var(--shadow);
}

.card.premier-card {
    border-color: rgba(245, 166, 35, 0.25);
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(20, 30, 48, 0.9) 100%);
}

.card-title {
    margin: 0 0 1.5rem;
    font-weight: 600;
    font-size: 1.15rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.card-title .icon {
    color: var(--premier-gold);
}

/* ── Form ── */
.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    font-size: 0.9rem;
}

.form-label span {
    color: var(--danger);
    margin-left: 2px;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--premier-gold);
    box-shadow: 0 0 0 3px rgba(245, 166, 35, 0.15);
}

.form-hint {
    margin-top: 0.4rem;
    font-size: 0.8rem;
    color: var(--text-secondary);
}

.input-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    align-items: start;
}

.tag-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 48px;
    padding: 0 1.25rem;
    background: var(--premier-gold-dim);
    border: 1px solid rgba(245, 166, 35, 0.4);
    border-radius: 8px;
    color: var(--premier-gold);
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: 0.08em;
    min-width: 90px;
    text-align: center;
    margin-top: 28px;
    white-space: nowrap;
}

/* ── Member Selection ── */
.members-section-title {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.select-all-btn {
    background: none;
    border: none;
    color: var(--accent);
    font-size: 0.8rem;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
    text-underline-offset: 3px;
}

.members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 0.75rem;
}

.member-select-card {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.875rem 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    user-select: none;
}

.member-select-card:hover {
    border-color: rgba(245, 166, 35, 0.4);
    background: rgba(30, 41, 59, 0.9);
}

.member-select-card.selected {
    border-color: var(--premier-gold);
    background: var(--premier-gold-dim);
    box-shadow: 0 0 0 1px rgba(245, 166, 35, 0.2);
}

.member-select-card input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.member-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border);
    flex-shrink: 0;
    background: var(--bg-secondary);
}

.member-avatar-placeholder {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: var(--bg-primary);
    border: 2px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--text-secondary);
    font-size: 1rem;
}

.member-info { flex: 1; min-width: 0; }

.member-name {
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 0.15rem;
}

.member-riot {
    font-size: 0.75rem;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.member-role-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 5px;
    font-weight: 600;
    flex-shrink: 0;
}

.role-manager {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.role-player {
    background: rgba(148, 163, 184, 0.1);
    color: var(--text-secondary);
    border: 1px solid var(--border);
}

.check-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid var(--border);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 0.7rem;
}

.member-select-card.selected .check-icon {
    background: var(--premier-gold);
    border-color: var(--premier-gold);
    color: #000;
}

/* ── Buttons ── */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    gap: 0.5rem;
}

.btn-premier {
    background: linear-gradient(135deg, #f5a623 0%, #e8950f 100%);
    color: #000;
    box-shadow: 0 4px 14px rgba(245, 166, 35, 0.3);
}

.btn-premier:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(245, 166, 35, 0.4);
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
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.2);
}

.btn-group {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 1.75rem;
}

/* ── Existing Premier Info ── */
.premier-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.info-tile {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 1rem;
}

.info-tile-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 0.4rem;
}

.info-tile-value {
    font-size: 1.1rem;
    font-weight: 700;
}

.tag-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.6rem;
    background: var(--premier-gold-dim);
    border: 1px solid rgba(245, 166, 35, 0.4);
    border-radius: 6px;
    color: var(--premier-gold);
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.06em;
}

/* ── Alerts ── */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.25);
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.25);
}

/* ── Counter ── */
.selection-counter {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    color: var(--text-secondary);
    padding: 0.3rem 0.75rem;
    background: var(--bg-secondary);
    border-radius: 20px;
    border: 1px solid var(--border);
}

.counter-num {
    font-weight: 700;
    color: var(--premier-gold);
}

/* ── Info box ── */
.info-box {
    padding: 1rem 1.25rem;
    background: rgba(59, 130, 246, 0.07);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.info-box strong { color: var(--text-primary); }

/* ── Responsive ── */
@media (max-width: 768px) {
    .input-row { grid-template-columns: 1fr; }
    .tag-preview { margin-top: 0; }
    .members-grid { grid-template-columns: 1fr; }
    .btn-group { flex-direction: column; }
    .btn-group .btn { width: 100%; justify-content: center; }
    .page-header-inner { flex-wrap: wrap; }
    .back-link { width: 100%; margin-left: 0; }
}
</style>
</head>
<body>
<div class="container">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-inner">
            <div class="premier-icon">🏆</div>
            <div>
                <h1>Premier Team Connect</h1>
                <p class="sub">เชื่อมต่อทีม <strong><?= htmlspecialchars($team['team_name']) ?></strong> กับระบบ Valorant Premier</p>
            </div>
            <a href="manage_team.php" class="back-link">
                <i class="fa fa-arrow-left"></i> กลับไปจัดการทีม
            </a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <i class="fa fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Status Banner -->
    <?php if ($existingPremier): ?>
        <div class="status-banner connected">
            <div class="status-dot online"></div>
            <div>
                <div class="label">เชื่อมต่อแล้ว</div>
                <div class="detail">Premier Team <strong><?= htmlspecialchars($existingPremier['team_name']) ?></strong> <span class="tag-chip"><?= htmlspecialchars($existingPremier['team_tag']) ?></span> · สร้างเมื่อ <?= date('d M Y', strtotime($existingPremier['created_at'])) ?></div>
            </div>
        </div>
    <?php else: ?>
        <div class="status-banner not-connected">
            <div class="status-dot offline"></div>
            <div>
                <div class="label">ยังไม่ได้เชื่อมต่อ</div>
                <div class="detail">กรอกข้อมูลด้านล่างเพื่อเชื่อมต่อทีมของคุณกับ Premier</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="info-box">
        <strong>เกี่ยวกับ Premier Connect</strong> — การเชื่อมต่อ Premier Team จะนำข้อมูลสมาชิกจากทีม <em><?= htmlspecialchars($team['team_name']) ?></em> ไปบันทึกในระบบ Premier เพื่อให้สามารถติดตามสถิติ ตารางการแข่งขัน และผลการแข่งขันได้ คุณสามารถเลือกสมาชิกที่จะเข้าร่วม Premier และแก้ไขได้ตลอดเวลา
    </div>

    <form method="post" id="premierForm">
        <input type="hidden" name="action" value="connect">

        <!-- Team Info Card -->
        <div class="card premier-card">
            <h3 class="card-title">
                <span class="icon"><i class="fa fa-shield"></i></span>
                ข้อมูล Premier Team
            </h3>

            <div class="input-row">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">ชื่อทีม Premier <span>*</span></label>
                    <input
                        type="text"
                        name="premier_team_name"
                        class="form-control"
                        placeholder="เช่น Talon Esports Premier"
                        value="<?= htmlspecialchars($existingPremier['team_name'] ?? $team['team_name']) ?>"
                        required
                        maxlength="100">
                    <div class="form-hint">ชื่อที่ใช้แสดงในการแข่งขัน Premier</div>
                </div>
                <div>
                    <label class="form-label">Team Tag <span>*</span></label>
                    <input
                        type="text"
                        name="premier_team_tag"
                        id="tagInput"
                        class="form-control"
                        placeholder="TLN"
                        value="<?= htmlspecialchars($existingPremier['team_tag'] ?? $team['abbreviation'] ?? '') ?>"
                        required
                        minlength="2"
                        maxlength="10"
                        style="text-transform:uppercase; letter-spacing:0.08em; font-weight:700;"
                        oninput="this.value=this.value.toUpperCase(); document.getElementById('tagPreview').textContent=this.value||'TAG'">
                    <div class="form-hint">2–10 ตัวอักษร</div>
                </div>
            </div>

            <!-- Tag Preview -->
            <div style="margin-top:1.25rem; display:flex; align-items:center; gap:0.75rem;">
                <span style="font-size:0.85rem; color:var(--text-secondary);">ตัวอย่างการแสดงผล:</span>
                <span style="font-weight:700; font-size:1rem;"><?= htmlspecialchars($existingPremier['team_name'] ?? $team['team_name']) ?></span>
                <span class="tag-chip" id="tagPreview"><?= htmlspecialchars($existingPremier['team_tag'] ?? $team['abbreviation'] ?? 'TAG') ?></span>
            </div>
        </div>

        <!-- Member Selection Card -->
        <div class="card">
            <h3 class="card-title">
                <span class="icon"><i class="fa fa-users"></i></span>
                เลือกสมาชิก Premier
            </h3>

            <div class="members-section-title">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <span>สมาชิกในทีม (<?= count($allMembers) ?> คน)</span>
                    <span class="selection-counter">
                        เลือกแล้ว <span class="counter-num" id="selCount">0</span> คน
                    </span>
                </div>
                <button type="button" class="select-all-btn" id="selectAllBtn" onclick="toggleSelectAll()">เลือกทั้งหมด</button>
            </div>

            <?php if (empty($allMembers)): ?>
                <div style="text-align:center; padding:2rem; color:var(--text-secondary);">
                    <i class="fa fa-user-slash" style="font-size:2rem; margin-bottom:0.75rem; display:block; opacity:0.4;"></i>
                    ยังไม่มีสมาชิกในทีม
                </div>
            <?php else: ?>
                <div class="members-grid">
                    <?php foreach ($allMembers as $m):
                        $isSelected = in_array((int)$m['user_id'], $currentPremierMemberIds);
                        if (empty($m['profile_img'])) {
                            $avatar = null;
                        } elseif (preg_match('#^https?://#i', $m['profile_img'])) {
                            $avatar = $m['profile_img'];
                        } else {
                            $p = str_replace('\\', '/', $m['profile_img']);
                            $p = ltrim($p, '/');
                            $avatar = '/' . ltrim('VALPROJECT/' . $p, '/');
                        }
                    ?>
                    <label class="member-select-card <?= $isSelected ? 'selected' : '' ?>" onclick="toggleCard(this)">
                        <input
                            type="checkbox"
                            name="selected_members[]"
                            value="<?= (int)$m['user_id'] ?>"
                            <?= $isSelected ? 'checked' : '' ?>>
                        <?php if ($avatar): ?>
                            <img src="<?= htmlspecialchars($avatar) ?>" alt="" class="member-avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="member-avatar-placeholder" style="display:none;"><i class="fa fa-user"></i></div>
                        <?php else: ?>
                            <div class="member-avatar-placeholder"><i class="fa fa-user"></i></div>
                        <?php endif; ?>
                        <div class="member-info">
                            <div class="member-name"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></div>
                            <div class="member-riot"><?= htmlspecialchars($m['riot_id'] ?: '—') ?></div>
                        </div>
                        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:0.4rem;">
                            <span class="member-role-badge <?= strtolower($m['role_in_team']) === 'manager' ? 'role-manager' : 'role-player' ?>">
                                <?= htmlspecialchars($m['role_in_team'] ?: 'Player') ?>
                            </span>
                            <div class="check-icon">
                                <i class="fa fa-check"></i>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="btn-group">
            <button type="submit" class="btn btn-premier">
                <i class="fa fa-<?= $existingPremier ? 'sync' : 'link' ?>"></i>
                <?= $existingPremier ? 'อัปเดต Premier Team' : 'เชื่อมต่อ Premier Team' ?>
            </button>
            <a href="manage_team.php" class="btn btn-outline">
                <i class="fa fa-times"></i> ยกเลิก
            </a>
            <?php if ($existingPremier): ?>
                <button type="button" class="btn btn-danger" id="disconnectBtn">
                    <i class="fa fa-unlink"></i> ยกเลิกการเชื่อมต่อ
                </button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Disconnect Form -->
    <form id="disconnectForm" method="post" style="display:none;">
        <input type="hidden" name="action" value="disconnect">
    </form>

    <br><br>
</div>

<script>
// ── Selection counter ──
function updateCounter() {
    const checked = document.querySelectorAll('input[name="selected_members[]"]:checked').length;
    document.getElementById('selCount').textContent = checked;
}

function toggleCard(label) {
    const cb = label.querySelector('input[type="checkbox"]');
    // The click on the label already toggles the checkbox — just sync the class
    setTimeout(() => {
        label.classList.toggle('selected', cb.checked);
        updateCounter();
    }, 0);
}

let allSelected = false;
function toggleSelectAll() {
    const cards = document.querySelectorAll('.member-select-card');
    const checkboxes = document.querySelectorAll('input[name="selected_members[]"]');
    allSelected = !allSelected;
    checkboxes.forEach((cb, i) => {
        cb.checked = allSelected;
        cards[i].classList.toggle('selected', allSelected);
    });
    document.getElementById('selectAllBtn').textContent = allSelected ? 'ยกเลิกทั้งหมด' : 'เลือกทั้งหมด';
    updateCounter();
}

// Tag preview sync
document.getElementById('tagInput')?.addEventListener('input', function () {
    document.getElementById('tagPreview').textContent = this.value || 'TAG';
});

// Disconnect confirmation
document.getElementById('disconnectBtn')?.addEventListener('click', function () {
    if (!confirm('ยืนยันการยกเลิกการเชื่อมต่อ Premier Team?\nข้อมูล Premier จะถูกลบออก')) return;
    document.getElementById('disconnectForm').submit();
});

// Form validation
document.getElementById('premierForm').addEventListener('submit', function (e) {
    const selected = document.querySelectorAll('input[name="selected_members[]"]:checked').length;
    if (selected === 0) {
        e.preventDefault();
        showNotif('กรุณาเลือกสมาชิกอย่างน้อย 1 คน', 'error');
        return;
    }
    if (!confirm('ยืนยันการ' + (<?= $existingPremier ? 'true' : 'false' ?> ? 'อัปเดต' : 'เชื่อมต่อ') + ' Premier Team?')) {
        e.preventDefault();
    }
});

// Init counter
updateCounter();

// Toast notification
function showNotif(msg, type) {
    const el = document.createElement('div');
    el.textContent = msg;
    el.style.cssText = `
        position:fixed; top:20px; right:20px; padding:12px 20px;
        border-radius:8px; color:white; font-weight:500; z-index:9999;
        box-shadow:0 4px 16px rgba(0,0,0,0.3); font-size:.9rem;
        transition: opacity .3s, transform .3s;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
    `;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3000);
}
</script>
</body>
</html>