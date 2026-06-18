<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
define('ACCESS', true);
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../auth/auth_check.php'; // optional: ensure logged in

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$description = trim($_POST['description'] ?? '');
$is_published = isset($_POST['is_published']) && $_POST['is_published'] == '1' ? 1 : 0;

if (empty($description)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Description cannot be empty']);
    exit;
}

try {
    // find team where user is manager
    $stmt = $conn->prepare("SELECT team_id FROM teams WHERE manager_id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        // user is not a manager — cannot post for a team
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'You are not manager of any team']);
        $stmt->close();
        exit;
    }
    $team = $res->fetch_assoc();
    $team_id = (int)$team['team_id'];
    $stmt->close();

    $conn->begin_transaction();

    // Check if user already has a post in lft_posts
    $check_stmt = $conn->prepare("SELECT id FROM lft_posts WHERE user_id = ? LIMIT 1");
    $check_stmt->bind_param('i', $user_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    $post_exists = $check_res->num_rows > 0;
    $check_stmt->close();

    if ($post_exists) {
        // UPDATE existing post
        $update_stmt = $conn->prepare("UPDATE lft_posts SET description = ?, created_at = NOW() WHERE user_id = ?");
        $update_stmt->bind_param('si', $description, $user_id);
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update post: " . $update_stmt->error);
        }
        $update_stmt->close();
    } else {
        // INSERT new post
        $insert_stmt = $conn->prepare("INSERT INTO lft_posts (user_id, description, created_at) VALUES (?, ?, NOW())");
        $insert_stmt->bind_param('is', $user_id, $description);
        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to create post: " . $insert_stmt->error);
        }
        $insert_stmt->close();
    }

    // update teams table: set description and is_published flag
    $team_stmt = $conn->prepare("UPDATE teams SET description = ?, is_published = ? WHERE team_id = ? AND manager_id = ?");
    $team_stmt->bind_param('siii', $description, $is_published, $team_id, $user_id);
    if (!$team_stmt->execute()) {
        throw new Exception("Failed to update team: " . $team_stmt->error);
    }
    $team_stmt->close();

    $conn->commit();
    echo json_encode(['success'=>true, 'message'=>'Post ' . ($post_exists ? 'updated' : 'created') . ' successfully']);
} catch (Exception $e) {
    if ($conn) $conn->rollback();
    http_response_code(500);
    error_log("Create LFT Post Error: " . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Server error: ' . $e->getMessage()]);
}
?>