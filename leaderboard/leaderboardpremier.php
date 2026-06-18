<?php
session_start(); // เริ่มต้นเซสชัน
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key

// Default: ถ้าไม่ได้ส่ง division มา ให้เป็น Elite 5 (division 20)
$region = $_GET['region'] ?? 'ap';
$conference = $_GET['conference'] ?? 'AP_ASIA';
$division = isset($_GET['division']) ? $_GET['division'] : '20'; // default elite 5
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// ดึงข้อมูล leaderboard จาก API
$url = "https://api.henrikdev.xyz/valorant/v1/premier/leaderboard/{$region}/{$conference}/{$division}";
$options = [
    "http" => [
        "header" => "Authorization: $api_key\r\n"
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);
$allTeams = [];
$data = null;
if ($response !== false) {
    $data = json_decode($response, true);
    if (isset($data['status']) && $data['status'] == 200 && isset($data['data'])) {
        $allTeams = $data['data'];
    }
}

// Pagination
$teamsPerPage = 20;
$totalTeams = count($allTeams);
$totalPages = max(1, ceil($totalTeams / $teamsPerPage));
$startIndex = ($page - 1) * $teamsPerPage;
$teams = array_slice($allTeams, $startIndex, $teamsPerPage);

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
    if ($division >= 16 && $division <= 20) {
        return "Elite " . ($division - 15);
    } elseif ($division >= 11 && $division <= 15) {
        return "Advanced " . ($division - 10);
    } elseif ($division >= 6 && $division <= 10) {
        return "Intermediate " . ($division - 5);
    } elseif ($division >= 1 && $division <= 5) {
        return "Open " . $division;
    }
    return "Division " . $division;
}

