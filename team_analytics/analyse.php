<?php 
session_start(); // เริ่มต้นเซสชัน
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
require_once '../auth/auth_check.php';
include '../utils/db.php'; // ใช้ connection จาก db.php

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch team details for the current user
    $teamDetails = [];
    $teamQuery = "SELECT t.team_id, t.team_name, t.abbreviation, t.team_logo 
                  FROM teams t
                  JOIN team_members tm ON t.team_id = tm.team_id
                  WHERE tm.user_id = ?";
    $stmt = $conn->prepare($teamQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // ตรวจสอบว่า team_logo มีเส้นทางที่ถูกต้องหรือไม่
        $logoFile = !empty($row['team_logo']) ? $row['team_logo'] : '';
        $row['team_logo'] = $logoFile ? "../" . $logoFile : "../images/team_logo_placeholder.png";
        // team_id เก็บไว้ด้วยเพื่อใช้ดึงสมาชิกทีม
        $row['team_id'] = intval($row['team_id']);
        $teamDetails[] = $row;
    }
    $stmt->close();

    // Fetch premier team details for the current user
    $premierTeamDetails = [];
    $premierTeamQuery = "SELECT pt.team_name, pt.team_tag, u.first_name, u.last_name 
                         FROM premier_teams pt
                         JOIN premier_team_members ptm ON pt.id = ptm.premier_team_id
                         JOIN users u ON pt.created_by = u.user_id
                         WHERE ptm.user_id = ?";
    $stmt = $conn->prepare($premierTeamQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $premierTeamDetails[] = $row;
    }
    $stmt->close();

    // เก็บประวัติแมตช์จากทุกทีม premier ที่พบ
    $all_match_history = [];
    $all_premier_players = []; // <-- เพิ่มตัวแปรเก็บสมาชิกของทีม Premier

    // ----- เรียกแมตช์โหมด "premier" ของผู้ใช้ที่เข้าชมหน้า (ใช้ v3/matches) -----
    // ดึง riot_id และ region จากตาราง users (ฐานข้อมูลเก็บ riot_id เป็นรูปแบบ "name#tag")
    $player_matches = [];

    // เตรียมตัวแปร
    $riot_region = null;
    $riot_name = null;
    $riot_tag = null;
    $riot_id_db = null;

    $stmt_u = $conn->prepare("SELECT riot_id, region FROM users WHERE user_id = ? LIMIT 1");
    $stmt_u->bind_param("i", $user_id);
    $stmt_u->execute();
    $stmt_u->bind_result($riot_id_db, $riot_region_db);
    $stmt_u->fetch();
    $stmt_u->close();

    // map ชื่อคอลัมน์ที่ได้ให้ตัวแปรที่เดิมใช้
    if (!empty($riot_region_db)) {
        $riot_region = $riot_region_db;
    }

    // ถ้าไม่มีแยก name/tag ให้พยายาม parse จาก riot_id (รูปแบบ name#tag หรือ name tag)
    if (empty($riot_name) || empty($riot_tag)) {
        if (!empty($riot_id_db)) {
            if (strpos($riot_id_db, '#') !== false) {
                list($pname, $ptag) = explode('#', $riot_id_db, 2);
            } elseif (strpos($riot_id_db, ' ') !== false) {
                $parts = preg_split('/\s+/', trim($riot_id_db));
                $pname = $parts[0];
                $ptag  = $parts[count($parts)-1];
            } else {
                $pname = $riot_id_db;
                $ptag  = '';
            }
            $riot_name = $riot_name ?: $pname;
            $riot_tag  = $riot_tag  ?: $ptag;
        }
    }

    // default region ถ้าไม่มีใน DB — ปรับตามการตั้งค่าระบบ (ตัวอย่างใช้ "ap")
    $region = $riot_region ?: 'ap';

    // หมายเหตุ: การเรียก API v3/matches ถูกยกเลิกและเอาออกแล้ว
    // เก็บตัวแปรไว้เป็น array ว่างเพื่อความเข้ากันได้ของส่วนแสดงผลที่อาจคาดหวังตัวแปรนี้
    $player_matches = [];
}
?>

