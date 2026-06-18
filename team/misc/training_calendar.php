<?php 
session_start(); // เริ่มต้นเซสชัน
define('ACCESS', true);
require_once '../../utils/apikey.php';  // โหลด API Key
require_once '../../auth/auth_check.php';
include '../../utils/db.php'; // ใช้ connection จาก db.php

// ตั้งค่าเดือนและปีปัจจุบัน
$currentMonth = date('n');
$currentYear = date('Y');

// ตรวจสอบว่ามีการส่งเดือนและปีมาหรือไม่
if (isset($_GET['month']) && isset($_GET['year'])) {
    $currentMonth = intval($_GET['month']);
    $currentYear = intval($_GET['year']);
}

// คำนวณเดือนและปีก่อนหน้าและถัดไป
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear = $currentYear - 1;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear = $currentYear + 1;
}

// ตั้งชื่อเดือน
$monthNames = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 
    4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน', 
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 
    10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

// คำนวณวันแรกของเดือนและจำนวนวันในเดือน
$firstDayOfMonth = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$numberOfDays = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('N', $firstDayOfMonth); // 1=จันทร์, 7=อาทิตย์

// วันนี้
$today = date('j');
$todayMonth = date('n');
$todayYear = date('Y');

// วันหยุด (ตัวอย่าง)
$holidays = [
    '1-1' => 'วันขึ้นปีใหม่',
    '13-4' => 'วันสงกรานต์',
    '14-4' => 'วันสงกรานต์',
    '15-4' => 'วันสงกรานต์',
    '5-5' => 'วันฉัตรมงคล',
    '28-7' => 'วันเฉลิมพระชนมพรรษา',
    '12-8' => 'วันแม่',
    '23-10' => 'วันปิยมหาราช',
    '5-12' => 'วันพ่อ',
    '10-12' => 'วันรัฐธรรมนูญ',
    '31-12' => 'วันสิ้นปี'
];

