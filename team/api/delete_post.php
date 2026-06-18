<?php
// dev: แสดง error ชั่วคราว (ปิดก่อนขึ้น production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json; charset=utf-8');
define('ACCESS', true);
require_once __DIR__ . '/../../auth/auth_check.php';
require_once __DIR__ . '/../../utils/db.php';

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$uid) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Helper to send json and exit
function out($arr) {
    echo json_encode($arr);
    exit;
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
$is_published = isset($_POST['is_published']) ? $_POST['is_published'] : null;

// CASE A: Delete a post by id (called by generic .delete-post-btn)
// Try to delete from lft_posts (only owner or admin)
// If not found, attempt lfp_posts
if ($post_id) {
    // try lft_posts
    $stmt = $conn->prepare("DELETE FROM lft_posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $post_id, $uid);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        out(['success' => true, 'message' => 'LFT post deleted']);
    }

    // try lfp_posts
    $stmt = $conn->prepare("DELETE FROM lfp_posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $post_id, $uid);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        out(['success' => true, 'message' => 'LFP post deleted']);
    }

    out(['success' => false, 'message' => 'Post not found or permission denied']);
}

// CASE B: Update team's is_published (unpublish) and also delete related lft_posts when set to 0
if ($team_id && $is_published !== null) {
    // validate value (0 or 1)
    $is_published_val = ($is_published === '1' || $is_published === 1 || $is_published === 'true') ? 1 : 0;

    // fetch manager_id for permission check
    $stmt = $conn->prepare("SELECT manager_id FROM teams WHERE team_id = ? LIMIT 1");
    $stmt->bind_param('i', $team_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        out(['success' => false, 'message' => 'Team not found']);
    }
    $row = $res->fetch_assoc();
    $manager_id = (int)$row['manager_id'];
    $stmt->close();

    // Only manager or admin role should be allowed
    $is_admin = false;
    $u = $conn->query("SELECT role FROM users WHERE user_id = $uid LIMIT 1")->fetch_assoc();
    if ($u && isset($u['role']) && $u['role'] === 'admin') $is_admin = true;

    if ($uid !== $manager_id && !$is_admin) {
        out(['success' => false, 'message' => 'Permission denied']);
    }

    // update teams.is_published
    $stmt = $conn->prepare("UPDATE teams SET is_published = ? WHERE team_id = ?");
    $stmt->bind_param('ii', $is_published_val, $team_id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        out(['success' => false, 'message' => 'Failed to update team']);
    }

    // If unpublishing (is_published = 0) then delete related lft_posts of the manager
    if ($is_published_val === 0) {
        $stmt = $conn->prepare("DELETE FROM lft_posts WHERE user_id = ?");
        $stmt->bind_param('i', $manager_id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        out(['success' => true, 'message' => 'Team unpublished', 'deleted_lft_posts' => $deleted]);
    }

    out(['success' => true, 'message' => 'Team updated']);
}

$conn->close();