<?php
// ตรวจสอบบทบาทของผู้ใช้
$show_premier_link = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();

    if ($role === 'manager' || $role === 'admin') {
        $show_premier_link = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($teamDetails[0]['team_name'] ?? 'Unknown Team'); ?> :: Analysis</title>
    <link rel="stylesheet" href="../css/analyse.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>

    </style>
    <?php include '../utils/link.php'; ?>
</head>

<body>
    <div class="main-container">
        <section class="hero">
            <h2>วิเคราะห์แมตช์ Valorant สำหรับทีม</h2>
            <p>สำหรับทีม Valorant ที่ต้องการพัฒนากลยุทธ์ วิเคราะห์สถิติทีม และเพิ่มโอกาสในการชนะ</p>
            <a href="#dashboard" class="main-btn ">เริ่มวิเคราะห์ทีมของคุณ</a>
        </section>

        <h2 class="section-title" id="dashboard">แดชบอร์ดวิเคราะห์ทีม</h2>

        <div class="dashboard">
            <!-- Win Rate Chart (Doughnut) -->
            <div class="card">
                <h3>Win Rate Analysis</h3>
                <div class="chart-main-container">
                    <canvas id="winRateChart"></canvas>
                </div>
            </div>

            <!-- Map Win Rate Chart (Bar) -->
            <div class="card">
                <h3>Map Win Rate Analysis</h3>
                <div class="chart-main-container">
                    <canvas id="mapWinRateChart"></canvas>
                </div>
            </div>
        </div>
        <h2 class="section-title" id="roster">ทีมของฉัน</h2>
        <!-- Container for Team and Premier Team -->
        <div class="team-premier-container">

            <?php if (!empty($teamDetails)): ?>
            <?php foreach ($teamDetails as $team): ?>
            <div class="team-card">
                <div class="team-avatar">
                    <img src="<?= htmlspecialchars($team['team_logo']) ?>" alt="Team Logo"
                        style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="player-name"><?= htmlspecialchars($team['team_name']) ?></div>
                <div class="player-role"><?= htmlspecialchars($team['abbreviation']) ?></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p>ไม่มีข้อมูลทีมในระบบ</p>
            <?php endif; ?>

            <!-- Card for Premier Team Details (ใช้ผลจาก fetchPremierTeamData) -->
            <?php if (!empty($premierTeamDetails)): ?>
            <?php foreach ($premierTeamDetails as $premierTeam): ?>
            <?php
                    $team_name = $premierTeam['team_name'];
                    $team_tag = $premierTeam['team_tag'];
                    // ใช้ตัวแปร $api_key ที่มาจาก ../utils/apikey.php
                    $team_data = fetchPremierTeamData($team_name, $team_tag, $api_key);

                    $team_icon = $team_data['customization']['image'] ?? null;

                    // ดึงสมาชิก (API ระบุ field เป็น "member")
                    $members = $team_data['member'] ?? $team_data['members'] ?? [];
                    if (is_array($members)) {
                        foreach ($members as $m) {
                            $all_premier_players[] = [
                                'name'  => $m['name'] ?? ($m['nickname'] ?? 'Unknown'),
                                'tag'   => $m['tag'] ?? '',
                                'puuid' => $m['puuid'] ?? ''
                            ];
                        }
                    }

                    // เรียก history โดยใช้ชื่อและแท็ก (new endpoint)
                    $match_history = fetchPremierMatchHistory($team_name, $team_tag, $api_key);

                    // รวม history เข้าตัวแปรรวม พร้อมแนบชื่อทีม
                    if (is_array($match_history)) {
                        foreach ($match_history as $mh) {
                            $mh['team_name'] = $team_name;
                            $all_match_history[] = $mh;
                        }
                    }
                    ?>
            <div class="team-card">
                <div class="premier-avatar">
                    <?php if ($team_icon): ?>
                    <img src="<?= htmlspecialchars($team_icon) ?>" alt="Premier Team Logo"
                        style="height: 100px; width: 100px; border-radius: 6px;">
                    <?php else: ?>
                    <p>ไม่สามารถโหลดรูปทีมได้</p>
                    <?php endif; ?>
                </div>
                <div class="player-name"><?= htmlspecialchars($premierTeam['team_name']) ?></div>
                <div class="player-role"><?= htmlspecialchars($premierTeam['team_tag']) ?></div>
            </div>



            <?php endforeach; ?>
            <?php else: ?>
            <p>ไม่มีข้อมูลทีม Premier ในระบบ</p>
            <?php endif; ?>
        </div>
        <h2 class="section-title" id="roster">รายชื่อผู้เล่นในทีม</h2>

        <?php
// ดึงสมาชิกทีมจากตาราง team_members + users สำหรับทีมที่อยู่ใน $teamDetails
$team_members_all = [];

if (!empty($teamDetails)) {
    $member_stmt = $conn->prepare("SELECT u.user_id, u.riot_id, u.profile_img, u.first_name, u.last_name, u.region, tm.role_in_team
                                   FROM users u
                                   JOIN team_members tm ON u.user_id = tm.user_id 
                                   WHERE tm.team_id = ?
                                   ORDER BY tm.id ASC");
    foreach ($teamDetails as $team) {
        $tid = intval($team['team_id']);
        $member_stmt->bind_param("i", $tid);
        $member_stmt->execute();
        $res = $member_stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $r['team_id'] = $tid;
            $r['team_name'] = $team['team_name'] ?? '';
            $team_members_all[] = $r;
        }
    }
    $member_stmt->close();
}
?>

        <div class="player-roster">
            <?php if (!empty($team_members_all)): ?>
            <?php foreach ($team_members_all as $member): ?>
            <?php
                // รูปโปรไฟล์ (fallback ถ้าไม่มี)
                $profile_img = !empty($member['profile_img']) ? $member['profile_img'] : '../img/profile_placeholder.png';
                // ถ้า path ไม่มี prefixed .. ให้แนบ ../ (ปรับได้ตามรูปแบบใน DB)
                if (strpos($profile_img, '..') !== 0 && strpos($profile_img, '/') !== 0) {
                    $profile_img = '../' . ltrim($profile_img, '/');
                }

                // Riot ID (fallback ชื่อ-นามสกุล ถ้าว่าง)
                $riot_id = !empty($member['riot_id']) ? $member['riot_id'] : trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));

                // ค่าสถิติจำลอง (ค่าจำลองเท่านั้น)
                $kd  = number_format(mt_rand(80,200) / 100, 2); // ตัวอย่าง 0.80 - 2.00
                $kpr = number_format(mt_rand(120,400) / 100, 2); // ตัวอย่าง 1.20 - 4.00
                $adr = mt_rand(120, 300); // ตัวอย่าง ADR
            ?>
            <div class="player-card">
                <div class="player-avatar"
                    style="background-image: url('<?= htmlspecialchars($profile_img) ?>'); background-size: cover; background-position: center;">
                    <!-- ถ้ต้องการตัวอักษร fallback ให้ใช้ตัวแรกของ Riot ID -->
                    <span style="display:none;"><?= htmlspecialchars(substr($riot_id,0,1)) ?></span>
                </div>
                <div class="player-name"><?= htmlspecialchars($riot_id) ?></div>
                <div class="player-role"><?= htmlspecialchars($member['role_in_team'] ?: 'Player') ?></div>
                <button type="button" class="main-btn"
                    onclick="window.location.href='player_analysis.php?riot_id=<?= urlencode($member['riot_id']) ?>&region=<?= urlencode($member['region']) ?>'">วิเคราะห์ผู้เล่น</button>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p>ไม่พบสมาชิกในทีม — โปรดเพิ่มสมาชิกหรือตรวจสอบการตั้งค่าทีม</p>
            <?php endif; ?>
        </div>


        <h2 class="section-title" id="history">ประวัติแมตช์ล่าสุดของทีม (10 แมตช์ล่าสุด)</h2>

        <div class="card match-history">
            <table>
                <thead>
                    <tr>
                        <th>Map</th>
                        <th>วันที่เริ่ม</th>
                        <th>ทีม</th>
                        <th>ผล</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
            if (!empty($all_match_history)): 
                // เรียงลำดับตาม started_at จากใหม่ไปเก่า
                usort($all_match_history, function($a, $b) {
                    return strtotime($b['started_at'] ?? 0) - strtotime($a['started_at'] ?? 0);
                });
                
                // จำกัดจำนวนแมตช์ที่แสดงเป็น 10 แมตช์
                $recent_matches = array_slice($all_match_history, 0, 10);
                
                foreach ($recent_matches as $m):
                    $started = isset($m['started_at']) ? date('d/m/Y H:i', strtotime($m['started_at'])) : '-';
                    $team_for_row = htmlspecialchars($m['team_name'] ?? '-');
                    $map = '-';

                    // ดึงข้อมูล Map จาก get_match_detail.php
                    $matchid = null;
                    if (!empty($m['match_id'])) {
                        $matchid = $m['match_id'];
                    } elseif (!empty($m['id'])) {
                        $matchid = $m['id'];
                    }

                    if ($matchid) {
                        $matchApi = "http://{$_SERVER['HTTP_HOST']}/VALPROJECT/leaderboard/get_match_detail.php?matchid=" . urlencode($matchid);
                        $matchRes = @file_get_contents($matchApi);
                        if ($matchRes !== false) {
                            $matchJson = json_decode($matchRes, true);
                            if (isset($matchJson['map'])) {
                                $map = $matchJson['map'];
                            }
                        }
                    }

                    // คำนวณผลแพ้ชนะ - default เป็นแพ้
                    $result = '<span style="color: #ff0000;">แพ้</span>';
                    if (isset($m['points_before']) && isset($m['points_after'])) {
                        if ($m['points_after'] > $m['points_before']) {
                            $result = '<span style="color: #00ff00;">ชนะ</span>';
                        }
                    }

                    $detail_url = $matchid ? "../team_analytics/match_detail.php?matchid=" . urlencode($matchid) : null;
                    ?>
                    <tr style="cursor:pointer;" onclick="window.open('<?= $detail_url ?>', '_blank')">
                        <td><?= htmlspecialchars($map) ?></td>
                        <td><?= $started ?></td>
                        <td><?= $team_for_row ?></td>
                        <td><?= $result ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="4">ไม่มีประวัติแมตช์จากทีม Premier</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>




    <?php
