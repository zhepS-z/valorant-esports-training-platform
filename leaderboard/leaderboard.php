<?php
session_start();
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key

$region = $_GET['region'] ?? 'ap';
$act = $_GET['act'] ?? 'e9a3';

// URL สำหรับดึงข้อมูลผู้เล่นสูงสุด 1000 คน
$url = "https://api.henrikdev.xyz/valorant/v3/leaderboard/{$region}/pc?act={$act}&limit=1000";

// เพิ่ม API Key ใน Header
$options = [
    "http" => [
        "header" => "Authorization: $api_key\r\n"
    ]
];
$context = stream_context_create($options);

// ดึงข้อมูลจาก API
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    $error = error_get_last();
    echo "เกิดข้อผิดพลาด: " . $error['message'];
    exit;
}

$data = json_decode($response, true);
if ($data === null || !isset($data['data']['players']) || !is_array($data['data']['players'])) {
    echo "เกิดข้อผิดพลาด: ไม่พบข้อมูลหรือข้อมูลไม่ถูกต้อง";
    exit;
}

// เก็บข้อมูลผู้เล่นทั้งหมด
$allPlayers = $data['data']['players'];


// Tier ชื่อแบบเป็น text และ URL รูปภาพ
$tierNames = [
    24 => 'Immortal 1',
    25 => 'Immortal 2',
    26 => 'Immortal 3',
    27 => 'Radiant'
];

$tierImages = [
    24 => '../img/rank/immortal1.png',
    25 => '../img/rank/immortal2.png',
    26 => '../img/rank/immortal3.png',
    27 => '../img/rank/radiant.png'
];

// กำหนดจำนวนผู้เล่นต่อหน้า
$playersPerPage = 100;

// รับค่าหน้าปัจจุบันจาก URL (ค่าเริ่มต้นคือ 1)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// คำนวณตำแหน่งเริ่มต้นและสิ้นสุดของข้อมูลในหน้านั้น
$startIndex = ($page - 1) * $playersPerPage;
$endIndex = $startIndex + $playersPerPage;

// ตรวจสอบว่ามีข้อมูลผู้เล่นหรือไม่
if (isset($allPlayers) && is_array($allPlayers)) {
    $totalPlayers = count($allPlayers); // จำนวนผู้เล่นทั้งหมด
    $totalPages = ceil($totalPlayers / $playersPerPage); // จำนวนหน้าทั้งหมด

    // ตัดข้อมูลผู้เล่นเฉพาะหน้าปัจจุบัน
    $players = array_slice($allPlayers, $startIndex, $playersPerPage);
} else {
    $players = [];
    $totalPlayers = 0;
    $totalPages = 1;
}

$regionNames = [
    "ap" => "Asia Pacific",
    "na" => "North America",
    "eu" => "Europe",
    "kr" => "Korea",
    "br" => "Brazil"
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valorant Leaderboard</title>
    <link href="../css/leaderboard.css" rel="stylesheet">
    <?php include '../utils/link.php'; ?>
</head>

<body>
    <div class="container">
        <br>
        <h1>
            Ranked Leaderboard :: <?= htmlspecialchars($regionNames[$region] ?? $region) ?> Ranked Leaderboard
        </h1>

        <!-- ฟอร์มสำหรับเลือกภูมิภาคและ act -->
        <form method="GET" action="" class="d-flex gap-2 align-items-center">
            <label for="region" class="form-label mb-0">Region:</label>
            <select name="region" id="region" class="form-select" style="width: auto;">
                <option value="ap" <?= $region === 'ap' ? 'selected' : '' ?>>Asia Pacific</option>
                <option value="na" <?= $region === 'na' ? 'selected' : '' ?>>North America</option>
                <option value="eu" <?= $region === 'eu' ? 'selected' : '' ?>>Europe</option>
                <option value="kr" <?= $region === 'kr' ? 'selected' : '' ?>>Korea</option>
                <option value="br" <?= $region === 'br' ? 'selected' : '' ?>>Brazil</option>
            </select>
            <button type="submit" class="btn-custom">See</button>
        </form>
<br>
        <!-- ตารางแสดงข้อมูล -->
        <div class="table-responsive">
            <table id="leaderboard" class="table">
                <thead>
                    <tr>
                        <th scope="col">Place</th>
                        <th scope="col">Player Name</th>
                        <th scope="col" class="text-center">Ranked Rating (RR)</th>
                        <th scope="col" class="text-center">Tier</th>
                        <th scope="col" class="text-center">Wins</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (isset($players) && is_array($players)) {
                        foreach ($players as $player) {
                            $tier = $tierNames[$player['tier']] ?? "Unknown";
                            $tierImage = $tierImages[$player['tier']] ?? 'images/default.png';
                            $tag = !empty($player['tag']) ? $player['tag'] : "SecretAgent";

                            $riotId = $player['name'] . '#' . $tag;
                            $playerLink = "leaderboardplayer.php?riot_id=" . urlencode($riotId) . "&region=" . urlencode($region);

                            if ($player['leaderboard_rank'] == 1) {
                                $rowClass = 'highlight-row highlight-gold';
                            } elseif ($player['leaderboard_rank'] == 2) {
                                $rowClass = 'highlight-row highlight-silver';
                            } elseif ($player['leaderboard_rank'] == 3) {
                                $rowClass = 'highlight-row highlight-bronze';
                            } else {
                                $rowClass = '';
                            }

                            echo "<tr class='{$rowClass}'>";
                            echo "<th scope='row'>{$player['leaderboard_rank']}</th>";
                            echo "<td><a href='{$playerLink}'>{$player['name']}<span class='tag'>#{$tag}</span></a></td>";
                            echo "<td class='text-center'>{$player['rr']}</td>";
                            echo "<td class='text-center'><img src='{$tierImage}' alt='{$tier}' style='width: 30px; height: 30px; margin-right: 5px;'><span class='tier-text'>{$tier}</span></td>";
                            echo "<td class='text-center'>{$player['wins']}</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>ไม่พบข้อมูล leaderboard</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?region=<?= $region ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php else: ?>
                <li class="page-item disabled">
                    <a class="page-link" href="#" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?region=<?= $region ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?region=<?= $region ?>&page=<?= $page + 1 ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <?php else: ?>
                <li class="page-item disabled">
                    <a class="page-link" href="#" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

    </div>
</body>

</html>