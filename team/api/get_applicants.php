<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
define('ACCESS', true);

require_once '../../auth/auth_check.php';
include '../../utils/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post_id']);
    exit;
}

// verify current user is the post owner
$stmt = $conn->prepare("SELECT user_id FROM lfp_posts WHERE id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Post not found']);
    exit;
}

if ((int)$post['user_id'] !== (int)$_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You are not the post owner']);
    exit;
}

// get all applicants for this post
$sql = "SELECT a.app_id, a.user_id, a.message, a.status,
               u.first_name, u.last_name, u.email, u.profile_img
        FROM lfp_applications a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.post_id = ? AND a.status = 'pending'
        ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $post_id);
$stmt->execute();
$result = $stmt->get_result();

$applicants = [];
while ($row = $result->fetch_assoc()) {
    $applicants[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Applicants fetched',
    'data' => $applicants
]);
$conn->close();
?>
