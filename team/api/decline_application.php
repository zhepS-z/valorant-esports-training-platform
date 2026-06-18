<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
define('ACCESS', true);
require_once '../../auth/auth_check.php';
require_once '../../utils/db.php';
require_once '../../utils/notification_helper.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit; }
$app_id = isset($_POST['app_id']) ? (int)$_POST['app_id'] : 0;
if ($app_id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid app_id']); exit; }

// verify that current user is the owner of the post for this application and get applicant info
$sql = "SELECT a.app_id, a.user_id FROM lfp_applications a JOIN lfp_posts p ON a.post_id = p.id WHERE a.app_id = ? AND p.user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $app_id, $_SESSION['user_id']);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$r) { echo json_encode(['success'=>false,'message'=>'Not allowed or not found']); exit; }

$applicant_id = $r['user_id'];

// update status to declined
$stmt = $conn->prepare("UPDATE lfp_applications SET status = 'declined' WHERE app_id = ?");
$stmt->bind_param('i', $app_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // ✅ ส่งการแจ้งเตือนให้ผู้สมัคร
    triggerNotification(
        $applicant_id,
        'lfp_application_declined',
        'การสมัครถูกปฏิเสธ ❌',
        'ขออภัย การสมัครของคุณถูกปฏิเสธแล้ว',
        ['app_id' => $app_id]
    );
    
    echo json_encode(['success'=>true,'message'=>'ปฏิเสธคำสมัครเรียบร้อยแล้ว']);
}
else { 
    error_log('decline_application error: '.$conn->error); 
    echo json_encode(['success'=>false,'message'=>'Server error']); 
}