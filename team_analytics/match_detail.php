<?php 
define('ACCESS', true);
require_once '../utils/apikey.php';

session_start();

if (!isset($_GET['matchid'])) {
    echo "No match ID provided.";
    exit;
}
$matchid = $_GET['matchid'];
$api_key = 'HDEV-1a5b2355-49ed-4abc-9c97-8cad2223a1a8';

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
$rounds = $data['data']['rounds'];

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

// Calculate total stats
$total_kills = array_sum(array_column(array_column($players, 'stats'), 'kills'));
$total_damage = array_sum(array_column($players, 'damage_made'));
$total_headshots = array_sum(array_column(array_column($players, 'stats'), 'headshots'));
$hs_percentage = $total_kills > 0 ? round(($total_headshots / $total_kills) * 100, 1) : 0;

// Calculate team economy stats
$red_total_spent = 0;
foreach ($red_team as $player) {
    if (isset($player['economy']['spent'])) {
        if (is_array($player['economy']['spent'])) {
            if (isset($player['economy']['spent']['overall'])) {
                $red_total_spent += is_array($player['economy']['spent']['overall']) 
                    ? array_sum($player['economy']['spent']['overall'])
                    : $player['economy']['spent']['overall'];
            }
        } else {
            $red_total_spent += $player['economy']['spent'];
        }
    }
}

$blue_total_spent = 0;
foreach ($blue_team as $player) {
    if (isset($player['economy']['spent'])) {
        if (is_array($player['economy']['spent'])) {
            if (isset($player['economy']['spent']['overall'])) {
                $blue_total_spent += is_array($player['economy']['spent']['overall']) 
                    ? array_sum($player['economy']['spent']['overall'])
                    : $player['economy']['spent']['overall'];
            }
        } else {
            $blue_total_spent += $player['economy']['spent'];
        }
    }
}

// คำนวณค่าเฉลี่ยต่อผู้เล่น
$red_avg_spent = count($red_team) > 0 ? round($red_total_spent / count($red_team)) : 0;
$blue_avg_spent = count($blue_team) > 0 ? round($blue_total_spent / count($blue_team)) : 0;

// คำนวณค่าเฉลี่ยต่อรอบ
$red_avg_per_round = $rounds_played > 0 ? round($red_total_spent / $rounds_played) : 0;
$blue_avg_per_round = $rounds_played > 0 ? round($blue_total_spent / $rounds_played) : 0;

// ADVANCED ANALYSIS FUNCTIONS

// 1. Player Performance Analysis
function calculatePlayerPerformance($player, $rounds_played) {
    $stats = $player['stats'];
    
    // คำนวณ Headshot Rate จาก headshots / total_hits แทน
    $total_hits = $stats['headshots'] + $stats['bodyshots'] + $stats['legshots'];
    $hs_rate = $total_hits > 0 ? round(($stats['headshots'] / $total_hits) * 100, 1) : 0;
    
    $acs = $rounds_played ? round($stats['score'] / $rounds_played) : 0;
    $kda = $stats['deaths'] > 0 ? round(($stats['kills'] + $stats['assists']) / $stats['deaths'], 2) : $stats['kills'] + $stats['assists'];
    
    return [
        'acs' => $acs,
        'kda' => $kda,
        'hs_rate' => $hs_rate,
        'damage_per_round' => $rounds_played ? round($player['damage_made'] / $rounds_played) : 0
    ];
}

