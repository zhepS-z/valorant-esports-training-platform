<?php
// ============================================================
// MOCK DATA — แทน DB / API calls ทั้งหมด
// ============================================================

$teamDetails = [
    [
        'team_id'    => 1,
        'team_name'  => 'Phantom FC',
        'abbreviation'=> 'PHM',
        'team_logo'  => '../images/team_logo_placeholder.png',
    ]
];

$premierTeamDetails = [
    [
        'team_name'  => 'Phantom Premier',
        'team_tag'   => 'PHMPR',
        'first_name' => 'สมชาย',
        'last_name'  => 'ใจดี',
    ]
];

// Mock สมาชิกทีม
$team_members_all = [
    ['user_id'=>1, 'riot_id'=>'Phantom#7890',    'profile_img'=>'', 'first_name'=>'สมชาย',   'last_name'=>'ใจดี',    'region'=>'ap', 'role_in_team'=>'IGL / Controller', 'team_id'=>1, 'team_name'=>'Phantom FC'],
    ['user_id'=>2, 'riot_id'=>'ShadowBlade#TH1', 'profile_img'=>'', 'first_name'=>'สมหญิง',  'last_name'=>'มั่นคง',  'region'=>'ap', 'role_in_team'=>'Duelist',          'team_id'=>1, 'team_name'=>'Phantom FC'],
    ['user_id'=>3, 'riot_id'=>'Specter#2233',     'profile_img'=>'', 'first_name'=>'วิชัย',   'last_name'=>'เก่งกาจ', 'region'=>'ap', 'role_in_team'=>'Initiator',        'team_id'=>1, 'team_name'=>'Phantom FC'],
    ['user_id'=>4, 'riot_id'=>'NightOwl#TH99',   'profile_img'=>'', 'first_name'=>'ประเสริฐ','last_name'=>'ดีมาก',   'region'=>'ap', 'role_in_team'=>'Sentinel',         'team_id'=>1, 'team_name'=>'Phantom FC'],
    ['user_id'=>5, 'riot_id'=>'DarkRift#6699',   'profile_img'=>'', 'first_name'=>'กมล',     'last_name'=>'สุขใจ',   'region'=>'ap', 'role_in_team'=>'Flex',             'team_id'=>1, 'team_name'=>'Phantom FC'],
];

// Mock premier team icon
$premier_team_icons = [
    'Phantom Premier' => 'https://i.imgur.com/placeholder.png'
];

