<?php
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "valorant_esports";

// Create mysqli connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add this code to check the connection
if ($conn === false) {
    die("ERROR: Could not connect to database");
}

// For PDO
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (isset($_SESSION['user_id'])) {
    // ถ้ามีการล็อกอิน ดึงข้อมูล user
    $userId = $_SESSION['user_id'];
    $sql = "SELECT u.*, t.team_name 
            FROM users u 
            LEFT JOIN teams t ON u.team_id = t.team_id 
            WHERE u.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    // ตรวจสอบสถานะแบน
    if ($userData && $userData['ban_until']) {
        $ban_until_time = strtotime($userData['ban_until']);
        $current_time = time();
        
        if ($ban_until_time > $current_time) {
            // ผู้ใช้ถูกแบน ทำการออกจากระบบและลบ session
            session_destroy();
            
            if ($userData['ban_until'] === '9999-12-31 23:59:59') {
                $ban_msg = "บัญชีของคุณถูกแบนอย่างถาวรและไม่สามารถเข้าใช้งานได้";
            } else {
                $days_remaining = ceil(($ban_until_time - $current_time) / 86400);
                $ban_msg = "บัญชีของคุณถูกแบนชั่วคราว สามารถล็อกอินได้ใน " . $days_remaining . " วัน";
            }
            
            // Redirect เพื่อป้องกันการเข้าถึงหน้าอื่น
            if (!defined('SKIP_BAN_CHECK') || !SKIP_BAN_CHECK) {
                header("Location: /VALPROJECT/auth/login.php?banned=1");
                exit;
            }
        }
    }

    // เซ็ตค่า Session จากข้อมูลที่ได้
    if ($userData) {
        $_SESSION['first_name'] = $userData['first_name'];
        $_SESSION['last_name'] = $userData['last_name'];
        $_SESSION['team_name'] = $userData['team_name'];
        $_SESSION['team_id'] = $userData['team_id'];
    }
}
?>