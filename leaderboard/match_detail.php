<?php 
define('ACCESS', true);
require_once '../utils/apikey.php';

session_start();

if (!isset($_GET['matchid'])) {
    echo "No match ID provided.";
    exit;
}
$matchid = $_GET['matchid'];
$api_key = 'XXXXX'; // Replace with your actual API key

function call_api($url, $api_key) {
    $options = [
        "http" => [
            "header" => "Authorization: $api_key\r\nAccept: */*\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    if ($response === FALSE) {
        return null;
    }
    return json_decode($response, true);
}

$url = "https://api.henrikdev.xyz/valorant/v2/match/" . urlencode($matchid);
$data = call_api($url, $api_key);

if (!$data || $data['status'] != 200) {
    echo "ไม่พบข้อมูลแมตช์นี้";
    exit;
}

$meta = $data['data']['metadata'];
$players = $data['data']['players']['all_players'];

$region = $_GET['region'] ?? ($meta['region'] ?? null);
$region = $region ? trim($region) : null;

$is_premier = (isset($meta['premier_info']) && is_array($meta['premier_info']) && !empty($meta['premier_info']));

$mapName = $meta['map'] ?? ($meta['mapName'] ?? 'Unknown Map');
$mode = $meta['mode'] ?? ($is_premier ? 'Premier' : '');
$rounds_played = $meta['rounds_played'] ?? ($meta['roundsPlayed'] ?? 0);
$game_start = $meta['game_start_patched'] ?? '';
$game_length_sec = ($meta['game_length'] ?? 0) / 1000;
$game_duration = sprintf("%dm %ds", floor($game_length_sec / 60), $game_length_sec % 60);

$red_team = array_filter($players, fn($p) => $p['team'] === 'Red');
$blue_team = array_filter($players, fn($p) => $p['team'] === 'Blue');

usort($red_team, fn($a, $b) => $b['stats']['score'] <=> $a['stats']['score']);
usort($blue_team, fn($a, $b) => $b['stats']['score'] <=> $a['stats']['score']);

$red_won = $data['data']['teams']['red']['has_won'];
$blue_won = $data['data']['teams']['blue']['has_won'];
$red_rounds = $data['data']['teams']['red']['rounds_won'];
$blue_rounds = $data['data']['teams']['blue']['rounds_won'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Detail - <?= htmlspecialchars($mapName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --valorant-red: #FF4655;
            --valorant-blue: #00C2FF;
            --valorant-dark: #0F1923;
            --valorant-gray: #1C2B35;
            --valorant-light: #ECE8E1;
            --success: #00ffae;
            --defeat: #ff4e4e;
        }
        
        body {
            background: linear-gradient(135deg, #0F1923 0%, #1C2B35 100%);
            color: var(--valorant-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .match-header {
            background: linear-gradient(135deg, rgba(255, 70, 85, 0.1) 0%, rgba(0, 194, 255, 0.1) 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid rgba(236, 232, 225, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .match-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--valorant-red) 0%, var(--valorant-blue) 100%);
        }
        
        .score-display {
            background: rgba(28, 43, 53, 0.8);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3rem;
        }
        
        .team-score {
            text-align: center;
        }
        
        .team-score .result {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .team-score .score {
            font-size: 2.5em;
            font-weight: bold;
            color: #fff;
        }
        
        .team-score .label {
            font-size: 1em;
            color: rgba(236, 232, 225, 0.7);
            margin-top: 5px;
        }
        
        .victory-text {
            color: var(--success);
        }
        
        .defeat-text {
            color: var(--defeat);
        }
        
        .score-separator {
            font-size: 2.5em;
            font-weight: bold;
            color: #fff;
        }
        
        .map-badge {
            background: rgba(0, 194, 255, 0.2);
            color: var(--valorant-blue);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: bold;
            border: 1px solid var(--valorant-blue);
        }
        
        .scoreboard-table {
            background: rgba(28, 43, 53, 0.8);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .scoreboard-table th,
        .scoreboard-table td {
            text-align: center;
            vertical-align: middle;
            padding: 1rem 0.5rem;
            border: none;
            color: #fff;
        }
        
        .scoreboard-table th {
            background: linear-gradient(90deg, #1e3357 0%, #233b5e 100%);
            color: #ffd700;
            font-weight: 700;
            font-size: 0.95em;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .scoreboard-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .team-header {
            background: linear-gradient(90deg, #1e3357 0%, #233b5e 100%);
            color: #ffd700;
            font-weight: 600;
            font-size: 1.05em;
        }
        
        .team-win-header {
            background: linear-gradient(90deg, var(--success) 0%, #14243a 100%) !important;
            color: #14243a !important;
        }
        
        .team-lose-header {
            background: linear-gradient(90deg, var(--defeat) 0%, #14243a 100%) !important;
        }
        
        .team-win {
            background: linear-gradient(90deg, rgba(0,255,174,0.15) 0%, rgba(0,255,174,0.05) 40%, transparent 60%);
        }
        
        .team-lose {
            background: linear-gradient(90deg, rgba(255,78,78,0.15) 0%, rgba(255,78,78,0.05) 40%, transparent 60%);
        }
        
        .agent-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1e3357;
            object-fit: cover;
            border: 2px solid rgba(255, 215, 0, 0.3);
            transition: transform 0.2s;
        }
        
        .agent-icon:hover {
            transform: scale(1.1);
        }
        
        .player-card {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            border: 2px solid rgba(255, 215, 0, 0.2);
            transition: transform 0.2s;
        }
        
        .player-card:hover {
            transform: scale(1.05);
        }
        
        .player-link {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .player-link:hover {
            color: var(--success);
            text-decoration: underline;
        }
        
        .tag-badge {
            background: #233b5e;
            color: #ffd700;
            font-size: 0.75em;
            border-radius: 6px;
            padding: 2px 8px;
            margin-left: 6px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .rank-icon {
            height: 30px;
            vertical-align: middle;
        }
        
        .round-summary {
            background: rgba(28, 43, 53, 0.8);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            overflow-x: auto;
        }
        
        .round-row {
            display: flex;
            align-items: flex-end;
            gap: 18px;
            margin-bottom: 12px;
        }
        
        .round-label {
            width: 80px;
            color: rgba(236, 232, 225, 0.7);
            font-size: 1.1em;
        }
        
        .round-item {
            width: 36px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .round-icon {
            font-size: 1.7em;
            filter: drop-shadow(0 0 8px currentColor);
        }
        
        .round-number {
            color: #888;
            font-size: 0.9em;
            margin-top: 4px;
        }
        
        .round-total {
            font-size: 1.25em;
            font-weight: bold;
            margin-left: 16px;
        }
        
        @media (max-width: 768px) {
            .score-display {
                gap: 1.5rem;
            }
            
            .team-score .score {
                font-size: 2em;
            }
            
            .scoreboard-table th,
            .scoreboard-table td {
                padding: 0.5rem 0.3rem;
                font-size: 0.9em;
            }
            
            .agent-icon,
            .player-card {
                width: 32px;
                height: 32px;
            }
        }
    </style>
    <?php include '../utils/link.php'; ?>
</head>
<body>
    <div class="container">
        <!-- Match Header -->
        <div class="match-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-3">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        Match Detail
                    </h1>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="map-badge">
                            <i class="fas fa-map me-2"></i><?= htmlspecialchars($mapName) ?>
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-gamepad me-2"></i><?= $mode ? htmlspecialchars($mode) : ($is_premier ? 'Premier' : 'Standard') ?>
                        </span>
                        <span class="badge bg-dark">
                            <i class="fas fa-clock me-2"></i><?= $game_duration ?>
                        </span>
                        <?php if ($game_start): ?>
                        <span class="badge bg-dark">
                            <i class="fas fa-calendar me-2"></i><?= htmlspecialchars($game_start) ?>
                        </span>
                        <?php endif; ?>

                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Score Display -->
        <div class="score-display">
            <div class="team-score">
                <div class="result <?= $red_won ? 'victory-text' : 'defeat-text' ?>">
                    <?= $red_won ? 'VICTORY' : 'DEFEAT' ?>
                </div>
                <div class="score"><?= $red_rounds ?></div>
                <div class="label">Team A</div>
            </div>
            
            <div class="score-separator">:</div>
            
            <div class="team-score">
                <div class="result <?= $blue_won ? 'victory-text' : 'defeat-text' ?>">
                    <?= $blue_won ? 'VICTORY' : 'DEFEAT' ?>
                </div>
                <div class="score"><?= $blue_rounds ?></div>
                <div class="label">Team B</div>
            </div>
        </div>

        <!-- Scoreboard -->
        <div class="table-responsive">
            <table class="table scoreboard-table align-middle">
                <thead>
                    <tr>
                        <th>Card</th>
                        <th>Agent</th>
                        <th>Player</th>
                        <th>Rank</th>
                        <th>ACS</th>
                        <th>K</th>
                        <th>D</th>
                        <th>A</th>
                        <th>KDA</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Team A (Red) -->
                    <tr class="team-header <?= $red_won ? 'team-win-header' : 'team-lose-header' ?>">
                        <td colspan="10" style="text-align:left; padding-left:18px;">
                            <span style="font-weight:600;">Team A</span>
                            <?php if (strtolower($mode) === 'competitive'): ?>
                            <span style="margin-left:18px; font-weight:400; font-size:0.95em;">
                                Avg. Rank:
                                <?php
                                $tiers = array_filter(array_column($red_team, 'currenttier'), fn($t) => is_numeric($t) && $t > 0);
                                if ($tiers && count($tiers)) {
                                    $avg = array_sum($tiers) / count($tiers);
                                    $rankNames = [
                                        3 => 'Iron 1', 4 => 'Iron 2', 5 => 'Iron 3',
                                        6 => 'Bronze 1', 7 => 'Bronze 2', 8 => 'Bronze 3',
                                        9 => 'Silver 1', 10 => 'Silver 2', 11 => 'Silver 3',
                                        12 => 'Gold 1', 13 => 'Gold 2', 14 => 'Gold 3',
                                        15 => 'Platinum 1', 16 => 'Platinum 2', 17 => 'Platinum 3',
                                        18 => 'Diamond 1', 19 => 'Diamond 2', 20 => 'Diamond 3',
                                        21 => 'Ascendant 1', 22 => 'Ascendant 2', 23 => 'Ascendant 3',
                                        24 => 'Immortal 1', 25 => 'Immortal 2', 26 => 'Immortal 3',
                                        27 => 'Radiant'
                                    ];
                                    $avgTier = round($avg);
                                    $avgRank = $rankNames[$avgTier] ?? 'Unknown';
                                    $rankIcon = "../img/rank/".strtolower(str_replace([' ', '.'], '', $avgRank)).".png";
                                    echo "<img src='$rankIcon' alt='$avgRank' class='rank-icon' style='height:20px;'> $avgRank";
                                } else {
                                    echo "-";
                                }
                                ?>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php foreach ($red_team as $p): ?>
                    <tr class="<?= $red_won ? 'team-win' : 'team-lose' ?>">
                        <td>
                            <img src="<?= $p['assets']['card']['small'] ?>" alt="Card" class="player-card">
                        </td>
                        <td>
                            <img src="<?= $p['assets']['agent']['small'] ?>" alt="<?= $p['character'] ?>" class="agent-icon">
                        </td>
                        <td>
                            <?php
                            $player_query = 'riot_id=' . urlencode($p['name'] . '#' . $p['tag']);
                            if ($region) $player_query .= '&region=' . urlencode($region);
                            ?>
                            <a href="leaderboardplayer.php?<?= $player_query ?>" class="player-link">
                                <?= htmlspecialchars($p['name']) ?>
                                <span class="tag-badge">#<?= htmlspecialchars($p['tag']) ?></span>
                            </a>
                        </td>
                        <td>
                            <?php
                            if (strtolower($mode) === 'competitive') {
                                $rankName = $p['currenttier_patched'] ?? '';
                                if ($rankName) {
                                    $rankIcon = "../img/rank/" . strtolower(str_replace([' ', '.'], '', $rankName)) . ".png";
                                    echo "<img src='$rankIcon' alt='$rankName' class='rank-icon'>";
                                } else {
                                    echo "-";
                                }
                            }
                            ?>
                        </td>
                        <td><?= $rounds_played ? round($p['stats']['score'] / $rounds_played) : '-' ?></td>
                        <td><?= $p['stats']['kills'] ?></td>
                        <td><?= $p['stats']['deaths'] ?></td>
                        <td><?= $p['stats']['assists'] ?></td>
                        <td><?= $p['stats']['deaths'] > 0 ? round(($p['stats']['kills'] + $p['stats']['assists']) / $p['stats']['deaths'], 2) : '-' ?></td>
                        <td><?= $p['stats']['score'] ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Team B (Blue) -->
                    <tr class="team-header <?= $blue_won ? 'team-win-header' : 'team-lose-header' ?>">
                        <td colspan="10" style="text-align:left; padding-left:18px;">
                            <span style="font-weight:600;">Team B</span>
                            <?php if (strtolower($mode) === 'competitive'): ?>
                            <span style="margin-left:18px; font-weight:400; font-size:0.95em;">
                                Avg. Rank:
                                <?php
                                $tiers = array_filter(array_column($blue_team, 'currenttier'), fn($t) => is_numeric($t) && $t > 0);
                                if ($tiers && count($tiers)) {
                                    $avg = array_sum($tiers) / count($tiers);
                                    $rankNames = [
                                        3 => 'Iron 1', 4 => 'Iron 2', 5 => 'Iron 3',
                                        6 => 'Bronze 1', 7 => 'Bronze 2', 8 => 'Bronze 3',
                                        9 => 'Silver 1', 10 => 'Silver 2', 11 => 'Silver 3',
                                        12 => 'Gold 1', 13 => 'Gold 2', 14 => 'Gold 3',
                                        15 => 'Platinum 1', 16 => 'Platinum 2', 17 => 'Platinum 3',
                                        18 => 'Diamond 1', 19 => 'Diamond 2', 20 => 'Diamond 3',
                                        21 => 'Ascendant 1', 22 => 'Ascendant 2', 23 => 'Ascendant 3',
                                        24 => 'Immortal 1', 25 => 'Immortal 2', 26 => 'Immortal 3',
                                        27 => 'Radiant'
                                    ];
                                    $avgTier = round($avg);
                                    $avgRank = $rankNames[$avgTier] ?? 'Unknown';
                                    $rankIcon = "../img/rank/".strtolower(str_replace([' ', '.'], '', $avgRank)).".png";
                                    echo "<img src='$rankIcon' alt='$avgRank' class='rank-icon' style='height:20px;'> $avgRank";
                                } else {
                                    echo "-";
                                }
                                ?>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php foreach ($blue_team as $p): ?>
                    <tr class="<?= $blue_won ? 'team-win' : 'team-lose' ?>">
                        <td>
                            <img src="<?= $p['assets']['card']['small'] ?>" alt="Card" class="player-card">
                        </td>
                        <td>
                            <img src="<?= $p['assets']['agent']['small'] ?>" alt="<?= $p['character'] ?>" class="agent-icon">
                        </td>
                        <td>
                            <?php
                            $player_query = 'riot_id=' . urlencode($p['name'] . '#' . $p['tag']);
                            if ($region) $player_query .= '&region=' . urlencode($region);
                            ?>
                            <a href="leaderboardplayer.php?<?= $player_query ?>" class="player-link">
                                <?= htmlspecialchars($p['name']) ?>
                                <span class="tag-badge">#<?= htmlspecialchars($p['tag']) ?></span>
                            </a>
                        </td>
                        <td>
                            <?php
                            if (strtolower($mode) === 'competitive') {
                                $rankName = $p['currenttier_patched'] ?? '';
                                if ($rankName) {
                                    $rankIcon = "../img/rank/" . strtolower(str_replace([' ', '.'], '', $rankName)) . ".png";
                                    echo "<img src='$rankIcon' alt='$rankName' class='rank-icon'>";
                                } else {
                                    echo "-";
                                }
                            }
                            ?>
                        </td>
                        <td><?= $rounds_played ? round($p['stats']['score'] / $rounds_played) : '-' ?></td>
                        <td><?= $p['stats']['kills'] ?></td>
                        <td><?= $p['stats']['deaths'] ?></td>
                        <td><?= $p['stats']['assists'] ?></td>
                        <td><?= $p['stats']['deaths'] > 0 ? round(($p['stats']['kills'] + $p['stats']['assists']) / $p['stats']['deaths'], 2) : '-' ?></td>
                        <td><?= $p['stats']['score'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Round Summary -->
        <div class="round-summary">
            <div class="round-row">
                <div class="round-label">Team A</div>
                <?php
                $rounds = $data['data']['rounds'];
                $red_win_count = 0;
                foreach ($rounds as $i => $round) {
                    if ($round['winning_team'] === 'Red') $red_win_count++;
                ?>
                    <div class="round-item">
                        <?php if ($round['winning_team'] === 'Red'): ?>
                            <i class="fa-solid fa-xmark round-icon" style="color:var(--success);"></i>
                        <?php elseif (!empty($round['bomb_defused'])): ?>
                            <i class="" style="color:#ffd700; font-size:1.3em;"></i>
                        <?php elseif ($round['end_type'] === 'Detonate'): ?>
                            <i class="fa-solid fa-bomb" style="color:#ff4e4e; font-size:1.3em;"></i>
                        <?php else: ?>
                            <span style="display:inline-block;width:24px;height:24px;"></span>
                        <?php endif; ?>
                        <span class="round-number"><?= $i+1 ?></span>
                    </div>
                <?php } ?>
                <span class="round-total" style="color:var(--success);"><?= $red_win_count ?></span>
            </div>
            
            <div class="round-row">
                <div class="round-label">Team B</div>
                <?php
                $blue_win_count = 0;
                foreach ($rounds as $i => $round) {
                    if ($round['winning_team'] === 'Blue') $blue_win_count++;
                ?>
                    <div class="round-item">
                        <?php if ($round['winning_team'] === 'Blue'): ?>
                            <i class="fa-solid fa-xmark round-icon" style="color:var(--defeat);"></i>
                        <?php elseif (!empty($round['bomb_defused'])): ?>
                            <i class="" style="color:#ffd700; font-size:1.3em;"></i>
                        <?php elseif ($round['end_type'] === 'Detonate'): ?>
                            <i class="fa-solid fa-bomb" style="color:#ff4e4e; font-size:1.3em;"></i>
                        <?php else: ?>
                            <span style="display:inline-block;width:24px;height:24px;"></span>
                        <?php endif; ?>
                        <span class="round-number"><?= $i+1 ?></span>
                    </div>
                <?php } ?>
                <span class="round-total" style="color:var(--defeat);"><?= $blue_win_count ?></span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>