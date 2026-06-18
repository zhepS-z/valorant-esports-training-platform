<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    // แจ้งเตือนก่อน redirect
    echo "<script>
        alert('กรุณาเข้าสู่ระบบก่อนใช้งานหน้านี้');
        window.location.href = '/VALPROJECT/auth/login.php';
    </script>";
    exit;
}

// ตรวจสอบสถานะแบน
// ถ้า db.php ยังไม่ได้ include ให้ include เอง
if (!isset($conn) && !isset($pdo)) {
    // หา path ที่ถูกต้อง
    $db_path = null;
    if (file_exists(__DIR__ . '/../utils/db.php')) {
        $db_path = __DIR__ . '/../utils/db.php';
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . '/VALPROJECT/utils/db.php')) {
        $db_path = $_SERVER['DOCUMENT_ROOT'] . '/VALPROJECT/utils/db.php';
    }
    
    if ($db_path) {
        define('SKIP_BAN_CHECK', true); // ป้องกัน infinite redirect จาก db.php
        include $db_path;
    }
}

// ตรวจสอบสถานะแบน
if (isset($conn) || isset($pdo)) {
    $user_id = (int)$_SESSION['user_id'];
    
    // ใช้ connection ที่มีอยู่
    if (isset($conn)) {
        // ใช้ mysqli
        $stmt = $conn->prepare("SELECT ban_until FROM users WHERE user_id = ? LIMIT 1");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    } else {
        // ใช้ PDO
        $stmt = $pdo->prepare("SELECT ban_until FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($user && $user['ban_until']) {
        $ban_until_time = strtotime($user['ban_until']);
        $current_time = time();
        
        if ($ban_until_time > $current_time) {
            // ผู้ใช้ถูกแบน ทำการออกจากระบบและลบ session
            session_destroy();
            
            if ($user['ban_until'] === '9999-12-31 23:59:59') {
                $ban_msg = "บัญชีของคุณถูกแบนอย่างถาวรและไม่สามารถเข้าใช้งานได้";
            } else {
                $days_remaining = ceil(($ban_until_time - $current_time) / 86400);
                $ban_msg = "บัญชีของคุณถูกแบนชั่วคราว สามารถล็อกอินได้ใน " . $days_remaining . " วัน";
            }
            
            echo "<script>
                alert('" . addslashes($ban_msg) . "');
                window.location.href = '../auth/login.php';
            </script>";
            exit;
        }
    }
}
?>