// ย้ายตัวแปร debug และฟังก์ชันที่เกี่ยวข้องขึ้นมาไว้ก่อนส่วนแสดงผลของ team premier
// (เอาโค้ดนี้มาจากตอนท้ายของไฟล์ เพื่อให้เรียกใช้ได้เมื่อแสดงผล)
$debug_data = [];

// ฟังก์ชันสำหรับดึงข้อมูลทีม Premier และประวัติการเล่น (ใช้ /premier/search ก่อน)
function fetchPremierTeamData($team_name, $team_tag, $api_key) {
    global $debug_data;

    $debug_data['last_request_input'][] = [
        'team_name_raw' => $team_name,
        'team_tag_raw'  => $team_tag
    ];

    $search_url = "https://api.henrikdev.xyz/valorant/v1/premier/search?name=" . rawurlencode($team_name) . "&tag=" . rawurlencode($team_tag);
    $ch = curl_init($search_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: {$api_key}",
        "Accept: application/json"
    ]);
    $raw_search = curl_exec($ch);
    $err_search = curl_error($ch);
    $http_code_search = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $debug_data['search_request'][] = [
        'url' => $search_url,
        'http_code' => $http_code_search,
        'error' => $err_search ?: null,
        'raw_response' => $raw_search ?: null,
    ];

    $search_parsed = $raw_search ? json_decode($raw_search, true) : null;
    $debug_data['search_parsed'][] = $search_parsed;

    if (is_array($search_parsed) && !empty($search_parsed['data']) && is_array($search_parsed['data'])) {
        $first = $search_parsed['data'][0];
        $debug_data['used_search_result'] = $first;
        return $first;
    }

    // Fallback: call by name/tag directly
    $name_enc = rawurlencode($team_name);
    $tag_enc  = rawurlencode($team_tag);
    $api_url = "https://api.henrikdev.xyz/valorant/v1/premier/{$name_enc}/{$tag_enc}";

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: {$api_key}",
        "Accept: application/json"
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $debug_data['teams'][] = [
        'url' => $api_url,
        'http_code' => $http_code,
        'error' => $err ?: null,
        'raw_response' => $raw ?: null,
    ];

    if (!$raw) return null;
    $data = json_decode($raw, true);
    $debug_data['teams_parsed'][] = $data;

    if (!isset($data['status']) || !in_array($data['status'], [200, 1]) || !isset($data['data'])) {
        return null;
    }

    return $data['data'];
}