// 2. Team Analysis
function analyzeTeamPerformance($team_players, $rounds_played, $rounds_won, $rounds) {
    $total_kills = array_sum(array_column(array_column($team_players, 'stats'), 'kills'));
    $total_deaths = array_sum(array_column(array_column($team_players, 'stats'), 'deaths'));
    $total_assists = array_sum(array_column(array_column($team_players, 'stats'), 'assists'));
    $total_damage = array_sum(array_column($team_players, 'damage_made'));
    $total_score = array_sum(array_column(array_column($team_players, 'stats'), 'score'));
    
    $avg_acs = $rounds_played ? round($total_score / (count($team_players) * $rounds_played)) : 0;
    
    // Economy efficiency
    $total_spent = array_sum(array_column(array_column($team_players, 'economy'), 'spent'));
    $economy_efficiency = $total_spent > 0 ? round(($total_score / $total_spent) * 100, 2) : 0;
    
    // Win conditions analysis
    $attack_wins = 0;
    $defense_wins = 0;
    $bomb_plants = 0;
    $bomb_defuses = 0;
    
    foreach ($rounds as $round) {
        // This would need more detailed round data to determine attack/defense sides
    }
    
    return [
        'total_kills' => $total_kills,
        'total_deaths' => $total_deaths,
        'total_assists' => $total_assists,
        'total_damage' => $total_damage,
        'avg_acs' => $avg_acs,
        'economy_efficiency' => $economy_efficiency,
        'attack_wins' => $attack_wins,
        'defense_wins' => $defense_wins,
        'bomb_plants' => $bomb_plants,
        'bomb_defuses' => $bomb_defuses,
        'win_rate' => $rounds_played ? round(($rounds_won / $rounds_played) * 100, 1) : 0
    ];
}

// 3. Strategy Analysis
function analyzeStrategy($rounds, $players) {
    $plant_locations = [];
    $kill_locations = [];
    $first_bloods = [];
    $clutch_situations = [];
    
    foreach ($rounds as $round_num => $round) {
        // Plant events handling
        if (isset($round['plant_events']) && !empty($round['plant_events'])) {
            foreach ($round['plant_events'] as $plant_event) {
                // Make sure plant_location exists and is a string
                if (isset($plant_event['plant_location']) && is_string($plant_event['plant_location'])) {
                    $location = $plant_event['plant_location'];
                    if (!isset($plant_locations[$location])) {
                        $plant_locations[$location] = 0;
                    }
                    $plant_locations[$location]++;
                }
            }
        }
    }
    
    // Find most common plant site safely
    $most_common_plant = 'Unknown';
    $max_plants = 0;
    
    foreach ($plant_locations as $location => $count) {
        if (is_string($location) && is_numeric($count) && $count > $max_plants) {
            $max_plants = $count;
            $most_common_plant = $location;
        }
    }
    
    return [
        'plant_locations' => $plant_locations,
        'most_common_plant' => $most_common_plant,
        'kill_locations' => [],  // Empty array for now
        'first_bloods' => [],    // Empty array for now
        'clutch_situations' => [] // Empty array for now
    ];
}

// 4. Advanced Competitive Metrics
function calculateAdvancedMetrics($players, $rounds) {
    $player_metrics = [];
    
    foreach ($players as $player) {
        $name = $player['name'] . '#' . $player['tag'];
        
        // Clutch rate calculation (simplified)
        $clutch_attempts = 0;
        $clutch_successes = 0;
        
        // Trade kill efficiency (simplified)
        $trade_kills = 0;
        $trade_opportunities = 0;
        
        // Opening duel success rate (simplified)
        $opening_duels = 0;
        $opening_duel_wins = 0;
        
        // Spike-related impact (simplified)
        $plants = 0;
        $defuses = 0;
        
        $player_metrics[$name] = [
            'clutch_rate' => $clutch_attempts > 0 ? round(($clutch_successes / $clutch_attempts) * 100, 1) : 0,
            'trade_efficiency' => $trade_opportunities > 0 ? round(($trade_kills / $trade_opportunities) * 100, 1) : 0,
            'opening_success' => $opening_duels > 0 ? round(($opening_duel_wins / $opening_duels) * 100, 1) : 0,
            'spike_impact' => $plants + $defuses
        ];
    }
    
    return $player_metrics;
}

// Calculate advanced metrics
$red_team_performance = analyzeTeamPerformance($red_team, $rounds_played, $red_rounds, $rounds);
$blue_team_performance = analyzeTeamPerformance($blue_team, $rounds_played, $blue_rounds, $rounds);
$strategy_analysis = analyzeStrategy($rounds, $players);
$advanced_metrics = calculateAdvancedMetrics($players, $rounds);

