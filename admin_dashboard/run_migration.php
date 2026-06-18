<?php
/**
 * รัน migration สำหรับ valorant_agents และ valorant_maps
 * เข้าหน้านี้ครั้งเดียวเมื่อติดตั้งระบบใหม่
 */
define('ACCESS', true);
include '../utils/db.php';
include 'admin_auth.php';

$sqlFile = dirname(__DIR__) . '/database/migrate_agents_maps.sql';
if (!file_exists($sqlFile)) {
    die('ไฟล์ migrate_agents_maps.sql ไม่พบ');
}

$sql = file_get_contents($sqlFile);
$sql = preg_replace('/--.*$/m', '', $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)));

$done = 0;
$errors = [];
foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    if ($conn->query($stmt)) {
        $done++;
    } else {
        $errors[] = $conn->error . ' | ' . substr($stmt, 0, 80) . '...';
    }
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Migration</title></head><body>";
echo "<h2>Migration เสร็จสิ้น</h2>";
echo "<p>รันคำสั่งสำเร็จ: $done รายการ</p>";
if (!empty($errors)) {
    echo "<h3>ข้อผิดพลาด:</h3><ul>";
    foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
    echo "</ul>";
}
echo "<p><a href='agent_table.php'>ไปหน้า Agents</a> | <a href='map_table.php'>ไปหน้า Maps</a></p>";
echo "</body></html>";
