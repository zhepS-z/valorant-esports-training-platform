<?php
session_start();
define('ACCESS', true);
header('Content-Type: application/json; charset=utf-8');

require_once '../../auth/auth_check.php';
include '../../utils/db.php';

// สำหรับดีบั๊ก ถ้าต้องการให้เปิดแสดง error ชั่วคราว
// ini_set('display_errors', 1); error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post id']);
    exit;
}

// delete only if current user is the owner (single statement)
$stmt = $conn->prepare("DELETE FROM lfp_posts WHERE id = ? AND user_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: '.$conn->error]);
    exit;
}
$stmt->bind_param('ii', $post_id, $_SESSION['user_id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Post deleted']);
} else {
    // no rows affected = post not found or not owner
    echo json_encode(['success' => false, 'message' => 'Post not found or permission denied']);
}

$stmt->close();
$conn->close();