<?php
session_start();
header('Content-Type: application/json');
define('ACCESS', true);
require_once '../../utils/apikey.php';
require_once '../../auth/auth_check.php';
include '../../utils/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'ต้องล็อกอินก่อน']);
    exit;
}

$position = $_POST['position'] ?? '';
$rank = $_POST['rank'] ?? '';
$experience = $_POST['experience'] ?? '';

if (!$position || !$experience) {
    echo json_encode(['success'=>false,'message'=>'กรอกข้อมูลไม่ครบ']);
    exit;
}

// optional: validate rank against allowed list
$allowed = ['','Unranked','Iron','Bronze','Silver','Gold','Platinum','Diamond','Ascendant','Immortal','Radiant'];
if (!in_array($rank, $allowed, true)) $rank = '';

$stmt = $conn->prepare("INSERT INTO lfp_posts (user_id, position, `rank`, experience, created_at) VALUES (?, ?, ?, ?, NOW())");
if (!$stmt) {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
    exit;
}
$stmt->bind_param("isss", $_SESSION['user_id'], $position, $rank, $experience);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    echo json_encode(['success'=>true,'message'=>'สร้างโพสต์เรียบร้อย']);
} else {
    echo json_encode(['success'=>false,'message'=>'ไม่สามารถบันทึกโพสต์: '.$conn->error]);
}