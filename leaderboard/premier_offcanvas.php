<?php
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key

// เพิ่มฟังก์ชัน debug ไว้ด้านบนไฟล์ หลัง require
function debug_to_console($data) {
    $output = $data;
    if (is_array($output))
        $output = json_encode($output);
    echo "<script>console.log(" . json_encode($output) . ");</script>";
}

// รับค่าจาก GET
$name = $_GET['name'] ?? '';
$tag = $_GET['tag'] ?? '';
$region = $_GET['region'] ?? ''; // เพิ่มบรรทัดนี้


if (!$name || !$tag) {
    echo '<div class="alert alert-danger">ไม่พบข้อมูลทีมนี้</div>';
    exit;
}

// เรียก API ตรง
$url = "https://api.henrikdev.xyz/valorant/v1/premier/" . rawurlencode($name) . "/" . rawurlencode($tag);

// ถ้าต้องการใส่ API Key ใน header
$options = [
    "http" => [
        "header" => "Authorization: $api_key\r\n"
    ]
];
$context = stream_context_create($options);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
    exit;
}
$data = json_decode($response, true);

if (!isset($data['status']) || !in_array($data['status'], [1, 200]) || !isset($data['data'])) {
    echo '<div class="alert alert-danger">ไม่พบข้อมูลทีมนี้</div>';
    exit;
}

$team = $data['data'];

// ดึง match history ด้วย team id
$history = [];
if (!empty($team['id'])) {
    $historyUrl = "https://api.henrikdev.xyz/valorant/v1/premier/" . urlencode($team['id']) . "/history";
    $historyResponse = @file_get_contents($historyUrl, false, $context);
    if ($historyResponse !== false) {
        $historyJson = json_decode($historyResponse, true);
        if (isset($historyJson['status']) && $historyJson['status'] == 200 && isset($historyJson['data']['league_matches'])) {
            $history = $historyJson['data']['league_matches'];
        }
    }
}

// Mapping ชื่อ conference
$conferenceNames = [
    "AP_ASIA" => "Asia",
    "AP_JAPAN" => "Japan",
    "AP_OCEANIA" => "Oceania",
    "AP_SOUTH_ASIA" => "South Asia",
    "EU_CENTRAL_EAST" => "Central East",
    "EU_WEST" => "West",
    "EU_MIDDLE_EAST" => "Middle East",
    "EU_TURKEY" => "Turkey",
    "NA_US_EAST" => "US East",
    "NA_US_WEST" => "US West",
    "LATAM_NORTH" => "LATAM North",
    "LATAM_SOUTH" => "LATAM South",
    "BR_BRAZIL" => "Brazil",
    "KR_KOREA" => "Korea"
];

// Mapping ชื่อ division
function getDivisionName($division) {
    if ($division >= 16 && $division <= 20) return "Elite " . ($division - 15);
    if ($division >= 11 && $division <= 15) return "Advanced " . ($division - 10);
    if ($division >= 6 && $division <= 10) return "Intermediate " . ($division - 5);
    if ($division >= 1 && $division <= 5) return "Open " . $division;
    return "Division " . $division;
}

// แปลงชื่อ Conference และ Division
$confDisplay = $team['placement']['conference'] ?? '-';
if (isset($conferenceNames[$confDisplay])) $confDisplay = $conferenceNames[$confDisplay];
$divDisplay = $team['placement']['division'] ?? '-';
if (is_numeric($divDisplay)) $divDisplay = getDivisionName($divDisplay);
$placeDisplay = $team['placement']['place'] ?? '-';
if (is_numeric($placeDisplay)) $placeDisplay = intval($placeDisplay) + 1;
?>
<div class="text-center mb-3">
  <?php if (!empty($team['customization']['image'])): ?>
    <img src="<?= htmlspecialchars($team['customization']['image']) ?>" alt="logo" style="height:64px;width:64px;border-radius:10px;">
  <?php endif; ?>
  <h2><?= htmlspecialchars($team['name']) ?> <span class="tag">#<?= htmlspecialchars($team['tag']) ?></span></h2>
