<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();  // เริ่ม output buffering เพื่อจับ error อื่น ๆ
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    include '../../utils/db.php';

    // ตรวจสอบการ include notification_helper อย่างปลอดภัย
    $notification_helper_path = $_SERVER['DOCUMENT_ROOT'] . '/VALPROJECT/utils/notification_helper.php';
    $has_notification = false;
    if (file_exists($notification_helper_path)) {
        @include_once $notification_helper_path;
        $has_notification = function_exists('triggerNotification');
    }

    if (!isset($_SESSION['user_id'])) {
        ob_end_clean();
        echo json_encode(['success'=>false, 'message'=>'Please login first.']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $team_id = (int)($_POST['team_id'] ?? 0);

    if (!$team_id) {
        ob_end_clean();
        echo json_encode(['success'=>false, 'message'=>'Invalid team.']);
        exit;
    }

    // เช็คว่ามีทีมอยู่แล้ว
    $userRes = $conn->query("SELECT team_id FROM users WHERE user_id = $user_id");
    if ($userRes && $userRes->num_rows > 0) {
        $myTeamId = $userRes->fetch_assoc()['team_id'];
        if ($myTeamId) {
            ob_end_clean();
            echo json_encode(['success'=>false, 'message'=>'You are already in a team.']);
            exit;
        }
    }

    // เช็คว่าขอเข้าทีมนี้ไปแล้วหรือยัง
    $check = $conn->prepare("SELECT request_id, status FROM team_join_requests WHERE team_id=? AND user_id=? LIMIT 1");
    $check->bind_param("ii", $team_id, $user_id);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        $check->bind_result($request_id_existing, $status_existing);
        $check->fetch();
        
        if ($status_existing === 'accepted') {
            ob_end_clean();
            echo json_encode(['success'=>false, 'message'=>'You already joined this team.']);
            $check->close();
            exit;
        }
        if ($status_existing === 'pending') {
            ob_end_clean();
            echo json_encode(['success'=>false, 'message'=>'You have already requested to join this team.']);
            $check->close();
            exit;
        }
    }
    $check->close();

    // เช็คว่ามีคำขอเดิมที่ถูกปฏิเสธ
    $declined_check = $conn->prepare("SELECT request_id FROM team_join_requests WHERE team_id=? AND user_id=? AND status='declined'");
    $declined_check->bind_param("ii", $team_id, $user_id);
    $declined_check->execute();
    $declined_check->store_result();
    $request_id = null;

    if ($declined_check->num_rows > 0) {
        $declined_check->bind_result($old_request_id);
        $declined_check->fetch();
        $request_id = $old_request_id;
        
        $update_sql = "UPDATE team_join_requests SET status = 'pending', created_at = NOW() WHERE request_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('i', $request_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // สร้างคำขอใหม่
        $sql = "INSERT INTO team_join_requests (team_id, user_id, status, created_at) VALUES (?, ?, 'pending', NOW())";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('ii', $team_id, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert join request");
            }
            $request_id = $conn->insert_id;
            $stmt->close();
        }
    }
    $declined_check->close();

    // เมื่อสร้างหรืออัปเดต join request
    if ($request_id) {
        $team_manager_sql = "SELECT manager_id, team_name FROM teams WHERE team_id = ?";
        if ($mgr_stmt = $conn->prepare($team_manager_sql)) {
            $mgr_stmt->bind_param('i', $team_id);
            $mgr_stmt->execute();
            $mgr_result = $mgr_stmt->get_result();
            
            if ($mgr_row = $mgr_result->fetch_assoc()) {
                $manager_id = $mgr_row['manager_id'];
                $team_name = $mgr_row['team_name'];
                
                // ดึงข้อมูล User ที่ขอเข้า
                $user_sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
                $u_stmt = $conn->prepare($user_sql);
                $u_stmt->bind_param('i', $user_id);
                $u_stmt->execute();
                $u_result = $u_stmt->get_result();
                $u_row = $u_result->fetch_assoc();
                
                if ($u_row) {
                    $user_name = $u_row['first_name'] . ' ' . $u_row['last_name'];
                    
                    // TRIGGER REAL-TIME NOTIFICATION (ถ้า function มีอยู่)
                    if ($has_notification) {
                        @triggerNotification(
                            $manager_id,
                            'join_request',
                            ucfirst($u_row['first_name']) . ' ขอเข้าร่วมทีม',
                            $user_name . ' ขอเข้าร่วมทีม ' . $team_name,
                            [
                                'request_id' => $request_id,
                                'team_id' => $team_id,
                                'user_id' => $user_id
                            ]
                        );
                    }
                }
                $u_stmt->close();
                
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'message' => 'Request sent successfully!',
                    'request_id' => $request_id
                ]);
            } else {
                ob_end_clean();
                echo json_encode(['success'=>false, 'message'=>'Team not found.']);
            }
            $mgr_stmt->close();
        } else {
            ob_end_clean();
            echo json_encode(['success'=>false, 'message'=>'Database error.']);
        }
    } else {
        ob_end_clean();
        echo json_encode(['success'=>false, 'message'=>'Failed to create request.']);
    }

    $conn->close();
    
} catch (Exception $e) {
    ob_end_clean();
    error_log("Request Join Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
?>