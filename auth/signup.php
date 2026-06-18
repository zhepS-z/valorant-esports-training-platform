<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
date_default_timezone_set('Asia/Bangkok');
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
include '../utils/db.php'; // ใช้ connection จาก db.php

$error_messages = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = trim($_POST['email']);
    $riot_id = trim($_POST['riotid']);
    $region = trim($_POST['region']);
    $role = 'player';

    // Check for duplicate email or riot_id in Users table
    $stmt = $conn->prepare("SELECT 1 FROM Users WHERE email=? OR riot_id=? LIMIT 1");
    $stmt->bind_param("ss", $email, $riot_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error_messages[] = "Email or Riot ID is already registered.";
    }
    $stmt->close();

    // Check for duplicate email or riot_id in pending_users table
    $stmt = $conn->prepare("SELECT 1 FROM pending_users WHERE email=? OR riot_id=? LIMIT 1");
    $stmt->bind_param("ss", $email, $riot_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error_messages[] = "Email or Riot ID is already pending verification.";
    }
    $stmt->close();

    // Validation
    if (
        empty($email) || empty($riot_id) || empty($password) || empty($confirm_password) ||
        empty($first_name) || empty($last_name)
    ) {
        $error_messages[] = "All fields are required.";
    }
    if (empty($region)) {
        $error_messages[] = "Please select a region.";
    }
    if (strlen($password) < 8) {
        $error_messages[] = "Password must be at least 8 characters long.";
    }
    if ($password !== $confirm_password) {
        $error_messages[] = "Passwords do not match.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_messages[] = "Invalid email format.";
    }
    if (!preg_match('/^[A-Za-z0-9]+$/', $password)) {
        $error_messages[] = "Password must contain only English letters and numbers.";
    }

    if (empty($error_messages)) {
        // บันทึกข้อมูลผู้ใช้โดยตรง (OTP ถูกตัดออก) และถือว่าเป็น verified
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO Users (first_name, last_name, password, email, riot_id, region, role, otp_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("sssssss", $first_name, $last_name, $hashed_password, $email, $riot_id, $region, $role);

        if ($stmt->execute()) {
            header("Location: ../auth/login.php");
            exit;
        } else {
            $error_messages[] = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <style>
    .register-box {
        background-color: #01182a;
        padding: 40px;
        border-radius: 8px;
        max-width: 500px;
        width: 100%;
        box-sizing: border-box;
    }

    .register-box h1 {
        text-align: center;
        margin-bottom: 20px;
    }

    .error-message {
        color: #ff6b6b;
        margin-bottom: 15px;
        padding: 10px;
        background-color: rgba(255, 107, 107, 0.1);
        border-radius: 4px;
    }

    .error-message ul {
        margin: 0;
        padding-left: 20px;
    }

    .input-group-custom {
        margin-bottom: 15px;
    }

    .input-group-custom label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .input-group-custom input {
        width: 100%;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #ccc;
        background-color: #fff;
        color: #333;
        box-sizing: border-box;
    }

    .btn-danger-custom {
        width: 100%;
        padding: 12px;
        margin-top: 10px;
    }

    .bottom-text {
        text-align: center;
        margin-top: 20px;
    }
    </style>
<?php include '../utils/link.php'; ?>
    <link rel="stylesheet" href="../css/sign.css">
</head>

<body>

    <!-- เอา overlay ไปครอบเฉพาะฟอร์ม ไม่ครอบ navbar -->
    <div class="overlay">
        <form class="register-box" method="POST">
            <h1>Register</h1>

            <?php if (!empty($error_messages)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($error_messages as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($success_message) echo $success_message; ?>

            <div class="input-group-custom">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required
                    value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
            </div>

            <div class="input-group-custom">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required
                    value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
            </div>

            <div class="input-group-custom">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="input-group-custom">
                <label for="riotid">Riot ID</label>
                <input type="text" id="riotid" name="riotid" required
                    value="<?php echo isset($_POST['riotid']) ? htmlspecialchars($_POST['riotid']) : ''; ?>">
            </div>

            <div class="input-group-custom">
                <label for="region">Region</label>
                <select name="region" id="region" class="form-select" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc; background-color: #fff; color: #333; box-sizing: border-box;">
                    <option value="">Select a region</option>
                    <option value="na" <?php echo (isset($_POST['region']) && $_POST['region'] === 'na') ? 'selected' : ''; ?>>North America (NA)</option>
                    <option value="eu" <?php echo (isset($_POST['region']) && $_POST['region'] === 'eu') ? 'selected' : ''; ?>>Europe (EU)</option>
                    <option value="ap" <?php echo (isset($_POST['region']) && $_POST['region'] === 'ap') ? 'selected' : ''; ?>>Asia Pacific (AP)</option>
                    <option value="kr" <?php echo (isset($_POST['region']) && $_POST['region'] === 'kr') ? 'selected' : ''; ?>>Korea (KR)</option>
                    <option value="latam" <?php echo (isset($_POST['region']) && $_POST['region'] === 'latam') ? 'selected' : ''; ?>>Latin America (LATAM)</option>
                    <option value="br" <?php echo (isset($_POST['region']) && $_POST['region'] === 'br') ? 'selected' : ''; ?>>Brazil (BR)</option>
                </select>
            </div>

            <div class="input-group-custom">
                <label for="password">Password (min 8 characters, only English letters and numbers)</label>
                <input type="password" id="password" name="password" required minlength="8"
                    pattern="[A-Za-z0-9]+"
                    title="Password must contain only English letters and numbers.">
            </div>

            <div class="input-group-custom">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                    pattern="[A-Za-z0-9]+"
                    title="Password must contain only English letters and numbers.">
            </div>

            <button class="btn-danger-custom" type="submit">Sign Up</button>

            <div class="bottom-text">
                Already have an account? <a href="login.php">Log in here.</a>
            </div>
        </form>
    </div>
</body>

</html>
