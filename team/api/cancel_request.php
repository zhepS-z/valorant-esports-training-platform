<?php
session_start();
define('ACCESS', true);
require_once '../../utils/apikey.php';
require_once '../../auth/auth_check.php';
include '../../utils/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? null;
    $request_id = $_POST['request_id'] ?? null;

    if (!$user_id || !$request_id) {
        header('Location: request_status.php?error=invalid');
        exit;
    }

    // ตรวจสอบว่าคำขอเป็นของ user นี้และยัง pending อยู่
    $sql = "SELECT * FROM team_join_requests WHERE request_id = ? AND user_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // ลบคำขอ
        $del_sql = "DELETE FROM team_join_requests WHERE request_id = ?";
        $del_stmt = $conn->prepare($del_sql);
        $del_stmt->bind_param('i', $request_id);
        $del_stmt->execute();
        header('Location: request_status.php?cancel=success');
        exit;
    } else {
        header('Location: request_status.php?error=notfound');
        exit;
    }
} else {
    header('Location: request_status.php');
    exit;
}