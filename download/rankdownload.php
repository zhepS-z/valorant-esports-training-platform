<?php
// ตั้งค่าการแสดงข้อผิดพลาด
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ฟังก์ชันดาวน์โหลดด้วย cURL
function downloadFile($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Failed to download file. HTTP Code: {$httpCode}");
    }

    return $data;
}

// ฟังก์ชันหลักสำหรับดาวน์โหลดรูปภาพ Rank
function downloadAllRankImages() {
    try {
        // สร้างโฟลเดอร์หากไม่มี
        $ranksDir = __DIR__ . '/ranks';
        if (!file_exists($ranksDir)) {
            mkdir($ranksDir, 0755, true);
        }

        // ดาวน์โหลดข้อมูล Rank จาก API
        $ranksUrl = 'https://valorant-api.com/v1/competitivetiers';
        $ranksData = json_decode(downloadFile($ranksUrl), true);

        if (!isset($ranksData['data'][0]['tiers'])) {
            throw new Exception("Invalid API response format");
        }

        $downloadedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        echo "<h2>Downloading Rank Images</h2>";
        echo "<ul>";

        foreach ($ranksData['data'][0]['tiers'] as $tier) {
            if (empty($tier['largeIcon'])) {
                echo "<li>Skipped: {$tier['tierName']} (No image URL)</li>";
                $skippedCount++;
                continue;
            }

            // ตั้งชื่อไฟล์ตามชื่อแรงค์ เช่น immortal1.png
            $tierName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $tier['tierName']));
            $filename = "{$tierName}.png";
            $filepath = "{$ranksDir}/{$filename}";

            // ตรวจสอบว่ามีไฟล์อยู่แล้วหรือไม่
            if (file_exists($filepath)) {
                echo "<li>Skipped: {$tier['tierName']} (Already exists)</li>";
                $skippedCount++;
                continue;
            }

            try {
                // ดาวน์โหลดรูปภาพ
                $imageData = downloadFile($tier['largeIcon']);

                // บันทึกรูปภาพ
                file_put_contents($filepath, $imageData);

                // สร้างไฟล์ข้อมูลเพิ่มเติม
                $info = [
                    'tier_name' => $tier['tierName'],
                    'original_url' => $tier['largeIcon'],
                    'download_date' => date('Y-m-d H:i:s')
                ];
                file_put_contents("{$ranksDir}/{$tierName}.json", json_encode($info, JSON_PRETTY_PRINT));

                echo "<li>Downloaded: <strong>{$tier['tierName']}</strong> as {$filename}</li>";
                $downloadedCount++;
            } catch (Exception $e) {
                echo "<li style='color:red'>Failed: {$tier['tierName']} - {$e->getMessage()}</li>";
                $failedCount++;
            }
        }

        echo "</ul>";
        echo "<h3>Summary:</h3>";
        echo "<p>Downloaded: {$downloadedCount} | Skipped: {$skippedCount} | Failed: {$failedCount}</p>";

    } catch (Exception $e) {
        echo "<h3 style='color:red'>Error:</h3>";
        echo "<p>{$e->getMessage()}</p>";
    }
}

// เรียกใช้ฟังก์ชันหลัก
downloadAllRankImages();
?>