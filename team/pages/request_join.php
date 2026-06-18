<?php
session_start();
define('ACCESS', true);

try {
    require_once '../../utils/apikey.php';
    require_once '../../auth/auth_check.php';
    include '../../utils/db.php';

    header('Content-Type: application/json');

    // Check database connection
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }

    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    // Get team_id from POST
    $team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;

    if (!$team_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid team_id']);
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];

    // Check if user already has a team
    $userRes = $conn->query("SELECT team_id FROM users WHERE user_id = $user_id LIMIT 1");
    if ($userRes === false) {
        throw new Exception('Query error: ' . $conn->error);
    }
    
    if ($userRes->num_rows > 0) {
        $userData = $userRes->fetch_assoc();
        if (!empty($userData['team_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You already have a team']);
            exit;
        }
    }

    // Check if team exists
    $teamRes = $conn->query("SELECT team_id FROM teams WHERE team_id = $team_id LIMIT 1");
    if ($teamRes === false) {
        throw new Exception('Query error: ' . $conn->error);
    }
    
    if ($teamRes->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Team not found']);
        exit;
    }

    // Check if request already exists (only pending requests, not declined or accepted)
    $existingRes = $conn->query("SELECT request_id, status FROM team_join_requests WHERE user_id = $user_id AND team_id = $team_id LIMIT 1");
    if ($existingRes === false) {
        throw new Exception('Query error: ' . $conn->error);
    }
    
    $request_id = null;
    if ($existingRes->num_rows > 0) {
        $existingRequest = $existingRes->fetch_assoc();
        // 如果已接受，则不能再请求（因为用户已在队伍中）
        if ($existingRequest['status'] === 'accepted') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You already joined this team']);
            exit;
        }
        // 如果只是待处理，则不能重复请求
        if ($existingRequest['status'] === 'pending') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You already sent a request to this team']);
            exit;
        }
        // 如果是被拒绝，更新现有的请求而不是创建新的
        if ($existingRequest['status'] === 'declined') {
            $request_id = $existingRequest['request_id'];
            $updateSql = "UPDATE team_join_requests SET status = 'pending', created_at = NOW() WHERE request_id = $request_id";
            $updateRes = $conn->query($updateSql);
            if ($updateRes === false) {
                throw new Exception('Failed to update: ' . $conn->error);
            }
        }
    }

    // 如果没有现有请求或已更新，创建新请求
    if ($request_id === null) {
        $sql = "INSERT INTO team_join_requests (user_id, team_id, status, created_at) VALUES ($user_id, $team_id, 'pending', NOW())";
        $insertRes = $conn->query($sql);
        
        if ($insertRes === false) {
            throw new Exception('Failed to insert: ' . $conn->error . ' | SQL: ' . $sql);
        }
        
        $request_id = $conn->insert_id;
    }

    echo json_encode(['success' => true, 'message' => 'Request sent successfully', 'request_id' => $request_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

if (isset($conn)) {
    $conn->close();
}
?>
