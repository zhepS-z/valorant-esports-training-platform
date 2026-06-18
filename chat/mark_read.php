<?php
session_start();
header('Content-Type: application/json');
require_once '../utils/db.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("UPDATE chat_notifications SET is_read = 1 WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);