<?php 
session_start();
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
include '../utils/db.php'; // ใช้ connection จาก db.php
// เช็คว่ามีการส่ง form มาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // ดึงข้อมูลผู้ใช้จากฐานข้อมูล โดยตรวจสอบเฉพาะ email (รวมสถานะแบนด้วย)
    $stmt = $pdo->prepare("SELECT user_id, riot_id, region, password, role, ban_until FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
      // ตรวจสอบสถานะแบน
      $is_banned = false;
      $ban_message = '';
      
      if ($user['ban_until']) {
        $ban_until_time = strtotime($user['ban_until']);
        $current_time = time();
        
        if ($ban_until_time > $current_time) {
          // ผู้ใช้ถูกแบนอยู่
          $is_banned = true;
          
          if ($user['ban_until'] === '9999-12-31 23:59:59') {
            $ban_message = "บัญชีของคุณถูกแบนอย่างถาวร";
          } else {
            $days_remaining = ceil(($ban_until_time - $current_time) / 86400);
            $ban_message = "บัญชีของคุณถูกแบนชั่วคราว สามารถล็อกอินได้ใน " . $days_remaining . " วัน";
          }
        }
      }
      
      if ($is_banned) {
        $error = $ban_message;
      } else {
        // login success (OTP removed)
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['riot_id'] = $user['riot_id'];
        $_SESSION['region'] = $user['region'];
        $_SESSION['role'] = strtolower($user['role']);
        header("Location: ../home/index.php");
        exit;
      }
    } else {
      $error = "Email หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <style>
    .login-box {
      background-color: #01182a;
      padding: 40px;
      border-radius: 8px;
      max-width: 400px;
      width: 100%;
      box-sizing: border-box;
    }

    .login-box h1 {
      text-align: center;
      margin-bottom: 20px;
    }
  </style>
<?php include '../utils/link.php'; ?>
    <link rel="stylesheet" href="../css/sign.css">
</head>

<body>

  <div class="overlay">
    <form class="login-box" method="POST" action="">
      <h1>Login</h1>

      <?php if (!empty($error)): ?>
        <div style="color: red; margin-bottom: 10px;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="input-group-custom">
        <label for="email">Email</label>
        <input type="text" id="email" name="email" required />
      </div>

      <div class="input-group-custom">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />
      </div>

      <div class="options">
        <label>
          <input type="checkbox" name="remember" />
          Remember me
        </label>
        <a href="forgot_password.php">Forgot password?</a>
      </div>

      <button class="btn-danger-custom" type="submit">Sign In</button>

      <div class="bottom-text">
        Don't have an account? <a href="signup.php">Register here.</a>
      </div>
    </form>
  </div>
</body>

</html>