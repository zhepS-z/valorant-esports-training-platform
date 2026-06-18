<?php 
session_start(); // เริ่มต้นเซสชัน
define('ACCESS', true);
require_once '../../utils/apikey.php';  // โหลด API Key
require_once '../../auth/auth_check.php';
include '../../utils/db.php'; // ใช้ connection จาก db.php

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// ดึงข้อมูล team_id ของผู้ใช้จากฐานข้อมูล
$user_id = $_SESSION['user_id'];
$query = "SELECT team_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_team_id = (int)($user['team_id'] ?? 0);
$stmt->close();

// ตรวจสอบว่า user เป็น manager โดยตรวจสอบ manager_id จากตาราง teams
$is_team_manager = false;
$user_role = 'player';
if (!empty($user_team_id)) {
    $team_query = "SELECT manager_id FROM teams WHERE team_id = ?";
    $team_stmt = $conn->prepare($team_query);
    $team_stmt->bind_param("i", $user_team_id);
    $team_stmt->execute();
    $team_result = $team_stmt->get_result();
    if ($team_result && $team_result->num_rows > 0) {
        $team_row = $team_result->fetch_assoc();
        $manager_id = (int)($team_row['manager_id'] ?? 0);
        $is_team_manager = ($manager_id === $user_id);
        if ($is_team_manager) {
            $user_role = 'manager';
        }
    }
    $team_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Team</title>
    <style>
    /* ====== Modern team page styles ====== */

    /* Navbar */


    /* Hero */
    .hero-section {
        padding: 4.5rem 0;
        background: linear-gradient(120deg, rgba(2, 45, 79, 0.14), rgba(3, 18, 28, 0.6)), url('/VALPROJECT/img/hero-bg.jpg');
        background-size: cover;
        background-position: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
    }

    .hero-section .display-4 {
        color: #fff;
        text-shadow: 0 6px 18px rgba(0, 0, 0, 0.6);
    }

    .hero-section .lead {
        color: rgba(255, 255, 255, 0.82);
    }

    /* Dashboard cards */
    .dashboard-card {
        background: linear-gradient(180deg, #01182a, #021b25);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 16px;
        padding: 1.75rem;
        transition: transform .12s ease, box-shadow .12s ease;
        color: #e9f6ff;
    }

    .dashboard-card .card-icon {
        font-size: 28px;
        color: #fff;
        background: linear-gradient(90deg, #022d4f, #2b6f8f);
        width: 64px;
        height: 64px;
        display: inline-flex;
        /* เปลี่ยนเป็น flex บรรทัดเดียว */
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(2, 45, 79, 0.18);
        margin: 0 auto 12px;
        /* ให้กล่องไอคอนอยู่กึ่งกลางแนวนอน */
    }

    .dashboard-card h3 {
        margin-top: 8px;
        font-weight: 700;
        color: #fff;
    }

    .dashboard-card p {
        color: rgba(255, 255, 255, 0.72);
    }

    .dashboard-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 48px rgba(2, 45, 79, 0.22);
    }

    /* Valorant-themed buttons */
    .btn-valorant {
        background: linear-gradient(135deg, #04406e, #0a7396);
        border: none;
        color: #fff;
        border-radius: 12px;
        padding: .55rem 1rem;
        font-weight: 700;
        box-shadow: 0 8px 20px rgba(2, 45, 79, 0.18);
    }

    .btn-valorant:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 40px rgba(2, 45, 79, 0.28);
    }

    /* Stats card */
    .stats-card {
        background: linear-gradient(180deg, #011216, rgba(2, 45, 79, 0.02));
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.03);
    }

    .stats-card h4 {
        color: #fff;
        font-weight: 700;
    }

    .stats-card p {
        color: rgba(255, 255, 255, 0.7);
    }

    /* Tables */
    .table thead.table-dark th {
        background: linear-gradient(180deg, #021419, #02202a);
        color: #fff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .table tbody tr {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.01), rgba(0, 0, 0, 0.02));
        transition: transform .12s ease, box-shadow .12s ease;
    }

    .table tbody tr:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.45);
    }

    .table td,
    .table th {
        vertical-align: middle;
        color: rgba(255, 255, 255, 0.88);
    }

    /* Footer */
    footer {
        background: linear-gradient(180deg, #010608, #02121a);
        color: rgba(255, 255, 255, 0.8);
        border-top: 1px solid rgba(255, 255, 255, 0.03);
    }

    footer h5 {
        color: #fff;
        font-weight: 700;
    }

    /* ปรับให้ไอคอนใน footer หรือ list จัดแนวกลางด้วย */
    footer ul li i {
        width: 20px;
        display: inline-block;
        text-align: center;
        vertical-align: middle;
        margin-right: 8px;
    }

    /* Responsive tweaks */
    @media (max-width: 767.98px) {
        .hero-section {
            padding: 2.5rem 0;
            text-align: center;
        }

        .dashboard-card {
            padding: 1.25rem;
        }

        .dashboard-card .card-icon {
            width: 56px;
            height: 56px;
            font-size: 22px;
        }
    }

    /* Accessibility: focus outlines */
    a:focus,
    button:focus {
        outline: 3px solid rgba(2, 45, 79, 0.22);
        outline-offset: 2px;
    }
    </style>
    <?php include '../../utils/link.php'; ?>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>


    <br>

    <!-- Main Content -->
    <div class="container">
        <!-- Dashboard Cards -->
        <br>
        <h2 class="mb-4 text-center">ระบบจัดการทีม</h2>
        <div class="row g-4 mb-5">
            <?php if ($user_role === 'manager' || $user_role === 'admin'): ?>
            <!-- Team Management Card -->
            <div class="col-md-6">
                <div class="dashboard-card card text-center p-4">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Team Management</h3>
                    <p>จัดการข้อมูลทีม, สมาชิก, และการตั้งค่าต่างๆ สำหรับทีมของคุณ</p>
                    <a href="team_manage.php" class="btn btn-valorant mt-3">จัดการทีม</a>
                </div>
            </div>

            <!-- Team Request Card -->
            <div class="col-md-6">
                <div class="dashboard-card card text-center p-4">
                    <div class="card-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Team Request</h3>
                    <p>จัดการคำขอเข้าร่วมทีม, การเชิญสมาชิก, และการตอบรับคำร้องขอ</p>
                    <a href="../api/team_request.php" class="btn btn-valorant mt-3">จัดการคำขอ</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Team Analysis Card -->
            <div class="col-md-6">
                <div class="dashboard-card card text-center p-4">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Team Analysis</h3>
                    <p>วิเคราะห์สถิติการเล่น, พัฒนาศักยภาพทีม และวางกลยุทธ์การแข่งขัน</p>
                    <a href="../../team_analytics/analyse.php" class="btn btn-valorant mt-3">ดูการวิเคราะห์</a>
                </div>
            </div>

            <!-- Training Calendar Card -->
            <div class="col-md-6">
                <div class="dashboard-card card text-center p-4">
                    <div class="card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Training Calendar</h3>
                    <p>ดูและจัดการปฏิทินการซ้อมของทีม เพื่อเตรียมความพร้อมสำหรับการแข่งขัน</p>
                    <a href="../misc/training_calendar.php" class="btn btn-valorant mt-3">ดูปฏิทิน</a>
                </div>
            </div>
        </div>
    </div>

    

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>