<?php
session_start();
define('ACCESS', true);
require_once '../utils/db.php'; // ใช้ connection จาก db.php

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    die("กรุณาเข้าสู่ระบบก่อนเพิ่มทีม Premier");
}

// ตรวจสอบบทบาทของผู้ใช้
$user_id = $_SESSION['user_id']; // ดึง user_id จากเซสชัน
$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'manager' && $role !== 'admin') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = trim($_POST['team_name']);
    $team_tag = trim($_POST['team_tag']);

    if (!empty($team_name) && !empty($team_tag)) {
        // เริ่มต้นการทำงานใน Transaction
        $conn->begin_transaction();

        try {
            // เพิ่มทีม Premier
            $stmt = $conn->prepare("INSERT INTO premier_teams (team_name, team_tag, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $team_name, $team_tag, $user_id);
            $stmt->execute();
            $premier_team_id = $stmt->insert_id; // ดึง ID ของทีม Premier ที่เพิ่มใหม่
            $stmt->close();

            // ดึง team_id ของผู้ใช้
            $stmt = $conn->prepare("SELECT team_id FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($team_id);
            $stmt->fetch();
            $stmt->close();

            if ($team_id) {
                // ดึงสมาชิกในทีมเดียวกัน
                $stmt = $conn->prepare("SELECT user_id, role_in_team FROM team_members WHERE team_id = ?");
                $stmt->bind_param("i", $team_id);
                $stmt->execute();
                $result = $stmt->get_result();

                // เพิ่มสมาชิกในทีม Premier
                $stmt_insert = $conn->prepare("INSERT INTO premier_team_members (premier_team_id, user_id, role_in_team) VALUES (?, ?, ?)");
                while ($row = $result->fetch_assoc()) {
                    $stmt_insert->bind_param("iis", $premier_team_id, $row['user_id'], $row['role_in_team']);
                    $stmt_insert->execute();
                }
                $stmt_insert->close();
                $stmt->close();
            }

            // ยืนยันการทำงาน
            $conn->commit();
            $success_message = "เพิ่มทีม Premier และสมาชิกในทีมเรียบร้อยแล้ว!";
        } catch (Exception $e) {
            // ยกเลิกการทำงานในกรณีเกิดข้อผิดพลาด
            $conn->rollback();
            $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    } else {
        $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มทีม Premier</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #01182a;
            color: #e9ecef;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #022d4f;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 0.5rem;
        }

        input[type="text"] {
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        button {
            padding: 10px;
            background-color: #04406e;
            color: #e9ecef;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background-color: #033659;
        }

        .message {
            margin-top: 1rem;
            text-align: center;
        }

        .success {
            color: #4CAF50;
        }

        .error {
            color: #F44336;
        }

        .warning {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #FFC107;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>เพิ่มทีม Premier</h1>
        <form method="POST" action="">
            <label for="team_name">ชื่อทีม:</label>
            <input type="text" id="team_name" name="team_name" placeholder="ป้อนชื่อทีม" required>

            <label for="team_tag">แท็กทีม:</label>
            <input type="text" id="team_tag" name="team_tag" placeholder="ป้อนแท็กทีม" required>

            <button type="submit">เพิ่มทีม</button>
        </form>

        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="warning">
            <p>หมายเหตุ: หากผู้เล่นในทีม Premier และ Riot ID ในเว็บไซต์ไม่ตรงกัน อาจทำให้ระบบผิดพลาดได้</p>
        </div>
    </div>
</body>

</html>