<?php

/**
 * Trigger real-time notification to user
 */
function triggerNotification($target_user_id, $type, $title, $body, $meta = []) {
    // ✅ Send TCP message to Node.js notification server
    $notification_data = [
        'type' => 'trigger_user_notification',
        'data' => [
            'target_user_id' => $target_user_id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'meta' => $meta
        ]
    ];
    
    try {
        // Connect to TCP server (port 6001)
        $socket = fsockopen('127.0.0.1', 6001, $errno, $errstr, 2);
        if ($socket) {
            $json_message = json_encode($notification_data);
            fwrite($socket, $json_message . "\n");
            fclose($socket);
            error_log("✅ Notification triggered for user " . $target_user_id);
            return true;
        } else {
            error_log("⚠️ TCP Connection failed: $errstr ($errno)");
            return false;
        }
    } catch (Exception $e) {
        error_log("❌ Error triggering notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Save notification to database (fallback)
 */
function saveNotificationToDB($target_user_id, $type, $title, $body, $meta = []) {
    global $conn;
    
    $query = "INSERT INTO user_notifications 
              (user_id, type, title, body, meta, is_read, created_at)
              VALUES (?, ?, ?, ?, ?, 0, NOW())";
    
    if ($stmt = $conn->prepare($query)) {
        $meta_json = json_encode($meta);
        $stmt->bind_param('issss', $target_user_id, $type, $title, $body, $meta_json);
        return $stmt->execute();
    }
    return false;
}
?>