// ดึงข้อมูลการจอง scrim จากฐานข้อมูล
$scrimEvents = [];
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    // ตรวจสอบว่าผู้ใช้เป็นสมาชิกทีมใดบ้าง
    $teamQuery = "SELECT team_id FROM team_members WHERE user_id = ?";
    $teamStmt = $conn->prepare($teamQuery);
    $teamStmt->bind_param("i", $userId);
    $teamStmt->execute();
    $teamResult = $teamStmt->get_result();
    
    $userTeams = [];
    while ($teamRow = $teamResult->fetch_assoc()) {
        $userTeams[] = $teamRow['team_id'];
    }
    $teamStmt->close();
    
    // หากผู้ใช้มีทีม ให้ดึงข้อมูลการจอง scrim
    if (!empty($userTeams)) {
        $placeholders = str_repeat('?,', count($userTeams) - 1) . '?';
        
        // ดึงข้อมูล scrim ที่เกี่ยวข้องกับทีมของผู้ใช้
        $scrimQuery = "
            SELECT s.scrim_id, s.scrim_start, t.team_name, srn.status
            FROM scrims s
            JOIN teams t ON s.team_id = t.team_id
            LEFT JOIN scrim_reservation_notifications srn ON s.scrim_id = srn.scrim_id
            WHERE (s.team_id IN ($placeholders) OR srn.manager_id = ?)
            ORDER BY s.scrim_start
        ";
        
        $scrimStmt = $conn->prepare($scrimQuery);
        
        // สร้าง array ของ parameters
        $params = array_merge($userTeams, [$userId]);
        $types = str_repeat('i', count($params));
        
        $scrimStmt->bind_param($types, ...$params);
        $scrimStmt->execute();
        $scrimResult = $scrimStmt->get_result();
        
        while ($scrimRow = $scrimResult->fetch_assoc()) {
            $scrimDate = date('j-n-Y', strtotime($scrimRow['scrim_start']));
            $scrimEvents[$scrimDate][] = [
                'team_name' => $scrimRow['team_name'],
                'status' => $scrimRow['status'] ?? 'pending'
            ];
        }
        $scrimStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปฏิทิน</title>
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #eef2ff;
            --secondary-color: #3f37c9;
            --text-color: #333;
            --light-text: #6c757d;
            --border-color: #e9ecef;
            --today-bg: #ffefef;
            --today-color: #e63946;
            --holiday-bg: #fff3cd;
            --holiday-color: #856404;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color)!important;
            line-height: 1.6;
        }

        .calendar-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .calendar-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .month-year {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }

        .today-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .today-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background-color: var(--primary-light);
            font-weight: 600;
            text-align: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: var(--border-color);
        }

        .calendar-day {
            background: white;
            min-height: 100px;
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            transition: all 0.2s ease;
            position: relative;
        }

        .calendar-day:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .day-number {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .other-month {
            color: var(--light-text);
            background-color: #f8f9fa;
        }

        .today {
            background-color: var(--today-bg);
            border: 2px solid var(--today-color);
        }

        .today .day-number {
            color: var(--today-color);
            font-weight: 700;
        }

        .holiday {
            background-color: var(--holiday-bg);
        }

        .holiday-name {
            font-size: 0.7rem;
            color: var(--holiday-color);
            margin-top: auto;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .event-dot {
            width: 6px;
            height: 6px;
            background-color: var(--primary-color);
            border-radius: 50%;
            margin: 2px 0;
        }

        .event-container {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .event-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.7rem;
            white-space: nowrap;
            z-index: 10;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s;
        }

        .calendar-day:hover .event-tooltip {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 768px) {
            .calendar-day {
                min-height: 80px;
                padding: 0.25rem;
            }
            
            .month-year {
                font-size: 1.5rem;
            }
            
            .holiday-name {
                font-size: 0.6rem;
            }
        }

        @media (max-width: 576px) {
            .calendar-day {
                min-height: 60px;
            }
            
            .day-number {
                font-size: 0.9rem;
            }
            
            .month-year {
                font-size: 1.3rem;
            }
        }
    </style>
    <?php include '../../utils/link.php'; ?>
</head>

<body>
    <br>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <div class="calendar-nav">
                            <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="nav-btn">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <h1 class="month-year"><?php echo $monthNames[$currentMonth] . ' ' . $currentYear; ?></h1>
                            <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="nav-btn">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <a href="?month=<?php echo $todayMonth; ?>&year=<?php echo $todayYear; ?>" class="today-btn">
                            <i class="fas fa-calendar-day"></i> ไปยังวันนี้
                        </a>
                    </div>
                    
                    <div class="calendar-weekdays">
                        <div>จันทร์</div>
                        <div>อังคาร</div>
                        <div>พุธ</div>
                        <div>พฤหัสบดี</div>
                        <div>ศุกร์</div>
                        <div>เสาร์</div>
                        <div>อาทิตย์</div>
                    </div>
                    
                    <div class="calendar-days">
                        <?php
                        // เติมวันว่างก่อนวันแรกของเดือน
                        for ($i = 1; $i < $firstDayOfWeek; $i++) {
                            echo '<div class="calendar-day other-month"></div>';
                        }
                        
                        // เติมวันในเดือน
                        for ($day = 1; $day <= $numberOfDays; $day++) {
                            $isToday = ($day == $today && $currentMonth == $todayMonth && $currentYear == $todayYear);
                            $dayClass = $isToday ? 'calendar-day today' : 'calendar-day';
                            
                            // ตรวจสอบวันหยุด
                            $holidayKey = $day . '-' . $currentMonth;
                            $isHoliday = isset($holidays[$holidayKey]);
                            if ($isHoliday) {
                                $dayClass .= ' holiday';
                            }
                            
                            echo '<div class="' . $dayClass . '">';
                            echo '<div class="day-number">' . $day . '</div>';
                            
                            // แสดงชื่อวันหยุด
                            if ($isHoliday) {
                                echo '<div class="holiday-name">' . $holidays[$holidayKey] . '</div>';
                            }
                            
                            // ตรวจสอบและแสดงจุดสีฟ้าสำหรับการจอง scrim
                            $currentDateKey = $day . '-' . $currentMonth . '-' . $currentYear;
                            if (isset($scrimEvents[$currentDateKey])) {
                                echo '<div class="event-container">';
                                foreach ($scrimEvents[$currentDateKey] as $event) {
                                    echo '<div class="event-dot" title="Scrim: ' . htmlspecialchars($event['team_name']) . ' (สถานะ: ' . $event['status'] . ')"></div>';
                                }
                                echo '</div>';
                                
                                // แสดง tooltip เมื่อ hover
                                echo '<div class="event-tooltip">';
                                foreach ($scrimEvents[$currentDateKey] as $event) {
                                    echo 'Scrim: ' . htmlspecialchars($event['team_name']) . ' (' . $event['status'] . ')<br>';
                                }
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        
                        // เติมวันว่างหลังจากวันสุดท้ายของเดือน
                        $lastDayOfWeek = date('N', mktime(0, 0, 0, $currentMonth, $numberOfDays, $currentYear));
                        if ($lastDayOfWeek < 7) {
                            for ($i = $lastDayOfWeek; $i < 7; $i++) {
                                echo '<div class="calendar-day other-month"></div>';
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-light rounded">
                    <h5>คำอธิบายสัญลักษณ์</h5>
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-primary rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                        <small>มี Scrim จอง</small>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="border rounded me-2" style="width: 12px; height: 12px; background-color: var(--today-bg); border-color: var(--today-color) !important;"></div>
                        <small>วันนี้</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="border rounded me-2" style="width: 12px; height: 12px; background-color: var(--holiday-bg);"></div>
                        <small>วันหยุด</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // เพิ่มเอฟเฟกต์เมื่อคลิกที่วันในปฏิทิน
        document.addEventListener('DOMContentLoaded', function() {
            const days = document.querySelectorAll('.calendar-day:not(.other-month)');
            
            days.forEach(day => {
                day.addEventListener('click', function() {
                    // ลบคลาส active จากวันที่อื่น
                    days.forEach(d => d.classList.remove('active'));
                    
                    // เพิ่มคลาส active ให้กับวันที่ที่คลิก
                    this.classList.add('active');
                    
                    // ตัวอย่าง: แสดงข้อมูลวันที่ที่เลือก
                    const dayNumber = this.querySelector('.day-number').textContent;
                    console.log('เลือกวันที่:', dayNumber);
                });
            });
        });
    </script>
</body>

</html>