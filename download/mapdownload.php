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

// ฟังก์ชันแปลงชื่อไฟล์ให้ปลอดภัย
function sanitizeFilename($name) {
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9-]/', '-', $name);
    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');
    return $name;
}

// ฟังก์ชันหลักสำหรับดาวน์โหลดรูปภาพแผนที่
function downloadValorantMaps() {
    try {
        // สร้างโฟลเดอร์หากไม่มี
        $mapsDir = __DIR__ . '/valorant_maps';
        if (!file_exists($mapsDir)) {
            mkdir($mapsDir, 0755, true);
        }

        // ดาวน์โหลดข้อมูลแผนที่จาก API
        $mapsUrl = 'https://valorant-api.com/v1/maps';
        $mapsData = json_decode(downloadFile($mapsUrl), true);
        
        if (!isset($mapsData['data'])) {
            throw new Exception("Invalid API response format");
        }

        $downloadedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        
        echo "<h2>Downloading Valorant Map Images</h2>";
        echo "<ul>";

        foreach ($mapsData['data'] as $map) {
            if (empty($map['displayIcon']) || empty($map['displayName'])) {
                echo "<li>Skipped: Map with UUID {$map['uuid']} (Missing data)</li>";
                $skippedCount++;
                continue;
            }

            $originalName = $map['displayName'];
            $safeName = sanitizeFilename($originalName);
            $filename = "{$safeName}.png";
            $filepath = "{$mapsDir}/{$filename}";

            // ตรวจสอบว่ามีไฟล์อยู่แล้วหรือไม่
            if (file_exists($filepath)) {
                echo "<li>Skipped: {$originalName} (Already exists)</li>";
                $skippedCount++;
                continue;
            }

            try {
                // ดาวน์โหลดรูปภาพ
                $imageData = downloadFile($map['displayIcon']);
                
                // บันทึกรูปภาพ
                file_put_contents($filepath, $imageData);
                
                // สร้างไฟล์ข้อมูลเพิ่มเติม
                $info = [
                    'original_name' => $originalName,
                    'uuid' => $map['uuid'],
                    'map_url' => $map['mapUrl'],
                    'coordinates' => $map['coordinates'],
                    'download_date' => date('Y-m-d H:i:s')
                ];
                file_put_contents("{$mapsDir}/{$safeName}.json", json_encode($info, JSON_PRETTY_PRINT));
                
                echo "<li>Downloaded: <strong>{$originalName}</strong> as {$filename}</li>";
                $downloadedCount++;
            } catch (Exception $e) {
                echo "<li style='color:red'>Failed: {$originalName} - {$e->getMessage()}</li>";
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
downloadValorantMaps();
?>