$regionNames = [
    "ap" => "Asia Pacific",
    "na" => "North America",
    "eu" => "Europe",
    "kr" => "Korea",
    "br" => "Brazil",
    "latam" => "LATAM"
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valorant Premier Leaderboard</title>

    <link href="../css/leaderboard.css" rel="stylesheet">
    <?php include '../utils/link.php'; ?>
    <style>

    </style>
</head>

<body>
    <div class="container">
        <br>
        <h1>
            Premier Leaderboard for
            <?= htmlspecialchars($regionNames[$region] ?? $region) ?> ::
            <?= htmlspecialchars($conferenceNames[$conference] ?? $conference) ?> ::
            <?= htmlspecialchars(getDivisionName((int)$division)) ?>
        </h1>
        <br>
        <form method="GET" action="" class="d-flex gap-2 align-items-center">
            <label for="region" class="form-label mb-0">Region:</label>
            <select name="region" id="region" class="form-select" style="width: auto;" onchange="filterConference()">
                <option value="ap" <?= $region === 'ap' ? 'selected' : '' ?>>Asia Pacific</option>
                <option value="na" <?= $region === 'na' ? 'selected' : '' ?>>North America</option>
                <option value="eu" <?= $region === 'eu' ? 'selected' : '' ?>>Europe</option>
                <option value="kr" <?= $region === 'kr' ? 'selected' : '' ?>>Korea</option>
                <option value="br" <?= $region === 'br' ? 'selected' : '' ?>>Brazil</option>
                <option value="latam" <?= $region === 'latam' ? 'selected' : '' ?>>LATAM</option>
            </select>
            <label for="conference" class="form-label mb-0">Conference:</label>
            <select name="conference" id="conference" class="form-select" style="width: auto;">
                <!-- APAC -->
                <option value="AP_ASIA" data-region="ap">Asia</option>
                <option value="AP_JAPAN" data-region="ap">Japan</option>
                <option value="AP_OCEANIA" data-region="ap">Oceania</option>
                <option value="AP_SOUTH_ASIA" data-region="ap">South Asia</option>
                <!-- EU -->
                <option value="EU_CENTRAL_EAST" data-region="eu">Central East</option>
                <option value="EU_WEST" data-region="eu">West</option>
                <option value="EU_MIDDLE_EAST" data-region="eu">Middle East</option>
                <option value="EU_TURKEY" data-region="eu">Turkey</option>
                <!-- NA -->
                <option value="NA_US_EAST" data-region="na">US East</option>
                <option value="NA_US_WEST" data-region="na">US West</option>
                <!-- LATAM -->
                <option value="LATAM_NORTH" data-region="latam">LATAM North</option>
                <option value="LATAM_SOUTH" data-region="latam">LATAM South</option>
                <!-- BR -->
                <option value="BR_BRAZIL" data-region="br">Brazil</option>
                <!-- KR -->
                <option value="KR_KOREA" data-region="kr">Korea</option>
            </select>
            <label for="division" class="form-label mb-0">Division:</label>
            <select name="division" id="division" class="form-select" style="width: auto;">
                <optgroup label="Elite">
                    <?php for ($i = 16; $i <= 20; $i++): ?>
                        <option value="<?= $i ?>" <?= $division == $i ? 'selected' : '' ?>>Elite <?= $i-15 ?></option>
                    <?php endfor; ?>
                </optgroup>
                <optgroup label="Advanced">
                    <?php for ($i = 11; $i <= 15; $i++): ?>
                        <option value="<?= $i ?>" <?= $division == $i ? 'selected' : '' ?>>Advanced <?= $i-10 ?></option>
                    <?php endfor; ?>
                </optgroup>
                <optgroup label="Intermediate">
                    <?php for ($i = 6; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>" <?= $division == $i ? 'selected' : '' ?>>Intermediate <?= $i-5 ?></option>
                    <?php endfor; ?>
                </optgroup>
                <!-- ไม่แสดง Open -->
            </select>
            <button type="submit" class="btn-custom">See</button>
        </form>
        <script>
        function filterConference() {
            var region = document.getElementById('region').value;
            var conference = document.getElementById('conference');
            for (var i = 0; i < conference.options.length; i++) {
                var opt = conference.options[i];
                opt.style.display = (opt.getAttribute('data-region') === region) ? '' : 'none';
            }
            // ถ้า option ที่เลือกอยู่ไม่ตรง region ให้เลือก option แรกที่ตรง region
            if (conference.selectedOptions.length > 0 && conference.selectedOptions[0].style.display === 'none') {
                for (var i = 0; i < conference.options.length; i++) {
                    if (conference.options[i].getAttribute('data-region') === region) {
                        conference.selectedIndex = i;
                        break;
                    }
                }
            }
        }
        // เรียกตอนโหลดหน้า
        window.onload = filterConference;
        </script>
        <br>
        <div class="table-responsive">
            <table id="leaderboard" class="table">
                <thead>
                    <tr>
                        <th scope="col">Place</th>
                        <th scope="col">Team Name</th>
                        <th scope="col" class="text-center">Score</th>
                        <th scope="col" class="text-center">Wins</th>
                        <th scope="col" class="text-center">Losses</th>
                        <th scope="col" class="text-center">Matches</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($teams)) {
                        foreach ($teams as $team) {
                            // Debug สีที่รับมาจาก API
                            if (!empty($team['customization']['color'])) {
                                error_log("Team: " . $team['name'] . " | Color: " . $team['customization']['color']);
                            }

                            // กำหนดคลาสตามอันดับ
                            if (($team['ranking'] ?? -1) == 0) {
                                $rowClass = 'highlight-row highlight-gold';
                            } elseif (($team['ranking'] ?? -1) == 1) {
                                $rowClass = 'highlight-row highlight-silver';
                            } elseif (($team['ranking'] ?? -1) == 2) {
                                $rowClass = 'highlight-row highlight-bronze';
                            } else {
                                $rowClass = '';
                            }

                            echo "<tr class='{$rowClass}'>";
                            echo "<th scope='row'>" . (isset($team['ranking']) ? $team['ranking'] + 1 : '-') . "</th>";
                            echo "<td>";
                            if (!empty($team['customization']['image'])) {
                                echo "<img src='" . htmlspecialchars($team['customization']['image']) . "' alt='logo' style='height:32px;width:32px;vertical-align:middle;margin-right:8px;border-radius:6px;'>";
                            }
                            $teamName = urlencode($team['name']);
                            $teamTag = urlencode($team['tag']);
                            echo "<span class='team-detail-link' data-name='{$team['name']}' data-tag='{$team['tag']}' data-bs-toggle='offcanvas' data-bs-target='#teamOffcanvas' style='cursor:pointer;'>" . htmlspecialchars($team['name']) . "<span class='tag'>#" . htmlspecialchars($team['tag']) . "</span></span></td>";
                            echo "<td class='text-center'>" . ($team['score'] ?? '-') . "</td>";
                            echo "<td class='text-center'>" . ($team['wins'] ?? '-') . "</td>";
                            echo "<td class='text-center'>" . ($team['losses'] ?? '-') . "</td>";
                            echo "<td class='text-center'>" . (($team['wins'] ?? 0) + ($team['losses'] ?? 0)) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>ไม่พบข้อมูล Premier Teams</td></tr>";
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
                    <a class="page-link" href="?region=<?= $region ?>&conference=<?= $conference ?>&division=<?= $division ?>&page=<?= $page - 1 ?>" aria-label="Previous">
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
                    <a class="page-link" href="?region=<?= $region ?>&conference=<?= $conference ?>&division=<?= $division ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?region=<?= $region ?>&conference=<?= $conference ?>&division=<?= $division ?>&page=<?= $page + 1 ?>" aria-label="Next">
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

    <!-- Offcanvas for Team Detail -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="teamOffcanvas" aria-labelledby="teamOffcanvasLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="teamOffcanvasLabel">Team Detail</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body" id="teamOffcanvasBody">
        <div class="text-center">
          <div class="spinner-border" role="status"></div>
          <span>Loading...</span>
        </div>
      </div>
    </div>
    <script>
    // ส่ง mapping จาก PHP ไป JS
    const conferenceNames = <?= json_encode($conferenceNames) ?>;
    function getDivisionNameJS(division) {
        division = parseInt(division);
        if (division >= 16 && division <= 20) return "Elite " + (division - 15);
        if (division >= 11 && division <= 15) return "Advanced " + (division - 10);
        if (division >= 6 && division <= 10) return "Intermediate " + (division - 5);
        if (division >= 1 && division <= 5) return "Open " + division;
        return "Division " + division;
    }
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
  const offcanvas = document.getElementById('teamOffcanvas');
  const offcanvasBody = document.getElementById('teamOffcanvasBody');
  let currentController = null;

  document.querySelectorAll('.team-detail-link').forEach(btn => {
    btn.addEventListener('click', function() {
      const name = this.getAttribute('data-name');
      const tag = this.getAttribute('data-tag');
      const region = "<?= $region ?>"; // ส่ง region ไปด้วย
      offcanvasBody.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"></div> <span>Loading...</span></div>`;

      if (currentController) currentController.abort();
      currentController = new AbortController();

      // เปลี่ยน fetch ไปที่ premier_offcanvas.php
      fetch(`premier_offcanvas.php?name=${name}&tag=${tag}&region=${region}`, { signal: currentController.signal })
        .then(res => res.text())
        .then(html => {
          offcanvasBody.innerHTML = html;
        })
        .catch(e => {
          if (e.name !== 'AbortError') {
            offcanvasBody.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>`;
          }
        });
    });
  });
});
</script>
</body>

</html>