function fetchPremierMatchHistory($team_name, $team_tag, $api_key) {
    global $debug_data;

    $name_enc = rawurlencode($team_name);
    $tag_enc  = rawurlencode($team_tag);
    $history_url = "https://api.henrikdev.xyz/valorant/v1/premier/{$name_enc}/{$tag_enc}/history";

    $ch = curl_init($history_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: {$api_key}",
        "Accept: application/json"
    ]);

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $debug_data['history_requests'][] = [
        'url' => $history_url,
        'http_code' => $http_code,
        'error' => $err ?: null,
        'raw_response' => $raw ?: null,
    ];

    if (empty($raw)) {
        return [];
    }

    $data = json_decode($raw, true);
    $debug_data['history_parsed'][] = $data;

    // API sample returns status = 1 and data.league_matches[]
    if (!isset($data['status']) || !in_array($data['status'], [200, 1])) {
        return [];
    }

    if (isset($data['data']['league_matches']) && is_array($data['data']['league_matches'])) {
        return $data['data']['league_matches'];
    }

    return [];
}

// ตอนนี้แทรกส่วนแสดงผล team-premier-container ที่รวมทั้ง teamDetails และ premierTeamDetails ข้างบน
?>


</body>

</html>

<?php

?>
<script>
window.__ALL_MATCH_HISTORY =
    <?php echo json_encode($all_match_history ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
window.__TEAM_MEMBERS =
    <?php echo json_encode($team_members_all ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get Win Rate Chart canvas context
    const teamPerfCtx = document.getElementById('winRateChart').getContext('2d'); 

    // ใช้ประวัติแมตช์รวมแทนการเรียก v3/matches
    const matches = window.__ALL_MATCH_HISTORY || [];
    const members = window.__TEAM_MEMBERS || [];

    // Calculate win rate
    let wins = 0, total = 0;
    matches.forEach(m => {
        const pb = (typeof m.points_before !== 'undefined') ? parseInt(m.points_before) : (typeof m.pointsBefore !== 'undefined' ? parseInt(m.pointsBefore) : null);
        const pa = (typeof m.points_after !== 'undefined') ? parseInt(m.points_after) : (typeof m.pointsAfter !== 'undefined' ? parseInt(m.pointsAfter) : null);
        if (pb !== null && pa !== null) {
            total++;
            if (pa > pb) wins++;
        }
    });

    const winRate = total ? Math.round((wins / total) * 100) : 0;

    // Create Win Rate Doughnut Chart
    new Chart(teamPerfCtx, {
        type: 'doughnut',
        data: {
            labels: ['ชนะ', 'แพ้'],
            datasets: [{
                data: [wins, total - wins],
                backgroundColor: [
                    'rgba(0, 74, 134, 0.85)', 
                    'rgba(255, 255, 255, 0.85)'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#fff'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const sum = context.dataset.data.reduce((a,b) => a+b);
                            const percentage = Math.round((value/sum) * 100);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ใช้ประวัติแมตช์รวมแทนการเรียก v3/matches
    const matches = window.__ALL_MATCH_HISTORY || [];
    const members = window.__TEAM_MEMBERS || [];

    // helper: get started_at
    function getStartedAt(m) {
        return m.started_at || (m.metadata && m.metadata.started_at) || (m.data && m.data.metadata && m.data
            .metadata.started_at) || null;
    }

    // helper: get match id
    function getMatchId(m) {
        return m.match_id || m.id || m.matchId || (m.data && (m.data.match_id || m.data.matchId)) || null;
    }

    // helper: try extract player stats from known possible fields
    function extractPlayerStats(m) {
        // prefer top-level player_stats / playerStats
        let s = null;
        if (m.player_stats && typeof m.player_stats === 'object') s = m.player_stats;
        if (!s && m.playerStats && typeof m.playerStats === 'object') s = m.playerStats;

        // search in common arrays: players, all_players, data.players
        if (!s) {
            const arrays = [m.players, m.all_players, (m.data && m.data.players)];
            const pname = m.player_name || null;
            for (const arr of arrays) {
                if (Array.isArray(arr) && pname) {
                    for (const p of arr) {
                        const display = (p.name || p.displayName || p.playerName || p.player_name || '') + '';
                        if (display && pname && display.toLowerCase().includes(pname.toLowerCase())) {
                            // possible stats locations
                            if (p.stats) return p.stats;
                            if (p.player_stats) return p.player_stats;
                            if (p.character && p.character.stats) return p.character.stats;
                            return p;
                        }
                    }
                }
            }
        }

        // fallback: scan for kills/deaths/assists anywhere in object
        if (!s) {
            const json = JSON.stringify(m).toLowerCase();
            if (json.includes('"kills"') || json.includes('"deaths"') || json.includes('"assists"')) {
                // best-effort parse - not guaranteed
                try {
                    // deep search for first object that has kills/deaths
                    function deepFind(obj) {
                        if (obj && typeof obj === 'object') {
                            if ('kills' in obj || 'deaths' in obj || 'assists' in obj) return obj;
                            for (const k in obj) {
                                const found = deepFind(obj[k]);
                                if (found) return found;
                            }
                        }
                        return null;
                    }
                    const found = deepFind(m);
                    if (found) s = found;
                } catch (e) {
                    s = null;
                }
            }
        }

        return s || null;
    }

    // คำนวณ win rate โดยใช้ points_before/points_after ถ้ามี
    let wins = 0,
        total = 0;
    const kdas = []; // per-match K/D/A for player
    matches.forEach(m => {
        const pb = (typeof m.points_before !== 'undefined') ? parseInt(m.points_before) : (typeof m
            .pointsBefore !== 'undefined' ? parseInt(m.pointsBefore) : null);
        const pa = (typeof m.points_after !== 'undefined') ? parseInt(m.points_after) : (typeof m
            .pointsAfter !== 'undefined' ? parseInt(m.pointsAfter) : null);
        if (pb !== null && pa !== null) {
            total++;
            if (pa > pb) wins++;
        }
        // attempt to extract player stats
        const stats = extractPlayerStats(m);
        const kills = stats && (stats.kills || stats.KILLS || stats.kill) ? (stats.kills || stats
            .KILLS || stats.kill) : null;
        const deaths = stats && (stats.deaths || stats.DEATHS || stats.death) ? (stats.deaths || stats
            .DEATHS || stats.death) : null;
        const assists = stats && (stats.assists || stats.ASSISTS || stats.assist) ? (stats.assists ||
            stats.ASSISTS || stats.assist) : null;
        kdas.push({
            id: getMatchId(m) || ('m' + Math.random().toString(36).slice(2, 8)),
            started_at: getStartedAt(m),
            kills: kills !== null ? Number(kills) : null,
            deaths: deaths !== null ? Number(deaths) : null,
            assists: assists !== null ? Number(assists) : null
        });
    });

    const winRate = total ? Math.round((wins / total) * 100) : 0;

    // --- Team performance radar (แสดงค่า Win Rate + simple proxies) ---
    const radarValues = [
        winRate, // การโจมตี (ใช้ win rate เป็น proxy)
        Math.min(100, Math.round(winRate * 0.9)), // การป้องกัน
        Math.min(100, Math.round(winRate * 1.05)), // การประสานงาน
        Math.min(100, Math.round(winRate * 0.85)), // การใช้สกิล
        Math.min(100, Math.round(winRate * 1.1)) // เศรษฐกิจ
    ];

    // เปลี่ยนจาก Map Win Rate เป็นแสดง Win Rate (ชนะ vs แพ้) แบบ doughnut
    const winCount = wins;
    const totalCount = total;
    const perfLabels = ['ชนะ', 'แพ้'];
    const perfData = totalCount ? [winCount, Math.max(0, totalCount - winCount)] : [0, 1];
    const perfColors = ['rgba(0, 74, 134, 0.85)', 'rgba(255, 255, 255, 0.85)'];

    new Chart(teamPerfCtx, {
        type: 'doughnut',
        data: {
            labels: perfLabels,
            datasets: [{
                data: perfData,
                backgroundColor: perfColors,
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true, // เพิ่ม
            layout: { // เพิ่ม
                padding: {
                    left: 0,
                    right: 0,
                    top: 10,
                    bottom: 10
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    align: 'center' // เพิ่ม
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const label = ctx.label || '';
                            const value = ctx.parsed || 0;
                            const sum = perfData.reduce((a, b) => a + b, 0) || 1;
                            const pct = Math.round((value / sum) * 100);
                            return label + ': ' + value + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });

    // --- KDA per match chart (ใช้ค่า player K/D/A ถ้าไม่มี ใช้ค่า mock จากสมาชิกทีม) ---
    const kdaLabels = [];
    const killsData = [];
    const deathsData = [];
    const assistsData = [];

    // if we have per-match stats, use them (latest first)
    const realStats = kdas.filter(x => x.kills !== null || x.deaths !== null || x.assists !== null);
    if (realStats.length) {
        // order by started_at descending when available
        realStats.sort((a, b) => {
            const ta = a.started_at ? new Date(a.started_at).getTime() : 0;
            const tb = b.started_at ? new Date(b.started_at).getTime() : 0;
            return tb - ta;
        });
        realStats.forEach(s => {
            const label = s.started_at ? (new Date(s.started_at)).toLocaleString() : s.id;
            kdaLabels.push(label);
            killsData.push(s.kills || 0);
            deathsData.push(s.deaths || 0);
            assistsData.push(s.assists || 0);
        });
    } else {
        // fallback: use team members and random-ish numbers (still better than static hardcode)
        const names = members.map(m => m.riot_id || (m.first_name && m.last_name ? (m.first_name + ' ' + m
            .last_name) : ('Player' + m.user_id)));
        names.slice(0, 6).forEach(n => {
            kdaLabels.push(n);
            killsData.push(Math.floor(Math.random() * 20) + 5);
            deathsData.push(Math.floor(Math.random() * 18) + 5);
            assistsData.push(Math.floor(Math.random() * 12));
        });
    }

    const teamKdaCtx = document.getElementById('teamKdaChart').getContext('2d');
    new Chart(teamKdaCtx, {
        type: 'bar',
        data: {
            labels: kdaLabels,
            datasets: [{
                    label: 'ฆ่า',
                    data: killsData,
                    backgroundColor: 'rgba(76,175,80,0.8)'
                },
                {
                    label: 'ตาย',
                    data: deathsData,
                    backgroundColor: 'rgba(244,67,54,0.8)'
                },
                {
                    label: 'ช่วยเหลือ',
                    data: assistsData,
                    backgroundColor: 'rgba(33,150,243,0.8)'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
    });

    // --- Team composition chart: try to infer from matches (agent picks) ---
    const agentCounts = {};
    matches.forEach(m => {
        // try to find agent/character field in player object
        const arrays = [m.players, m.all_players, (m.data && m.data.players)];
        const pname = m.player_name || null;
        for (const arr of arrays) {
            if (Array.isArray(arr) && pname) {
                for (const p of arr) {
                    const display = (p.name || p.displayName || p.playerName || '') + '';
                    if (display && pname && display.toLowerCase().includes(pname.toLowerCase())) {
                        const agent = p.character || p.agent || p.actor || p.displayIcon || null;
                        if (agent && typeof agent === 'string') {
                            if (!agentCounts[agent]) agentCounts[agent] = 0;
                            agentCounts[agent]++;
                        }
                    }
                }
            }
        }
    });

    // prepare data for chart
    const compLabels = Object.keys(agentCounts);
    const compData = compLabels.map(l => agentCounts[l] || 0);

    const teamCompCtx = document.getElementById('teamCompositionChart').getContext('2d');
    new Chart(teamCompCtx, {
        type: 'doughnut',
        data: {
            labels: compLabels,
            datasets: [{
                data: compData,
                backgroundColor: compLabels.map((_, i) =>
                    `hsl(${(i / compLabels.length) * 360}, 70%, 50%)`),
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Map Win Rate Analysis
    const processMapStats = (matches) => {
        const mapStats = {};

        matches.forEach(match => {
            // Get map name from match data
            let mapName = '-';
            if (match.match_id || match.id) {
                const matchId = match.match_id || match.id;
                // Use the same API endpoint as in PHP code
                fetch(`../leaderboard/get_match_detail.php?matchid=${matchId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.map) {
                            mapName = data.map;
                        }
                    });
            }

            // Initialize map stats if not exists
            if (!mapStats[mapName]) {
                mapStats[mapName] = {
                    total: 0,
                    wins: 0
                };
            }

            // Count total games and wins
            mapStats[mapName].total++;

            // Check if match was won using points_before/points_after
            const pointsBefore = match.points_before || match.pointsBefore || 0;
            const pointsAfter = match.points_after || match.pointsAfter || 0;

            if (pointsAfter > pointsBefore) {
                mapStats[mapName].wins++;
            }
        });

        // Calculate win rates
        const labels = Object.keys(mapStats);
        const winRates = labels.map(map => {
            const stats = mapStats[map];
            return stats.total > 0 ? (stats.wins / stats.total) * 100 : 0;
        });

        return {
            labels,
            winRates
        };
    };

    // Create Map Win Rate Chart
    const mapWinRateCtx = document.getElementById('mapWinRateChart').getContext('2d');
    const mapStats = processMapStats(matches);

    new Chart(mapWinRateCtx, {
        type: 'bar',
        data: {
            labels: mapStats.labels,
            datasets: [{
                label: 'Win Rate (%)',
                data: mapStats.winRates,
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Win Rate (%)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Maps'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Win Rate: ${context.parsed.y.toFixed(1)}%`;
                        }
                    }
                }
            },
        },
    });
});
</script>
<script>
// สร้างฟังก์ชันดึงข้อมูลแมพ
async function getMapData(matchId) {
    try {
        const response = await fetch(`../leaderboard/get_match_detail.php?matchid=${matchId}`);
        const data = await response.json();
        return data.map || '-';
    } catch (error) {
        console.error('Error fetching map data:', error);
        return '-';
    }
}

// ปรับฟังก์ชัน processMapStats ให้เป็น async
async function processMapStats(matches) {
    const mapStats = {};

    // รอให้ดึงข้อมูลแมพทั้งหมดเสร็จ
    await Promise.all(matches.map(async (match) => {
        const matchId = match.match_id || match.id;
        const mapName = matchId ? await getMapData(matchId) : null;

        // ข้ามการบันทึกถ้า mapName เป็น '-' หรือ null
        if (mapName && mapName !== '-') {
            if (!mapStats[mapName]) {
                mapStats[mapName] = {
                    total: 0,
                    wins: 0
                };
            }

            mapStats[mapName].total++;

            const pointsBefore = match.points_before || match.pointsBefore || 0;
            const pointsAfter = match.points_after || match.pointsAfter || 0;

            if (pointsAfter > pointsBefore) {
                mapStats[mapName].wins++;
            }
        }
    }));

    // กรองเอาเฉพาะแมพที่มีข้อมูล
    const labels = Object.keys(mapStats).filter(map => map !== '-');
    const winRates = labels.map(map => {
        const stats = mapStats[map];
        return stats.total > 0 ? (stats.wins / stats.total) * 100 : 0;
    });

    return {
        labels,
        winRates
    };
}

// เรียกใช้งาน
document.addEventListener('DOMContentLoaded', async function() {
    const matches = window.__ALL_MATCH_HISTORY || [];
    const mapStats = await processMapStats(matches);

    const mapWinRateCtx = document.getElementById('mapWinRateChart').getContext('2d');
    new Chart(mapWinRateCtx, {
        type: 'bar',
        data: {
            labels: mapStats.labels,
            datasets: [{
                label: 'Win Rate (%)',
                data: mapStats.winRates,
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Win Rate (%)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Maps'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Win Rate: ${context.parsed.y.toFixed(1)}%`;
                        }
                    }
                }
            },
        },
    });
});
</script>