<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
define('ACCESS',true);
require_once '../../auth/auth_check.php';
require_once '../../utils/db.php';
require_once '../../utils/notification_helper.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }
$app_id = isset($_POST['app_id']) ? (int)$_POST['app_id'] : 0;
if ($app_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid app_id']); exit; }

// load application + post owner + owner's team
$sql = "SELECT a.app_id, a.post_id, a.user_id AS applicant_id, a.status AS app_status,
               p.user_id AS post_owner,
               u.team_id AS owner_team_id,
               t.team_id AS team_exists, t.current_size, t.max_size, t.manager_id, t.team_name
        FROM lfp_applications a
        JOIN lfp_posts p ON a.post_id = p.id
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN teams t ON u.team_id = t.team_id
        WHERE a.app_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i',$app_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) { echo json_encode(['success'=>false,'message'=>'Application not found']); exit; }

// permission: post owner or team manager
$current = (int)$_SESSION['user_id'];
$postOwner = (int)$row['post_owner'];
$teamId = $row['owner_team_id'] ? (int)$row['owner_team_id'] : null;
$teamManager = isset($row['manager_id']) ? (int)$row['manager_id'] : null;
if ($current !== $postOwner && $current !== $teamManager) {
    echo json_encode(['success'=>false,'message'=>'You are not allowed to invite']); exit;
}

if (empty($teamId)) { echo json_encode(['success'=>false,'message'=>'Poster has no team to invite into']); exit; }
if ($row['app_status'] !== 'pending') { echo json_encode(['success'=>false,'message'=>'Application already processed']); exit; }

// capacity check
$cur = (int)($row['current_size'] ?? 0);
$max = (int)($row['max_size'] ?? 0);
if ($max>0 && $cur >= $max) { echo json_encode(['success'=>false,'message'=>'Team is full']); exit; }

// applicant not already in a team
$applicantId = (int)$row['applicant_id'];
$stmt = $conn->prepare("SELECT team_id FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i',$applicantId);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!empty($u['team_id'])) { echo json_encode(['success'=>false,'message'=>'Applicant already in a team']); exit; }

// transaction: add member, update users.team_id, update teams.current_size, mark application accepted
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO team_members (team_id, user_id, role_in_team) VALUES (?, ?, 'Member')");
    $stmt->bind_param('ii', $teamId, $applicantId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE users SET team_id = ? WHERE user_id = ?");
    $stmt->bind_param('ii', $teamId, $applicantId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE teams SET current_size = current_size + 1 WHERE team_id = ?");
    $stmt->bind_param('i', $teamId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE lfp_applications SET status = 'accepted' WHERE app_id = ?");
    $stmt->bind_param('i', $app_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    
    // ✅ ส่งการแจ้งเตือนให้ผู้สมัคร
    triggerNotification(
        $applicantId,
        'lfp_application_accepted',
        'ยินดีด้วย! สมัครสำเร็จแล้ว! ✅',
        'คุณได้รับการสมัครเข้า ' . $row['team_name'] . ' แล้ว!',
        ['team_id' => $teamId, 'team_name' => $row['team_name']]
    );
    
    echo json_encode(['success'=>true,'message'=>'Applicant invited successfully']);
} catch (Exception $e) {
    $conn->rollback();
    error_log('Invite error: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Server error while inviting']);
}