<?php
// ตั้งค่าการแสดงข้อผิดพลาด
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ฟังก์ชันดาวน์โหลดด้วย cURL (รองรับเซิร์ฟเวอร์ที่ปิดใช้งาน file_get_contents)
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

// ฟังก์ชันหลักสำหรับดาวน์โหลดรูปภาพ Agent โดยใช้ UUID เป็นชื่อไฟล์
function downloadAllAgentImages() {
    try {
        // สร้างโฟลเดอร์หากไม่มี
        $agentsDir = __DIR__ . '/agents';
        if (!file_exists($agentsDir)) {
            mkdir($agentsDir, 0755, true);
        }

        // ดาวน์โหลดข้อมูล Agent จาก API
        $agentsUrl = 'https://valorant-api.com/v1/agents?isPlayableCharacter=true';
        $agentsData = json_decode(downloadFile($agentsUrl), true);
        
        if (!isset($agentsData['data'])) {
            throw new Exception("Invalid API response format");
        }

        $downloadedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        
        echo "<h2>Downloading Agent Images</h2>";
        echo "<ul>";

        foreach ($agentsData['data'] as $agent) {
            if (empty($agent['displayIcon'])) {
                echo "<li>Skipped: {$agent['displayName']} (No image URL)</li>";
                $skippedCount++;
                continue;
            }

            $uuid = $agent['uuid'];
            $filename = "{$uuid}.png";
            $filepath = "{$agentsDir}/{$filename}";

            // ตรวจสอบว่ามีไฟล์อยู่แล้วหรือไม่
            if (file_exists($filepath)) {
                echo "<li>Skipped: {$agent['displayName']} (Already exists)</li>";
                $skippedCount++;
                continue;
            }

            try {
                // ดาวน์โหลดรูปภาพ
                $imageData = downloadFile($agent['displayIcon']);
                
                // บันทึกรูปภาพ
                file_put_contents($filepath, $imageData);
                
                // สร้างไฟล์ข้อมูลเพิ่มเติม
                $info = [
                    'display_name' => $agent['displayName'],
                    'original_url' => $agent['displayIcon'],
                    'download_date' => date('Y-m-d H:i:s')
                ];
                file_put_contents("{$agentsDir}/{$uuid}.json", json_encode($info, JSON_PRETTY_PRINT));
                
                echo "<li>Downloaded: <strong>{$agent['displayName']}</strong> as {$filename}</li>";
                $downloadedCount++;
            } catch (Exception $e) {
                echo "<li style='color:red'>Failed: {$agent['displayName']} - {$e->getMessage()}</li>";
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
downloadAllAgentImages();
?>