<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/VALPROJECT/utils/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$last_check = $_GET['last_check'] ?? date('Y-m-d H:i:s', time() - 3600);

if (!$user_id || !isset($conn)) {
    echo json_encode(['success' => false]);
    exit;
}

$notifications = [];

// ✅ เช็ค Scrim Reservations
$sql = "SELECT n.id AS notif_id, n.reservation_id, n.created_at,
                r.user_id, u.first_name, u.last_name, u.profile_img,
                s.scrim_id, t.team_id, t.team_name,
                rt.team_logo
         FROM scrim_reservation_notifications n
         JOIN scrim_reservations r ON r.reservation_id = n.reservation_id
         JOIN users u ON u.user_id = r.user_id
         JOIN scrims s ON s.scrim_id = n.scrim_id
         JOIN teams t ON t.team_id = n.team_id
         LEFT JOIN teams rt ON rt.team_id = u.team_id
         WHERE n.manager_id = ? AND n.status = 'pending' AND n.created_at > ?
         ORDER BY n.created_at DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('is', $user_id, $last_check);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $reserverName = $row['team_name'] ?: ($row['first_name'].' '.$row['last_name']);
        $reserverProfile = !empty($row['team_logo']) ? '/VALPROJECT/uploads/team_logos/' . basename($row['team_logo']) : '/VALPROJECT/img/avatar_default.png';
        
        $notifications[] = [
            'type' => 'scrim_reservation',
            'id' => $row['notif_id'],
            'reservation_id' => $row['reservation_id'],
            'team_id' => $row['team_id'],
            'team_name' => $row['team_name'],
            'reserver_name' => $reserverName,
            'reserver_profile' => $reserverProfile,
            'scrim_id' => $row['scrim_id'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();
}

// ✅ เช็ค User Notifications
$sql2 = "SELECT id, type, title, body, meta, created_at FROM user_notifications 
          WHERE user_id = ? AND created_at > ?
          ORDER BY created_at DESC";
          
if ($stmt2 = $conn->prepare($sql2)) {
    $stmt2->bind_param('is', $user_id, $last_check);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $notifications[] = [
            'type' => 'user_notification',
            'id' => $row['id'],
            'title' => $row['title'],
            'body' => $row['body'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt2->close();
}

// ✅ เช็ค Join Requests
$sql = "SELECT r.request_id, r.status, r.created_at, t.team_id, t.team_name, u.user_id, u.first_name, u.last_name, u.profile_img
        FROM team_join_requests r
        JOIN teams t ON r.team_id = t.team_id
        JOIN users u ON r.user_id = u.user_id
        WHERE t.manager_id = ? AND r.status = 'pending' AND r.created_at > ?
        ORDER BY r.created_at DESC
        LIMIT 6";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('is', $user_id, $last_check);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $notifications[] = [
            'type' => 'join_request',
            'id' => $row['request_id'],
            'team_id' => $row['team_id'],
            'team_name' => $row['team_name'],
            'from_user' => $row['user_id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'profile' => $row['profile_img'] ?: '/valproject/img/avatar_default.png',
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();
}

// ✅ เช็ค LFP Applications (แจ้งเตือน manager เจ้าของโพสต์ lfp)
$sql = "SELECT a.app_id, a.post_id, a.user_id, a.status, a.created_at,
               p.position, p.rank, p.user_id AS post_owner_id,
               u.first_name, u.last_name, u.profile_img
        FROM lfp_applications a
        JOIN lfp_posts p ON a.post_id = p.id
        JOIN users u ON a.user_id = u.user_id
        WHERE p.user_id = ? AND a.status = 'pending' AND a.created_at > ?
        ORDER BY a.created_at DESC
        LIMIT 6";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('is', $user_id, $last_check);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $notifications[] = [
            'type' => 'lfp_application',
            'id' => $row['app_id'],
            'post_id' => $row['post_id'],
            'from_user' => $row['user_id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'profile' => $row['profile_img'] ?: '/valproject/img/avatar_default.png',
            'position' => $row['position'],
            'rank' => $row['rank'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
?>