// Player performance for each team member
$red_player_performance = [];
foreach ($red_team as $player) {
    $player_name = $player['name'] . '#' . $player['tag'];
    $red_player_performance[$player_name] = calculatePlayerPerformance($player, $rounds_played);
}

$blue_player_performance = [];
foreach ($blue_team as $player) {
    $player_name = $player['name'] . '#' . $player['tag'];
    $blue_player_performance[$player_name] = calculatePlayerPerformance($player, $rounds_played);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Detail - <?= htmlspecialchars($mapName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/match_analysis.css">
    <style>
    
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
                <div class="label">Team Red</div>
            </div>
            
            <div class="score-separator">:</div>
            
            <div class="team-score">
                <div class="result <?= $blue_won ? 'victory-text' : 'defeat-text' ?>">
                    <?= $blue_won ? 'VICTORY' : 'DEFEAT' ?>
                </div>
                <div class="score"><?= $blue_rounds ?></div>
                <div class="label">Team Blue</div>
            </div>
        </div>

        <!-- แท็บเมนู -->
        <ul class="nav nav-tabs mb-3" id="matchTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="scoreboard-tab" data-bs-toggle="tab" data-bs-target="#scoreboard" type="button" role="tab">Scoreboard</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="analysis-tab" data-bs-toggle="tab" data-bs-target="#analysis" type="button" role="tab">Match Analysis</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab">Advanced Analysis</button>
            </li>
        </ul>

        <!-- เนื้อหาแท็บ -->
        <div class="tab-content" id="matchTabsContent">
            <!-- แท็บ Scoreboard -->
            <div class="tab-pane fade show active" id="scoreboard" role="tabpanel">
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
                                    <span style="font-weight:600;">Team Red</span>
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
                                    <span style="font-weight:600;">Team Blue</span>
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
            </div>

            <!-- แท็บ Match Analysis -->
            <div class="tab-pane fade" id="analysis" role="tabpanel">
                <h2 class="analysis-title">Match Analysis</h2>

                <div class="row">
                    <!-- Overall Match Stats -->
                    <div class="col-md-3 mb-3">
                        <div class="stat-card text-center">
                            <div class="stat-value"><?= $total_kills ?></div>
                            <div class="stat-label">Total Kills</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card text-center">
                            <div class="stat-value"><?= $hs_percentage ?>%</div>
                            <div class="stat-label">Headshot %</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card text-center">
                            <div class="stat-value"><?= $total_damage ?></div>
                            <div class="stat-label">Total Damage</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card text-center">
                            <div class="stat-value"><?= $rounds_played ?></div>
                            <div class="stat-label">Rounds Played</div>
                        </div>
                    </div>
                </div>

                <!-- Top Performers -->
                <div class="analysis-section">
                    <h3 class="analysis-title">Top Performers</h3>
                    <div class="row">
                        <?php
                        // Sort players by score
                        $top_players = $players;
                        usort($top_players, fn($a, $b) => $b['stats']['score'] <=> $a['stats']['score']);
                        $top_3_players = array_slice($top_players, 0, 3);
                        
                        // แก้ไขในส่วนการคำนวณ Top Performers
                        foreach ($top_3_players as $player): 
                            $kda = $player['stats']['deaths'] > 0 
                                ? round(($player['stats']['kills'] + $player['stats']['assists']) / $player['stats']['deaths'], 2) 
                                : $player['stats']['kills'] + $player['stats']['assists'];
                            
                            $acs = $rounds_played ? round($player['stats']['score'] / $rounds_played) : 0;
                            
                            // แก้ไขการคำนวณ Headshot Rate ให้ปัดเศษขึ้นและไม่มีทศนิยม
                            $total_hits = ($player['stats']['headshots'] ?? 0) + ($player['stats']['bodyshots'] ?? 0) + ($player['stats']['legshots'] ?? 0);
                            $hs_rate = $total_hits > 0 ? round(($player['stats']['headshots'] / $total_hits) * 100, 0) : 0;
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="player-analysis-card">
                                <div class="row align-items-center">
                                    <div class="col-4 text-center">
                                        <img src="<?= $player['assets']['agent']['small'] ?>" 
                                             alt="<?= $player['character'] ?>" 
                                             class="player-avatar">
                                    </div>
                                    <div class="col-8">
                                        <h5 class="mb-1"><?= htmlspecialchars($player['name']) ?></h5>
                                        <p class="mb-1">ACS: <strong><?= $acs ?></strong></p>
                                        <p class="mb-0">KDA: <strong><?= $kda ?></strong></p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Headshot Rate</span>
                                        <span><?= $hs_rate ?>%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?= $hs_rate ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Team Statistics -->
                <div class="analysis-section">
                    <h3 class="analysis-title">Team Statistics</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="stat-card">
                                <h5>Team Red</h5>
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1">Total Kills: <strong><?= array_sum(array_column(array_column($red_team, 'stats'), 'kills')) ?></strong></p>
                                        <p class="mb-1">Total Deaths: <strong><?= array_sum(array_column(array_column($red_team, 'stats'), 'deaths')) ?></strong></p>
                                        <p class="mb-0">Total Assists: <strong><?= array_sum(array_column(array_column($red_team, 'stats'), 'assists')) ?></strong></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-1">Avg ACS: <strong><?= $rounds_played ? round(array_sum(array_column(array_column($red_team, 'stats'), 'score')) / (count($red_team) * $rounds_played)) : 0 ?></strong></p>
                                        <p class="mb-0">Total Damage: <strong><?= array_sum(array_column($red_team, 'damage_made')) ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card">
                                <h5>Team Blue</h5>
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1">Total Kills: <strong><?= array_sum(array_column(array_column($blue_team, 'stats'), 'kills')) ?></strong></p>
                                        <p class="mb-1">Total Deaths: <strong><?= array_sum(array_column(array_column($blue_team, 'stats'), 'deaths')) ?></strong></p>
                                        <p class="mb-0">Total Assists: <strong><?= array_sum(array_column(array_column($blue_team, 'stats'), 'assists')) ?></strong></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-1">Avg ACS: <strong><?= $rounds_played ? round(array_sum(array_column(array_column($blue_team, 'stats'), 'score')) / (count($blue_team) * $rounds_played)) : 0 ?></strong></p>
                                        <p class="mb-0">Total Damage: <strong><?= array_sum(array_column($blue_team, 'damage_made')) ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Economy Analysis -->
                <div class="analysis-section">
                    <h3 class="analysis-title">Economy Analysis</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="stat-card">
                                <h5>Team Red Economy</h5>
                                <p class="mb-1">Total Spent: <span class="economy-value"><?= number_format($red_total_spent) ?></span></p>
                                <p class="mb-1">Average per Player: <span class="economy-value"><?= number_format($red_avg_spent) ?></span></p>
                                <p class="mb-0">Average per Round: <span class="economy-value"><?= $rounds_played ? number_format(round($red_total_spent / $rounds_played)) : 0 ?></span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card">
                                <h5>Team Blue Economy</h5>
                                <p class="mb-1">Total Spent: <span class="economy-value"><?= number_format($blue_total_spent) ?></span></p>
                                <p class="mb-1">Average per Player: <span class="economy-value"><?= number_format($blue_avg_spent) ?></span></p>
                                <p class="mb-0">Average per Round: <span class="economy-value"><?= $rounds_played ? number_format(round($blue_total_spent / $rounds_played)) : 0 ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- แท็บ Advanced Analysis -->
            <div class="tab-pane fade" id="advanced" role="tabpanel">
                <h2 class="analysis-title">Advanced Match Analysis</h2>

                <!-- Team Performance Analysis -->
                <div class="analysis-section">
                    <h3 class="analysis-title">Team Performance Analysis</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="stat-card">
                                <h5>Team Red Performance</h5>
                                <p class="mb-1">Win Rate: <strong><?= $red_team_performance['win_rate'] ?>%</strong></p>
                                <p class="mb-1">Economy Efficiency: <strong><?= $red_team_performance['economy_efficiency'] ?>%</strong></p>
                                <p class="mb-1">K/D Ratio: <strong><?= $red_team_performance['total_deaths'] > 0 ? round($red_team_performance['total_kills'] / $red_team_performance['total_deaths'], 2) : '-' ?></strong></p>
                                <p class="mb-0">Damage per Round: <strong><?= $rounds_played ? round($red_team_performance['total_damage'] / $rounds_played) : 0 ?></strong></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card">
                                <h5>Team Blue Performance</h5>
                                <p class="mb-1">Win Rate: <strong><?= $blue_team_performance['win_rate'] ?>%</strong></p>
                                <p class="mb-1">Economy Efficiency: <strong><?= $blue_team_performance['economy_efficiency'] ?>%</strong></p>
                                <p class="mb-1">K/D Ratio: <strong><?= $blue_team_performance['total_deaths'] > 0 ? round($blue_team_performance['total_kills'] / $blue_team_performance['total_deaths'], 2) : '-' ?></strong></p>
                                <p class="mb-0">Damage per Round: <strong><?= $rounds_played ? round($blue_team_performance['total_damage'] / $rounds_played) : 0 ?></strong></p>
                            </div>
                        </div>
                    </div>

                    <!-- Team Performance Chart -->
                    <div class="chart-container">
                        <canvas id="teamPerformanceChart"></canvas>
                    </div>
                </div>

                <!-- Strategy Analysis -->
                <div class="analysis-section">
                    <h3 class="analysis-title">Strategy Analysis</h3>
                    
                    <!-- Plant Locations -->
                    <div class="heatmap-container">
                        <h5>Spike Plant Locations</h5>
                        <?php if (!empty($strategy_analysis['plant_locations'])): ?>
                            <p>Most common plant site: <span class="metric-badge"><?= $strategy_analysis['most_common_plant'] ?></span></p>
                            <div class="heatmap-placeholder">
                                <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                                <p>Plant Location Heatmap for <?= htmlspecialchars($mapName) ?></p>
                                <p class="small">(Requires detailed round data from API)</p>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No plant location data available</p>
                        <?php endif; ?>
                    </div>

                    <!-- Kill Locations -->
                    <div class="heatmap-container">
                        <h5>Kill Locations Heatmap</h5>
                        <div class="heatmap-placeholder">
                            <i class="fas fa-crosshairs fa-3x mb-3"></i>
                            <p>Kill Location Heatmap for <?= htmlspecialchars($mapName) ?></p>
                            <p class="small">(Requires detailed kill data from API)</p>
                        </div>
                    </div>
                </div>

                <!-- Advanced Player Metrics -->
                <div class="analysis-section">
                    <h3 class="analysis-title">Advanced Player Metrics</h3>
                    
                    <!-- Player Radar Charts -->
                    <div class="row">
                        <?php 
                        $top_5_players = array_slice($top_players, 0, 5);
                        foreach ($top_5_players as $index => $player): 
                            $player_name = $player['name'] . '#' . $player['tag'];
                            $performance = calculatePlayerPerformance($player, $rounds_played);
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="radar-chart-container">
                                <h6 class="text-center"><?= htmlspecialchars($player['name']) ?> Performance Radar</h6>
                                <canvas id="playerRadar<?= $index ?>"></canvas>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Advanced Metrics Table -->
                    <div class="table-responsive">
                        <table class="table scoreboard-table align-middle">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th>Agent</th>
                                    <th>ACS</th>
                                    <th>KDA</th>
                                    <th>HS%</th>
                                    <th>DMG/Round</th>
                                    <th>Clutch Rate</th>
                                    <th>Trade Eff.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($players as $player): 
                                    $player_name = $player['name'] . '#' . $player['tag'];
                                    $performance = calculatePlayerPerformance($player, $rounds_played);
                                    $advanced = $advanced_metrics[$player_name] ?? [];
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $player['assets']['agent']['small'] ?>" alt="<?= $player['character'] ?>" class="agent-icon me-2">
                                            <span><?= htmlspecialchars($player['name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= $player['character'] ?></td>
                                    <td><?= $performance['acs'] ?></td>
                                    <td><?= $performance['kda'] ?></td>
                                    <td><?= $performance['hs_rate'] ?>%</td>
                                    <td><?= $performance['damage_per_round'] ?></td>
                                    <td><?= $advanced['clutch_rate'] ?? 0 ?>%</td>
                                    <td><?= $advanced['trade_efficiency'] ?? 0 ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Round Analysis -->
                <div class="analysis-section">
                    <h3 class="analysis-title">Round-by-Round Analysis</h3>
                    
                    <!-- Win Condition Analysis -->
                    <div class="chart-container">
                        <canvas id="roundOutcomeChart"></canvas>
                    </div>

                    <!-- Economy vs Round Outcome -->
                    <div class="chart-container">
                        <canvas id="economyRoundChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Round Summary -->
        <div class="round-summary">
            <div class="round-row">
                <div class="round-label">Team Red</div>
                <?php
                $red_win_count = 0;
                foreach ($rounds as $i => $round) {
                    if ($round['winning_team'] === 'Red') $red_win_count++;
                ?>
                    <div class="round-item">
                        <?php if ($round['winning_team'] === 'Red'): ?>
                            <i class="fa-solid fa-xmark round-icon" style="color:var(--success);"></i>
                        <?php elseif (!empty($round['bomb_defused'])): ?>
                            <i class="fa-solid fa-land-mine-on" style="color:#ffd700; font-size:1.3em;"></i>
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
                <div class="round-label">Team Blue</div>
                <?php
                $blue_win_count = 0;
                foreach ($rounds as $i => $round) {
                    if ($round['winning_team'] === 'Blue') $blue_win_count++;
                ?>
                    <div class="round-item">
                        <?php if ($round['winning_team'] === 'Blue'): ?>
                            <i class="fa-solid fa-xmark round-icon" style="color:var(--defeat);"></i>
                        <?php elseif (!empty($round['bomb_defused'])): ?>
                            <i class="fa-solid fa-land-mine-on" style="color:#ffd700; font-size:1.3em;"></i>
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
    <script>
        // Team Performance Chart
        const teamPerformanceCtx = document.getElementById('teamPerformanceChart').getContext('2d');
        const teamPerformanceChart = new Chart(teamPerformanceCtx, {
            type: 'bar',
            data: {
                labels: ['Win Rate', 'Economy Efficiency', 'K/D Ratio', 'Damage/Round'],
                datasets: [
                    {
                        label: 'Team Red',
                        data: [
                            <?= $red_team_performance['win_rate'] ?>,
                            <?= $red_team_performance['economy_efficiency'] ?>,
                            <?= $red_team_performance['total_deaths'] > 0 ? round($red_team_performance['total_kills'] / $red_team_performance['total_deaths'], 2) : 0 ?>,
                            <?= $rounds_played ? round($red_team_performance['total_damage'] / $rounds_played) : 0 ?>
                        ],
                        backgroundColor: 'rgba(255, 70, 85, 0.7)',
                        borderColor: 'rgba(255, 70, 85, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Team Blue',
                        data: [
                            <?= $blue_team_performance['win_rate'] ?>,
                            <?= $blue_team_performance['economy_efficiency'] ?>,
                            <?= $blue_team_performance['total_deaths'] > 0 ? round($blue_team_performance['total_kills'] / $blue_team_performance['total_deaths'], 2) : 0 ?>,
                            <?= $rounds_played ? round($blue_team_performance['total_damage'] / $rounds_played) : 0 ?>
                        ],
                        backgroundColor: 'rgba(0, 194, 255, 0.7)',
                        borderColor: 'rgba(0, 194, 255, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    }
                }
            }
        });

        // Player Radar Charts
        <?php foreach ($top_5_players as $index => $player): 
            $player_name = $player['name'] . '#' . $player['tag'];
            $performance = calculatePlayerPerformance($player, $rounds_played);
        ?>
        const playerRadarCtx<?= $index ?> = document.getElementById('playerRadar<?= $index ?>').getContext('2d');
        const playerRadarChart<?= $index ?> = new Chart(playerRadarCtx<?= $index ?>, {
            type: 'radar',
            data: {
                labels: ['ACS', 'KDA', 'HS%', 'Damage/Round', 'Clutch', 'Economy'],
                datasets: [{
                    label: '<?= htmlspecialchars($player['name']) ?>',
                    data: [
                        <?= $performance['acs'] ?>,
                        <?= $performance['kda'] * 10 ?>, // Scaled for radar chart
                        <?= $performance['hs_rate'] ?>,
                        <?= $performance['damage_per_round'] / 10 ?>, // Scaled for radar chart
                        50, // Placeholder for clutch rate
                        70  // Placeholder for economy efficiency
                    ],
                    backgroundColor: 'rgba(255, 70, 85, 0.2)',
                    borderColor: 'rgba(255, 70, 85, 1)',
                    pointBackgroundColor: 'rgba(255, 70, 85, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(255, 70, 85, 1)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    r: {
                        angleLines: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        pointLabels: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.5)',
                            backdropColor: 'transparent'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    }
                }
            }
        });
        <?php endforeach; ?>

        // Round Outcome Chart
        const roundOutcomeCtx = document.getElementById('roundOutcomeChart').getContext('2d');
        const roundOutcomeChart = new Chart(roundOutcomeCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(range(1, $rounds_played)) ?>,
                datasets: [
                    {
                        label: 'Team Red Score',
                        data: [
                            <?php
                            $red_score = 0;
                            $red_scores = [];
                            foreach ($rounds as $round) {
                                if ($round['winning_team'] === 'Red') {
                                    $red_score++;
                                }
                                $red_scores[] = $red_score;
                            }
                            echo implode(', ', $red_scores);
                            ?>
                        ],
                        borderColor: 'rgba(255, 70, 85, 1)',
                        backgroundColor: 'rgba(255, 70, 85, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Team Blue Score',
                        data: [
                            <?php
                            $blue_score = 0;
                            $blue_scores = [];
                            foreach ($rounds as $round) {
                                if ($round['winning_team'] === 'Blue') {
                                    $blue_score++;
                                }
                                $blue_scores[] = $blue_score;
                            }
                            echo implode(', ', $blue_scores);
                            ?>
                        ],
                        borderColor: 'rgba(0, 194, 255, 1)',
                        backgroundColor: 'rgba(0, 194, 255, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    }
                }
            }
        });

        // Economy vs Round Outcome Chart
        const economyRoundCtx = document.getElementById('economyRoundChart').getContext('2d');
        const economyRoundChart = new Chart(economyRoundCtx, {
            type: 'bar',
            data: {
                labels: ['Round 1-5', 'Round 6-10', 'Round 11-15', 'Round 16-20', 'Round 21+'],
                datasets: [
                    {
                        label: 'Team Red Avg Economy',
                        data: [2500, 3200, 3800, 4200, 4500], // Placeholder data
                        backgroundColor: 'rgba(255, 70, 85, 0.7)',
                        borderColor: 'rgba(255, 70, 85, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Team Blue Avg Economy',
                        data: [2400, 3100, 3700, 4100, 4400], // Placeholder data
                        backgroundColor: 'rgba(0, 194, 255, 0.7)',
                        borderColor: 'rgba(0, 194, 255, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Average Economy',
                            color: 'rgba(255, 255, 255, 0.7)'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>