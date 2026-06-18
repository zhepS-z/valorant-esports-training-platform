<?php
define('ACCESS', true);
require_once 'apikey.php';
header('Content-Type: application/json');

$name = $_GET['name'] ?? '';
$tag = $_GET['tag'] ?? '';
$region = $_GET['region'] ?? ''; // รับ region จาก JS

if (!$name || !$tag || !$region) {
    echo json_encode(['status' => 0, 'message' => 'Missing name, tag, or region']);
    exit;
}

$url = "https://api.henrikdev.xyz/valorant/v1/premier/" . urlencode($name) . "/" . urlencode($tag);
$options = [
    "http" => [
        "header" => "Authorization: $api_key\r\n"
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo json_encode(['status' => 0, 'message' => 'API error']);
    exit;
}

$json = json_decode($response, true);
if (!isset($json['status']) || $json['status'] != 200 || !isset($json['data'])) {
    echo json_encode(['status' => 0, 'message' => 'API returned error']);
    exit;
}

$data = $json['data'];

// ตรวจสอบสถานะของข้อมูลทีม
if (!isset($data['status']) || !in_array($data['status'], [1, 200]) || !isset($data['data'])) {
    echo '<div class="alert alert-danger">ไม่พบข้อมูลทีมนี้</div>';
    exit;
}

// ดึง match history
$history = [];
if (!empty($data['id'])) {
    $historyUrl = "https://api.henrikdev.xyz/valorant/v1/premier/" . urlencode($data['id']) . "/history";
    $historyResponse = @file_get_contents($historyUrl, false, $context);
    if ($historyResponse !== false) {
        $historyJson = json_decode($historyResponse, true);
        if (isset($historyJson['status']) && $historyJson['status'] == 200 && isset($historyJson['data']['league_matches'])) {
            $history = $historyJson['data']['league_matches'];
        }
    }
}

echo json_encode([
    'status' => 1,
    'data' => $data,
    'region' => $region,
    'history' => $history
]);