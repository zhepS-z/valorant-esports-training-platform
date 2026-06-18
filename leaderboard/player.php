<?php
session_start();
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
require_once '../utils/agent.php'; // 

function call_api($url, $api_key) {
    $options = [
        "http" => [
            "header" => "Authorization: $api_key\r\n" .
                        "Accept: */*\r\n"
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === FALSE) {
        return json_encode(['status' => 404, 'message' => 'Unable to fetch data from the API.']);
    }

    return $response;
}

function calculate_kda_ratio($kills, $deaths, $assists) {
    return $deaths > 0 ? round(($kills + $assists) / $deaths, 2) : $kills + $assists;
}

function get_match_result($player_team, $match, $red_score, $blue_score) {
    $result = 'Deathmatch';
    $result_class = 'Defeat';
    $score = "$blue_score - $red_score";

    if ($player_team === 'Red') {
        $has_won = $match['teams']['red']['has_won'] ?? false;
        $result = $has_won ? 'Victory' : 'Defeat';
        $result_class = $has_won ? 'Victory' : 'Defeat';
        $score = "$red_score - $blue_score";
    } elseif ($player_team === 'Blue') {
        $has_won = $match['teams']['blue']['has_won'] ?? false;
        $result = $has_won ? 'Victory' : 'Defeat';
        $result_class = $has_won ? 'Victory' : 'Defeat';
        $score = "$blue_score - $red_score";
    }

    return [$result, $result_class, $score];
}

// รับ riot_id จาก URL
$full_id = isset($_GET['riot_id']) ? $_GET['riot_id'] : null;

// รับ region จาก URL 
$region = isset($_GET['region']) ? strtolower(trim($_GET['region'])) : 'ap';
$api_key = 'XXXXX'; // Replace with your actual API key

if (!$full_id || strpos($full_id, '#') === false) {
    $data = null;
    $match_data = null;
} else {
    $parts = explode('#', $full_id);
    $name = urlencode(trim($parts[0]));
    $tag = urlencode(trim($parts[1]));

    $name = str_replace('+', '%20', $name);
    $tag = str_replace('+', '%20', $tag);
    $tag = str_replace('#', '%23', $tag);

    // ดึงข้อมูล rank
    $url_rank = "https://api.henrikdev.xyz/valorant/v1/mmr/$region/$name/$tag";
    $response_rank = call_api($url_rank, $api_key);
    $data = json_decode($response_rank, true);

    if (isset($data['status']) && $data['status'] != 200) {
        $data = null;
    }

    // ดึง match history
    $url_matches = "https://api.henrikdev.xyz/valorant/v1/stored-matches/$region/$name/$tag";
    $response_matches = call_api($url_matches, $api_key);
    $all_matches = json_decode($response_matches, true);

    if (isset($all_matches['status']) && $all_matches['status'] == 200) {
        $filtered_matches = $all_matches['data'];
        $match_data = [
            'status' => 200,
            'data' => $filtered_matches,
            'total_available' => count($filtered_matches)
        ];
    } else {
        $match_data = null;
    }
}

// Pagination settings
$matches_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_pages = isset($match_data['total_available']) ? ceil($match_data['total_available'] / $matches_per_page) : 1;

if ($match_data && isset($match_data['data'])) {
    // กรองตาม mode ถ้ามีการเลือก
    $mode = isset($_GET['mode']) ? $_GET['mode'] : '';
    $filtered = $match_data['data'];

    if ($mode) {
        $filtered = array_filter($filtered, function($match) use ($mode) {
            return isset($match['meta']['mode']) && 
                   strtolower($match['meta']['mode']) === strtolower($mode);
        });
        // รีเซ็ต index array
        $filtered = array_values($filtered);
    }

    // จำกัดจำนวน match สูงสุด 300
    $paginated_matches = array_slice($filtered, 0, 300);
    // อัปเดตจำนวน match ที่มีให้แสดงผล
    $match_data['total_available'] = count($filtered);
} else {
    $paginated_matches = [];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($full_id) . ' Player Info' ?>
    </title>
    <link href="../css/player.css" rel="stylesheet">
    <?php include '../utils/link.php'; ?>
</head>

<body>
    <div class="container">
        <br>

        <?php if ($data && isset($data['status']) && $data['status'] == 200): 
            $rank = $data['data']['currenttierpatched'];
            $elo = $data['data']['elo'];
            $image = $data['data']['images']['small'];
        ?>
        <div class="rank-card text-center">
            <h2 class="mb-3"><?= htmlspecialchars($full_id) ?> [<?= strtoupper($region) ?>]</h2>
            <img src="<?= $image ?>" alt="Rank" class="mb-3" style="width: 100px;">
            <h3>Rank: <?= $rank ?></h3>
            <p>MMR (ELO): <strong><?= $elo ?></strong></p>
        </div>
        <?php else: ?>
        <div class="alert alert-danger">
            ไม่พบข้อมูลผู้เล่น กรุณาตรวจสอบ Riot ID และ Region อีกครั้ง
        </div>
        <?php endif; ?>

        <?php if ($match_data && isset($match_data['status']) && $match_data['status'] == 200): ?>
        <div class="mt-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Match History (Showing <?= count($paginated_matches) ?> of
                    <?= min(300, $match_data['total_available']) ?> matches)</h4>
                    
                <form id="filter-form" method="GET" class="d-flex">
                    <!-- ใช้ค่าจาก URL parameters โดยตรง -->
                    <input type="hidden" name="riot_id" value="<?= htmlspecialchars($full_id) ?>">
                    <input type="hidden" name="region" value="<?= htmlspecialchars($region) ?>">
                    <select name="mode" id="mode-filter" class="form-select me-2">
                        <option value="">All Modes</option>
                        <option value="competitive" <?= isset($_GET['mode']) && $_GET['mode'] === 'competitive' ? 'selected' : '' ?>>Competitive</option>
                        <option value="unrated" <?= isset($_GET['mode']) && $_GET['mode'] === 'unrated' ? 'selected' : '' ?>>Unrated</option>
                        <option value="deathmatch" <?= isset($_GET['mode']) && $_GET['mode'] === 'deathmatch' ? 'selected' : '' ?>>Deathmatch</option>
                        <option value="premier" <?= isset($_GET['mode']) && $_GET['mode'] === 'premier' ? 'selected' : '' ?>>Premier</option>
                    </select>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i></button>
                    <button type="button" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i></button>


                </form>

            </div>

            <!-- list-group -->
            <?php 
                // $match_region จะถูกใช้งานภายใน listgroup.php เพื่อแนบพารามิเตอร์ region ให้ link แต่ยังคงชื่อเดิมถ้าต้องการ
                $match_region = $region;
                include '../career/listgroup.php'; 
            ?>
        </div>
        <?php endif; ?>


    </div>

    <script>
    // เพิ่มการจัดการเมื่อรูปภาพโหลดไม่สำเร็จ
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.agent-icon').forEach(img => {
            img.addEventListener('error', function() {
                this.outerHTML = '<div style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#6c757d;"><i class=\"fas fa-user-ninja\" style=\"color:white;font-size:10px;\"></i></div>';
            });
        });
    });
    </script>
</body>

</html>