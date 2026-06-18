<?php
session_start();
header('Content-Type: application/json');

// Error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set error handling to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $errstr,
        'debug' => ['file' => $errfile, 'line' => $errline]
    ]);
    exit;
});

// Catch exceptions
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'debug' => ['file' => $e->getFile(), 'line' => $e->getLine()]
    ]);
    exit;
});

try {
    require_once '../../utils/db.php';
    require_once '../../utils/notification_helper.php';

    if (!isset($_POST['request_id'], $_POST['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
        exit;
    }

    $request_id = intval($_POST['request_id']);
    $action = trim($_POST['action']);

    if (!in_array($action, ['accepted', 'declined'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'action ไม่ถูกต้อง']);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'ไม่ได้เข้าสู่ระบบ']);
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];

    // ตรวจสอบสิทธิ์และดึง team_id, user_id, team_name ของ request นี้
    $sql = "SELECT r.team_id, r.user_id, t.current_size, t.max_size, t.team_name
            FROM team_join_requests r
            JOIN teams t ON r.team_id = t.team_id
            WHERE r.request_id = ? AND t.manager_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare Error: ' . $conn->error);
    }
    $stmt->bind_param('ii', $request_id, $user_id);
    if (!$stmt->execute()) {
        throw new Exception('Execute Error: ' . $stmt->error);
    }
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์หรือไม่พบคำขอ']);
        exit;
    }
    $row = $res->fetch_assoc();
    $team_id = (int)$row['team_id'];
    $join_user_id = (int)$row['user_id'];
    $current_size = (int)$row['current_size'];
    $max_size = (int)$row['max_size'];
    $team_name = $row['team_name'];

    // ถ้าเป็นการรับเข้าทีม
    if ($action === 'accepted') {
        // ตรวจสอบจำนวนสมาชิก
        if ($current_size >= $max_size) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ทีมเต็มแล้ว']);
            exit;
        }

        // เริ่ม transaction
        $conn->begin_transaction();

        try {
            // 1. อัปเดตสถานะคำขอ
            $sql = "UPDATE team_join_requests SET status = 'accepted' WHERE request_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare Error: ' . $conn->error);
            }
            $stmt->bind_param('i', $request_id);
            if (!$stmt->execute()) {
                throw new Exception('Execute Error: ' . $stmt->error);
            }

            // 2. เพิ่มเข้า team_members (ถ้ายังไม่มี)
            $sql = "SELECT id FROM team_members WHERE team_id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare Error: ' . $conn->error);
            }
            $stmt->bind_param('ii', $team_id, $join_user_id);
            if (!$stmt->execute()) {
                throw new Exception('Execute Error: ' . $stmt->error);
            }
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $sql = "INSERT INTO team_members (team_id, user_id, role_in_team) VALUES (?, ?, 'Player')";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Prepare Error: ' . $conn->error);
                }
                $stmt->bind_param('ii', $team_id, $join_user_id);
                if (!$stmt->execute()) {
                    throw new Exception('Execute Error: ' . $stmt->error);
                }
            }

            // 3. อัปเดต users.team_id
            $sql = "UPDATE users SET team_id = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare Error: ' . $conn->error);
            }
            $stmt->bind_param('ii', $team_id, $join_user_id);
            if (!$stmt->execute()) {
                throw new Exception('Execute Error: ' . $stmt->error);
            }

            // 4. เพิ่ม current_size ใน teams
            $sql = "UPDATE teams SET current_size = current_size + 1 WHERE team_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare Error: ' . $conn->error);
            }
            $stmt->bind_param('i', $team_id);
            if (!$stmt->execute()) {
                throw new Exception('Execute Error: ' . $stmt->error);
            }

            $conn->commit();

            // ✅ ส่งการแจ้งเตือนให้ผู้ที่ขอเข้าทีม (optional - ไม่ทำให้ request ล้มเหลว)
            if (function_exists('triggerNotification')) {
                @triggerNotification(
                    $join_user_id,
                    'team_request_accepted',
                    'ยินดีด้วย! ✅ ' . $team_name,
                    'คุณได้รับการยอมรับเข้า ' . $team_name . ' แล้ว!',
                    ['team_id' => $team_id, 'team_name' => $team_name, 'request_id' => $request_id]
                );
            }
            
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'บันทึกคำขอเข้าทีมสำเร็จ']);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    } else {
        // ถ้าเป็นการปฏิเสธ
        try {
            $sql = "UPDATE team_join_requests SET status = 'declined' WHERE request_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare Error: ' . $conn->error);
            }
            $stmt->bind_param('i', $request_id);
            if (!$stmt->execute()) {
                throw new Exception('Execute Error: ' . $stmt->error);
            }

            if ($stmt->affected_rows > 0) {
                // ✅ ส่งการแจ้งเตือนให้ผู้ที่ขอเข้าทีม (optional - ไม่ทำให้ request ล้มเหลว)
                if (function_exists('triggerNotification')) {
                    @triggerNotification(
                        $join_user_id,
                        'team_request_declined',
                        'คำขอถูกปฏิเสธ ❌ ' . $team_name,
                        'ขออภัย คำขอของคุณเข้า ' . $team_name . ' ถูกปฏิเสธแล้ว',
                        ['team_id' => $team_id, 'team_name' => $team_name, 'request_id' => $request_id]
                    );
                }
                
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'ปฏิเสธคำขอสำเร็จ']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'อัปเดตไม่สำเร็จ']);
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fatal Error: ' . $e->getMessage(),
        'debug' => ['file' => $e->getFile(), 'line' => $e->getLine()]
    ]);
}