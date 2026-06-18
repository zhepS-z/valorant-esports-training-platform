<?php
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
header('Content-Type: application/json');

$matchid = $_GET['matchid'] ?? '';

if (!$matchid) {
    echo json_encode(['status' => 0, 'message' => 'Missing matchid']);
    exit;
}

$url = "https://api.henrikdev.xyz/valorant/v2/match/" . urlencode($matchid);
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
if ($json === null) {
    echo json_encode(['status' => 0, 'message' => 'Invalid JSON response']);
    exit;
}

// Check if we have the map data
if (!isset($json['data']['metadata']['map'])) {
    echo json_encode(['status' => 0, 'message' => 'No map data available']);
    exit;
}

// Return simplified response with just the map data if needed
$result = [
    'status' => 1,
    'map' => $json['data']['metadata']['map'],
    'full_data' => $json // Include full data in case it's needed
];

echo json_encode($result);
?>