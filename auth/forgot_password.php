<?php
date_default_timezone_set('Asia/Bangkok');
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
require_once '../auth/auth_check.php';
include '../utils/db.php'; // ใช้ connection จาก db.php

// เพิ่ม PHPMailer
require 'PHPMailer-6.10.0/PHPMailer-6.10.0/src/PHPMailer.php';
require 'PHPMailer-6.10.0/PHPMailer-6.10.0/src/SMTP.php';
require 'PHPMailer-6.10.0/PHPMailer-6.10.0/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    // ตรวจสอบว่ามี email ในระบบ
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 ชั่วโมง
        $stmt2 = $conn->prepare("UPDATE users SET reset_token=?, reset_token_expiry=? WHERE email=?");
        $stmt2->bind_param("sss", $token, $expiry, $email);
        $stmt2->execute();
        $stmt2->close();

        $reset_link = "http://localhost/VALPROJECT/reset_password.php?email=" . urlencode($email) . "&token=" . $token;
        $subject = "Password Reset";
        $message = "คลิกลิงก์นี้เพื่อรีเซ็ตรหัสผ่านของคุณ: $reset_link";

        // ส่งอีเมลด้วย PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // หรือ SMTP ของคุณ
            $mail->SMTPAuth = true;
            $mail->Username = 'settapongvest@gmail.com'; // อีเมลผู้ส่ง
            $mail->Password = 'jahekjkdbujmrnnh';    // App Password หรือรหัสผ่าน SMTP
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('settapongvest@gmail.com', 'Your App');
            $mail->addAddress($email);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
        } catch (Exception $e) {
            // คุณสามารถแสดง error ได้ที่นี่ถ้าต้องการ
        }
    }
    $msg = "หากอีเมลนี้มีอยู่ในระบบ เราจะส่งลิงก์สำหรับรีเซ็ตรหัสผ่านไปให้";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/sign.css" />
    <style>
      
        

    </style>
    <?php include 'link.php'; ?>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="overlay">
                <form class="login-box" method="POST">
                    <h1>Forgot Password</h1>
                    <?php if (!empty($msg)): ?>
                        <div style="color: green; margin-bottom: 10px;"><?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>

                    <?php if (empty($msg)): ?>
                        <div class="input-group-custom">
                            <label for="email">Enter your email</label>
                            <input type="email" id="email" name="email" required />
                        </div>
                        <button class="btn-danger-custom" type="submit">Send Reset Link</button>
                    <?php endif; ?>

                    <div class="bottom-text" style="margin-top:10px;">
                        <a href="login.php">Back to Login</a>
                    </div>
                </form>
            </div>

        </div>
    </div>

</body>

</html>