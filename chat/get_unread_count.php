<?php
session_start();
header('Content-Type: application/json');
require_once '../utils/db.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM chat_notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'count' => (int)$result['count']
]);