// Mock ประวัติแมตช์
// ปรับให้แต่ละแมพมี win rate ต่างกันชัดเจน
$all_match_history = [
    // Pearl: 80% win rate (4W-1L)
    ['match_id'=>'mock-001', 'started_at'=>'2026-03-28 20:15:00', 'team_name'=>'Phantom Premier', 'points_before'=>800,  'points_after'=>900,  'map'=>'Pearl'],
    ['match_id'=>'mock-001-1', 'started_at'=>'2026-03-28 20:00:00', 'team_name'=>'Phantom FC',      'points_before'=>750,  'points_after'=>850,  'map'=>'Pearl'],
    ['match_id'=>'mock-001-2', 'started_at'=>'2026-03-27 20:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>700,  'points_after'=>800,  'map'=>'Pearl'],
    ['match_id'=>'mock-001-3', 'started_at'=>'2026-03-26 20:00:00', 'team_name'=>'Phantom FC',      'points_before'=>650,  'points_after'=>750,  'map'=>'Pearl'],
    ['match_id'=>'mock-001-4', 'started_at'=>'2026-03-25 20:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>600,  'points_after'=>500,  'map'=>'Pearl'],
    
    // Bind: 60% win rate (3W-2L)
    ['match_id'=>'mock-002', 'started_at'=>'2026-03-27 18:42:00', 'team_name'=>'Phantom Premier', 'points_before'=>900,  'points_after'=>1000, 'map'=>'Bind'],
    ['match_id'=>'mock-002-1', 'started_at'=>'2026-03-26 18:00:00', 'team_name'=>'Phantom FC',      'points_before'=>850,  'points_after'=>750,  'map'=>'Bind'],
    ['match_id'=>'mock-002-2', 'started_at'=>'2026-03-25 18:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>800,  'points_after'=>900,  'map'=>'Bind'],
    ['match_id'=>'mock-002-3', 'started_at'=>'2026-03-24 18:00:00', 'team_name'=>'Phantom FC',      'points_before'=>900,  'points_after'=>800,  'map'=>'Bind'],
    ['match_id'=>'mock-002-4', 'started_at'=>'2026-03-23 18:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>750,  'points_after'=>850,  'map'=>'Bind'],
    
    // Haven: 40% win rate (2W-3L)
    ['match_id'=>'mock-003', 'started_at'=>'2026-03-25 21:05:00', 'team_name'=>'Phantom Premier', 'points_before'=>700,  'points_after'=>800,  'map'=>'Haven'],
    ['match_id'=>'mock-003-1', 'started_at'=>'2026-03-24 21:00:00', 'team_name'=>'Phantom FC',      'points_before'=>800,  'points_after'=>700,  'map'=>'Haven'],
    ['match_id'=>'mock-003-2', 'started_at'=>'2026-03-23 21:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>750,  'points_after'=>650,  'map'=>'Haven'],
    ['match_id'=>'mock-003-3', 'started_at'=>'2026-03-22 21:00:00', 'team_name'=>'Phantom FC',      'points_before'=>700,  'points_after'=>600,  'map'=>'Haven'],
    ['match_id'=>'mock-003-4', 'started_at'=>'2026-03-21 21:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>650,  'points_after'=>750,  'map'=>'Haven'],
    
    // Ascent: 20% win rate (1W-4L)
    ['match_id'=>'mock-004', 'started_at'=>'2026-03-23 19:30:00', 'team_name'=>'Phantom Premier', 'points_before'=>600,  'points_after'=>700,  'map'=>'Ascent'],
    ['match_id'=>'mock-004-1', 'started_at'=>'2026-03-22 19:00:00', 'team_name'=>'Phantom FC',      'points_before'=>700,  'points_after'=>600,  'map'=>'Ascent'],
    ['match_id'=>'mock-004-2', 'started_at'=>'2026-03-21 19:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>650,  'points_after'=>550,  'map'=>'Ascent'],
    ['match_id'=>'mock-004-3', 'started_at'=>'2026-03-20 19:00:00', 'team_name'=>'Phantom FC',      'points_before'=>600,  'points_after'=>500,  'map'=>'Ascent'],
    ['match_id'=>'mock-004-4', 'started_at'=>'2026-03-19 19:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>550,  'points_after'=>450,  'map'=>'Ascent'],
    
    // Icebox: 100% win rate (5W-0L)
    ['match_id'=>'mock-005', 'started_at'=>'2026-03-21 17:55:00', 'team_name'=>'Phantom FC',      'points_before'=>500,  'points_after'=>600,  'map'=>'Icebox'],
    ['match_id'=>'mock-005-1', 'started_at'=>'2026-03-20 17:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>550,  'points_after'=>650,  'map'=>'Icebox'],
    ['match_id'=>'mock-005-2', 'started_at'=>'2026-03-19 17:00:00', 'team_name'=>'Phantom FC',      'points_before'=>600,  'points_after'=>700,  'map'=>'Icebox'],
    ['match_id'=>'mock-005-3', 'started_at'=>'2026-03-18 17:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>650,  'points_after'=>750,  'map'=>'Icebox'],
    ['match_id'=>'mock-005-4', 'started_at'=>'2026-03-17 17:00:00', 'team_name'=>'Phantom FC',      'points_before'=>700,  'points_after'=>800,  'map'=>'Icebox'],
    
    // Lotus: 50% win rate (2W-2L)
    ['match_id'=>'mock-006', 'started_at'=>'2026-03-19 20:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>600,  'points_after'=>500,  'map'=>'Lotus'],
    ['match_id'=>'mock-006-1', 'started_at'=>'2026-03-18 20:00:00', 'team_name'=>'Phantom FC',      'points_before'=>550,  'points_after'=>650,  'map'=>'Lotus'],
    ['match_id'=>'mock-006-2', 'started_at'=>'2026-03-17 20:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>600,  'points_after'=>700,  'map'=>'Lotus'],
    ['match_id'=>'mock-006-3', 'started_at'=>'2026-03-16 20:00:00', 'team_name'=>'Phantom FC',      'points_before'=>650,  'points_after'=>550,  'map'=>'Lotus'],
    
    // Split: 66% win rate (2W-1L)
    ['match_id'=>'mock-008', 'started_at'=>'2026-03-15 18:30:00', 'team_name'=>'Phantom Premier', 'points_before'=>300,  'points_after'=>400,  'map'=>'Split'],
    ['match_id'=>'mock-008-1', 'started_at'=>'2026-03-14 18:00:00', 'team_name'=>'Phantom FC',      'points_before'=>350,  'points_after'=>450,  'map'=>'Split'],
    ['match_id'=>'mock-008-2', 'started_at'=>'2026-03-13 18:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>400,  'points_after'=>300,  'map'=>'Split'],
    
    // Sunset: 0% win rate (0W-3L)
    ['match_id'=>'mock-007', 'started_at'=>'2026-03-17 21:15:00', 'team_name'=>'Phantom FC',      'points_before'=>400,  'points_after'=>300,  'map'=>'Sunset'],
    ['match_id'=>'mock-007-1', 'started_at'=>'2026-03-16 21:00:00', 'team_name'=>'Phantom Premier', 'points_before'=>450,  'points_after'=>350,  'map'=>'Sunset'],
    ['match_id'=>'mock-007-2', 'started_at'=>'2026-03-15 21:00:00', 'team_name'=>'Phantom FC',      'points_before'=>500,  'points_after'=>400,  'map'=>'Sunset'],
];

// เรียงจากใหม่ไปเก่า
usort($all_match_history, function($a, $b) {
    return strtotime($b['started_at']) - strtotime($a['started_at']);
});
$recent_matches = array_slice($all_match_history, 0, 10);

$show_premier_link = true; // mock role = manager
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($teamDetails[0]['team_name'] ?? 'Unknown Team'); ?> :: Analysis</title>
    <link rel="stylesheet" href="../css/analyse.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php // include '../utils/link.php'; ?>
</head>

<body>
    <div class="main-container">
        <section class="hero">
            <h2>วิเคราะห์แมตช์ Valorant สำหรับทีม</h2>
            <p>สำหรับทีม Valorant ที่ต้องการพัฒนากลยุทธ์ วิเคราะห์สถิติทีม และเพิ่มโอกาสในการชนะ</p>
            <a href="#dashboard" class="main-btn">เริ่มวิเคราะห์ทีมของคุณ</a>
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

            <?php if (!empty($premierTeamDetails)): ?>
                <?php foreach ($premierTeamDetails as $premierTeam): 
                    $tname = $premierTeam['team_name'];
                    $ttag  = $premierTeam['team_tag'];
                    // Mock team icon (แทน API call)
                    $team_icon = null; // ไม่มีรูปใน mock
                ?>
                <div class="team-card">
                    <div class="premier-avatar">
                        <?php if ($team_icon): ?>
                            <img src="<?= htmlspecialchars($team_icon) ?>" alt="Premier Team Logo"
                                style="height: 100px; width: 100px; border-radius: 6px;">
                        <?php else: ?>
                            <p style="text-align:center;font-size:12px;padding:8px;">Premier<br>Team</p>
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

        <div class="player-roster">
            <?php if (!empty($team_members_all)): ?>
                <?php foreach ($team_members_all as $member): ?>
                <?php
                    $profile_img = '../img/profile_placeholder.png';
                    $riot_id = !empty($member['riot_id'])
                        ? $member['riot_id']
                        : trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
                ?>
                <div class="player-card">
                    <div class="player-avatar"
                        style="background-image: url('<?= htmlspecialchars($profile_img) ?>'); background-size: cover; background-position: center;">
                        <span style="display:none;"><?= htmlspecialchars(substr($riot_id, 0, 1)) ?></span>
                    </div>
                    <div class="player-name"><?= htmlspecialchars($riot_id) ?></div>
                    <div class="player-role"><?= htmlspecialchars($member['role_in_team'] ?: 'Player') ?></div>
                    <button type="button" class="main-btn"
                        onclick="window.location.href='player_analysis.php?riot_id=<?= urlencode($member['riot_id']) ?>&region=<?= urlencode($member['region']) ?>'">
                        วิเคราะห์ผู้เล่น
                    </button>
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
                    <?php if (!empty($recent_matches)): ?>
                        <?php foreach ($recent_matches as $m):
                            $started    = isset($m['started_at']) ? date('d/m/Y H:i', strtotime($m['started_at'])) : '-';
                            $team_for_row = htmlspecialchars($m['team_name'] ?? '-');
                            $map        = htmlspecialchars($m['map'] ?? '-');
                            $matchid    = $m['match_id'] ?? null;

                            // คำนวณผลแพ้ชนะ
                            $result = '<span style="color: #ff0000;">แพ้</span>';
                            if (isset($m['points_before']) && isset($m['points_after'])) {
                                if ($m['points_after'] > $m['points_before']) {
                                    $result = '<span style="color: #00ff00;">ชนะ</span>';
                                }
                            }

                            $detail_url = $matchid ? "../team_analytics/match_detail.php?matchid=" . urlencode($matchid) : '#';
                        ?>
                        <tr style="cursor:pointer;" onclick="window.open('<?= $detail_url ?>', '_blank')">
                            <td><?= $map ?></td>
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

</body>
</html>

<script>
window.__ALL_MATCH_HISTORY =
    <?php echo json_encode($all_match_history ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
window.__TEAM_MEMBERS =
    <?php echo json_encode($team_members_all ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const matches = window.__ALL_MATCH_HISTORY || [];

    // คำนวณ win rate
    let wins = 0, total = 0;
    matches.forEach(m => {
        const pb = parseInt(m.points_before ?? m.pointsBefore ?? null);
        const pa = parseInt(m.points_after  ?? m.pointsAfter  ?? null);
        if (!isNaN(pb) && !isNaN(pa)) {
            total++;
            if (pa > pb) wins++;
        }
    });

    // Win Rate Doughnut
    const winRateCtx = document.getElementById('winRateChart').getContext('2d');
    new Chart(winRateCtx, {
        type: 'doughnut',
        data: {
            labels: ['ชนะ', 'แพ้'],
            datasets: [{
                data: [wins, total - wins],
                backgroundColor: ['rgba(0, 74, 134, 0.85)', 'rgba(255, 255, 255, 0.85)'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#fff' } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const sum = context.dataset.data.reduce((a, b) => a + b);
                            const percentage = Math.round((value / sum) * 100);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Map Win Rate Bar
    // รวมสถิติแต่ละแมพจาก mock data
    const mapStats = {};
    matches.forEach(m => {
        const mapName = m.map || '-';
        if (mapName === '-') return;
        if (!mapStats[mapName]) mapStats[mapName] = { total: 0, wins: 0 };
        mapStats[mapName].total++;
        const pb = parseInt(m.points_before ?? 0);
        const pa = parseInt(m.points_after  ?? 0);
        if (pa > pb) mapStats[mapName].wins++;
    });

    const mapLabels   = Object.keys(mapStats);
    const mapWinRates = mapLabels.map(map =>
        mapStats[map].total > 0 ? (mapStats[map].wins / mapStats[map].total) * 100 : 0
    );

    const mapWinRateCtx = document.getElementById('mapWinRateChart').getContext('2d');
    new Chart(mapWinRateCtx, {
        type: 'bar',
        data: {
            labels: mapLabels,
            datasets: [{
                label: 'Win Rate (%)',
                data: mapWinRates,
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
                    title: { display: true, text: 'Win Rate (%)' }
                },
                x: {
                    title: { display: true, text: 'Maps' }
                }
            },
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: {
                    callbacks: {
                        label: ctx => `Win Rate: ${ctx.parsed.y.toFixed(1)}%`
                    }
                }
            }
        }
    });
});
</script>