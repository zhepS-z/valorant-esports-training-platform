<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
define('ACCESS',true);
require_once '../../auth/auth_check.php';
require_once '../../utils/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit;
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
if ($post_id <= 0) {
    echo json_encode(['success'=>false,'message'=>'Invalid post id']); exit;
}

$user_id = (int)$_SESSION['user_id'];

// check user has a team (only team members can invite/apply)
$stmt = $conn->prepare("SELECT team_id FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i',$user_id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();
$hasTeam = !empty($u['team_id']);
if (!$hasTeam) {
    // fallback check team_members
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM team_members WHERE user_id = ?");
    $stmt->bind_param('i',$user_id);
    $stmt->execute();
    $c = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (empty($c['c']) || $c['c'] == 0) {
        echo json_encode(['success'=>false,'message'=>'คุณต้องมีทีมเพื่อเชิญ']); exit;
    }
}

// prevent duplicate pending application by same user for same post
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM lfp_applications WHERE post_id = ? AND user_id = ? AND status = 'pending'");
$stmt->bind_param('ii',$post_id,$user_id);
$stmt->execute();
$ex = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!empty($ex['c']) && $ex['c'] > 0) {
    echo json_encode(['success'=>false,'message'=>'คุณได้ส่งคำเชิญไปแล้วสำหรับโพสต์นี้']); exit;
}

// insert application (no message)
$stmt = $conn->prepare("INSERT INTO lfp_applications (post_id, user_id, status) VALUES (?, ?, 'pending')");
$stmt->bind_param('ii', $post_id, $user_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) echo json_encode(['success'=>true,'message'=>'ส่งคำเชิญเรียบร้อยแล้ว']);
else echo json_encode(['success'=>false,'message'=>'Server error']);