</div>
<table class="table table mt-3">
  <tr><th>Enrolled</th><td><?= !empty($team['enrolled']) ? 'Yes' : 'No' ?></td></tr>
  <tr><th>Points</th><td><?= $team['placement']['points'] ?? '-' ?></td></tr>
  <tr><th>Conferences</th><td><?= $confDisplay ?></td></tr>
  <tr><th>Division</th><td><?= $divDisplay ?></td></tr>
  <tr><th>Place</th><td><?= $placeDisplay ?></td></tr>
  <tr><th>Wins</th><td><?= $team['stats']['wins'] ?? '-' ?></td></tr>
  <tr><th>Losses</th><td><?= $team['stats']['losses'] ?? '-' ?></td></tr>
  <tr><th>Matches</th><td><?= $team['stats']['matches'] ?? '-' ?></td></tr>
</table>

<h4>Match History</h4>
<?php if (!empty($history)): ?>
<div style="max-height:250px;overflow:auto; background:#01111c;">
  <table class="table" style="background:#01111c; color:#fff; border-color:#01111c;">
    <thead>
      <tr>
        <th>Map</th>
        <th>Date</th>
        <th>Score</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // เรียงลำดับใหม่ล่าสุดก่อน
      usort($history, function($a, $b) {
          return strtotime($b['started_at'] ?? 0) <=> strtotime($a['started_at'] ?? 0);
      });

      foreach ($history as $match) {
          $map = '-';
          $teamScore = '-';
          $oppScore = '-';
          $date = !empty($match['started_at']) ? date('M d Y', strtotime($match['started_at'])) : '-';
          $teamName = $team['name'];
          $matchid = $match['id'] ?? '-';

          // Update API URL to remove region parameter
          $matchApi = "http://{$_SERVER['HTTP_HOST']}/VALPROJECT/leaderboard/get_match_detail.php?matchid=" . urlencode($match['id']);
          
          $matchRes = @file_get_contents($matchApi);
          if ($matchRes !== false) {
              $matchJson = json_decode($matchRes, true);
              
              // Check for map data in the correct location
              if (isset($matchJson['map'])) {
                  $map = $matchJson['map'];
              }
          }

          // Fallback สำหรับคะแนน
          if ($teamScore === '-' && isset($match['points_after'])) {
              $teamScore = $match['points_after'];
          }

          $scoreDisplay = $teamScore;
          if ($oppScore !== '-' && $oppScore !== '' && $oppScore !== null) {
              $scoreDisplay .= ' - ' . $oppScore;
          }
          ?>
          <tr style="cursor:pointer;" onclick="window.open('match_detail.php?matchid=<?= urlencode($matchid) ?>', '_blank')">
            <td><?= htmlspecialchars($map) ?></td>
            <td><?= htmlspecialchars($date) ?></td>
            <td><?= htmlspecialchars($scoreDisplay) ?></td>
          </tr>
      <?php } ?>
    </tbody>
  </table>
</div>
<style>
  .offcanvas-body .table,
  .offcanvas-body .table th,
  .offcanvas-body .table td {
    border-color: #01111c !important;
  }
</style>
<?php else: ?>
<p>ไม่พบประวัติการแข่งขัน</p>
<?php endif; ?>
<br>
<h4>Members</h4><br>
<ul class="member-list">
  <?php if (!empty($team['member'])): ?>
    <?php foreach ($team['member'] as $m): ?>
      <?php
        $riot_id = urlencode($m['name']) . '%23' . urlencode($m['tag']);
        $region = htmlspecialchars($region);
        $is_captain = !empty($m['role']) && strtolower($m['role']) === 'captain';
      ?>
      <li>
        <a href="leaderboardplayer.php?riot_id=<?= $riot_id ?>&region=<?= $region ?>" target="_blank" class="member-link">
          <?= htmlspecialchars($m['name']) ?>
          <?php if ($is_captain): ?>
            <span style="color: gold;">&#x1F451;</span>
          <?php endif; ?>
          <?php if (!empty($m['tag'])): ?>
            <span style="color:#aaa;">#<?= htmlspecialchars($m['tag']) ?></span>
          <?php endif; ?>
        </a>
      </li>
    <?php endforeach; ?>
  <?php endif; ?>
</ul>
<style>
.member-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.member-list li {
  margin-bottom: 8px;
}
.member-link {
  display: block;
  padding: 10px 16px;
  border-radius: 10px;
  background: transparent;
  color: #fff;
  text-decoration: none;
  font-size: 1.1em;
  transition: background 0.2s, color 0.2s;
}
.member-link:hover, .member-link:focus {
  background: #16202a;
  color: #fff;
  text-decoration: none;
}
</style>