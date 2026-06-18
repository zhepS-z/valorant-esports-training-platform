<?php
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
require_once '../auth/auth_check.php';
include '../utils/db.php'; // ใช้ connection จาก db.php

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$show_form = false;
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $email && $token) {
    // ตรวจสอบ token
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND reset_token=? AND reset_token_expiry > NOW()");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $show_form = true;
    } else {
        $msg = "ลิงก์ไม่ถูกต้องหรือหมดอายุ";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($password !== $confirm) {
        $msg = "รหัสผ่านไม่ตรงกัน";
        $show_form = true;
    } else {
        // ตรวจสอบ token อีกครั้ง
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND reset_token=? AND reset_token_expiry > NOW()");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_token_expiry=NULL WHERE email=?");
            $stmt2->bind_param("ss", $password_hash, $email);
            $stmt2->execute();
            $stmt2->close();
            $msg = "รีเซ็ตรหัสผ่านสำเร็จ! <a href='login.php'>เข้าสู่ระบบ</a>";
        } else {
            $msg = "ลิงก์ไม่ถูกต้องหรือหมดอายุ";
        }
        $stmt->close();
    }
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>

    </style>
    <?php include 'link.php'; ?>
    <link rel="stylesheet" href="css/sign.css" />
</head>

<body>
    <br>
    <div class="container">
        <div class="row justify-content-center">
            <form class="login-box" method="POST" style="max-width:400px;width:100%;">
                <h1>Reset Password</h1>
                <?php if (!empty($msg)): ?>
                    <div style="color: <?= $show_form ? 'red' : 'green' ?>; margin-bottom: 10px;"><?= $msg ?></div>
                <?php endif; ?>
                <?php if ($show_form): ?>
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="input-group-custom">
                        <label for="password">New Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="input-group-custom">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button class="btn-danger-custom" type="submit">Reset Password</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>