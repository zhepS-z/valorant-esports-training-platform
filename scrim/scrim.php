<?php 
session_start(); // เริ่มต้นเซสชัน
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
require_once '../auth/auth_check.php';
include '../utils/db.php'; // ใช้ connection จาก db.php

// Fetch scrims (only published and future scrim_start)
$scrims = [];
try {
    $sql = "
        SELECT s.scrim_id, s.team_id, s.scrim_start, s.format, s.map, s.slots, s.reserved_count,
               s.desired_rank,
               t.team_name, t.team_logo, t.region, t.rank, COALESCE(trc.avg_score,0) AS avg_score
        FROM scrims s
        JOIN teams t ON t.team_id = s.team_id
        LEFT JOIN team_rank_cache trc ON trc.team_id = t.team_id
        WHERE s.is_published = 1
          AND s.scrim_start > NOW()
        ORDER BY s.scrim_start ASC, trc.avg_score DESC
        LIMIT 100
    ";

    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $scrims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && ($conn instanceof mysqli || get_class($conn) === 'mysqli')) {
        $res = $conn->query($sql);
        while ($row = $res->fetch_assoc()) { $scrims[] = $row; }
    } elseif (isset($mysqli) && $mysqli instanceof mysqli) {
        $res = $mysqli->query($sql);
        while ($row = $res->fetch_assoc()) { $scrims[] = $row; }
    }
} catch (Exception $e) {
    // keep $scrims as empty on error
}

// หลัง include '../utils/db.php';
$user_id = $_SESSION['user_id'] ?? null;
$is_manager = false;
$user_team_id = null;
if ($user_id) {
    if (isset($pdo) && $pdo instanceof PDO) {
        $mgr = $pdo->prepare("SELECT team_id FROM teams WHERE manager_id = :uid LIMIT 1");
        $mgr->execute([':uid' => $user_id]);
        $user_team_id = $mgr->fetchColumn();
        if ($user_team_id !== false && $user_team_id !== null) $is_manager = true;
    } elseif (isset($conn) && ($conn instanceof mysqli || get_class($conn) === 'mysqli')) {
        $res = $conn->query("SELECT team_id FROM teams WHERE manager_id = ".intval($user_id)." LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) { $user_team_id = $row['team_id']; $is_manager = true; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scrim Room | Find Competitive Matches</title>

    <link href="/VALPROJECT/css/scrims.css" rel="stylesheet">

    <?php include '../utils/link.php'; ?>
</head>
<body>


    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="fw-bold">Find Scrim Matches</h1>
                <p class="text">Connect with teams of similar skill level for practice matches</p>
            </div>
            <!-- ปรับปุ่ม Create Scrim ให้เห็น/ทำงานได้เฉพาะ manager -->
            <div class="col-md-4 text-md-end">
                <?php if (!empty($is_manager)): ?>
                    <button class="btn btn-primary" id="btnCreateScrim" data-bs-toggle="modal" data-bs-target="#createScrimModal">
                        <i class="fas fa-plus me-2"></i>Create Scrim
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary" disabled title="Only team managers can create scrims">
                        <i class="fas fa-plus me-2"></i>Create Scrim
                    </button>
                <?php endif; ?>
            </div>
        </div>



        <!-- Scrim List -->
        <div class="card">
            <div class="card-header py-3" style="background: var(--bg-secondary);">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Available Scrims</h5>
                    <span class="badge bg-primary"><?php echo count($scrims); ?> Active</span>
                </div>
            </div>
            <div class="scrim-list-container">
                <div class="list-group list-group-flush">
                    <?php if (!empty($scrims)): ?>
                        <?php foreach ($scrims as $s):
                            // build logo path: use basename to normalize whatever is stored in DB
                            $team_logo_raw = $s['team_logo'] ?? '';
                            if (!empty($team_logo_raw)) {
                                $logoFile = basename($team_logo_raw); // e.g. "0cb690ae15af82fe.png"
                                $logo = '/VALPROJECT/uploads/team_logos/' . $logoFile;
                            } else {
                                $logo = null;  // Use icon instead
                            }
                            $logo = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
                            $teamName = htmlspecialchars($s['team_name']);
                            // since title column removed, use team name as display title
                            $displayTitle = $teamName;
                            $map = htmlspecialchars($s['map'] ?: 'Any');
                            $format = htmlspecialchars($s['format'] ?: 'Single');
                            $slots = (int)$s['slots'];
                            $reserved = (int)$s['reserved_count'];
                            $slots_left = max(0, $slots - $reserved);
                            $scrimStart = $s['scrim_start'];
                            $scrimTs = strtotime($scrimStart);
                            $is_future = $scrimTs > time();
                            $scrimId = (int)$s['scrim_id'];

                            // <-- ADDED: determine if current user (a manager) can reserve:
                            $can_reserve = false;
                            if (!empty($user_id) && !empty($is_manager) && !empty($user_team_id)) {
                                // manager must belong to a team and can't reserve own team's scrim
                                $can_reserve = ((int)$user_team_id !== (int)$s['team_id']);
                            }
                            // <-- END ADDED

                            // map desired_rank -> image filename
                            $desired = $s['desired_rank'] ?? 'Unranked';
                            $rankMap = [
                                'Unranked' => 'unranked.png',
                                'Iron'     => 'iron3.png',
                                'Bronze'   => 'bronze3.png',
                                'Silver'   => 'silver3.png',
                                'Gold'     => 'gold3.png',
                                'Platinum' => 'platinum3.png',
                                'Diamond'  => 'diamond3.png',
                                'Ascendant'=> 'ascendant3.png',
                                'Immortal' => 'immortal3.png',
                                'Radiant'  => 'radiant.png'
                            ];
                            $rankFile = $rankMap[$desired] ?? 'unranked.png';
                            $desiredImg = '/VALPROJECT/img/rank/' . $rankFile;
                            $desiredImgEsc = htmlspecialchars($desiredImg, ENT_QUOTES, 'UTF-8');
                            $desiredLabel = htmlspecialchars($desired, ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="list-group-item scrim-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <img src="<?php echo $logo; ?>" class="rounded-circle" alt="Team Logo" width="50" height="50">
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo $displayTitle; ?> — <small><?php echo $teamName; ?></small></h6>
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="badge bg-secondary me-2"><?php echo date('Y-m-d H:i', $scrimTs); ?></span>


                                            <!-- desired rank image -->
                                            <img src="<?php echo $desiredImgEsc; ?>" alt="<?php echo $desiredLabel; ?>" title="<?php echo $desiredLabel; ?>" class="rank-req-img me-2">

                                            
                                            <small class="text">Map: <?php echo $map; ?> • Format: <?php echo $format; ?></small>
                                        </div>
                                        <div class="small text-muted">
                                            Slots: <?php echo $slots_left; ?>/<?php echo $slots; ?> <?php if (!$is_future) echo '(expired)'; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <?php if ($is_future && $slots_left > 0 && $can_reserve): ?>
                                        <button class="btn btn-reserve btn-primary" data-scrim-id="<?php echo $scrimId; ?>">Reserve</button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <?php
                                            if (!$is_future) {
                                                echo $is_future ? 'Available' : 'Expired';
                                            } elseif ($slots_left <= 0) {
                                                echo 'Full';
                                            } elseif (empty($user_id) || empty($is_manager) || empty($user_team_id)) {
                                                echo 'Only team managers can reserve';
                                            } elseif ((int)$user_team_id === (int)$s['team_id']) {
                                                echo 'Cannot reserve your own team';
                                            } else {
                                                echo 'Unavailable';
                                            }
                                            ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-group-item">
                            <div class="text-center py-4">
                                <p class="mb-0">No scrims posted yet.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Scrim Modal -->
    <!-- แสดง modal เฉพาะ manager -->
    <?php if (!empty($is_manager)): ?>
    <div class="modal fade" id="createScrimModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
          <form id="createScrimForm" novalidate>
            <div class="modal-header">
              <h5 class="modal-title">Create Scrim</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Date & Time</label>
                <input name="scrim_start" type="datetime-local" class="vp-form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Format</label>
                <select name="format" class="vp-form-select">
                  <option value="Single">Single</option>
                  <option value="BO3">BO3</option>
                  <option value="MR24">MR24</option>
                  <option value="MR12">MR12</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Map</label>
                <select name="map" class="vp-form-select">
                  <option value="">Any</option>
                  <option>Abyss</option>
                  <option>Ascent</option>
                  <option>Bind</option>
                  <option>Breeze</option>
                  <option>Corrode</option>
                  <option>Fracture</option>
                  <option>Haven</option>
                  <option>Icebox</option>
                  <option>Lotus</option>
                  <option>Pearl</option>
                  <option>Split</option>
                  <option>Sunset</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Slots</label>
                <input name="slots" type="number" min="1" class="vp-form-control" value="5" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Desired Rank</label>
                <select name="desired_rank" class="vp-form-select">
                  <option value="Unranked">Unranked</option>
                  <option value="Iron">Iron</option>
                  <option value="Bronze">Bronze</option>
                  <option value="Silver">Silver</option>
                  <option value="Gold">Gold</option>
                  <option value="Platinum">Platinum</option>
                  <option value="Diamond">Diamond</option>
                  <option value="Ascendant">Ascendant</option>
                  <option value="Immortal">Immortal</option>
                  <option value="Radiant">Radiant</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Create</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // small animation
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Reserve handler (placeholder) - implement POST to API endpoint /scrim/api_reserve.php
        document.querySelectorAll('.btn-reserve').forEach(btn => {
            btn.addEventListener('click', function() {
                const scrimId = this.dataset.scrimId;
                if (!confirm('Confirm reserve for scrim #' + scrimId + '?')) return;
                fetch('/VALPROJECT/scrim/api.php?action=reserve', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({scrim_id: scrimId})
                })
                .then(response => {
                    // always read text, then try parse
                    return response.text().then(text => ({ ok: response.ok, text }));
                })
                .then(({ ok, text }) => {
                    let data = null;
                    try {
                        data = text ? JSON.parse(text) : null;
                    } catch (e) {
                        // invalid JSON — show server output for debugging
                        alert('Server returned non-JSON response:\n\n' + text);
                        throw new Error('Invalid JSON');
                    }
                    if (!data) throw new Error('Empty response');
                    if (data.success) {
                        alert('Reserved');
                        location.reload();
                    } else {
                        alert(data.error || 'Server error: ' + JSON.stringify(data));
                    }
                })
                .catch(err => {
                    console.error('Reserve error:', err);
                    alert('Network/server error. Open DevTools → Network and check response for details.');
                });
            });
        });

        const form = document.getElementById('createScrimForm');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating...';
            const data = Object.fromEntries(new FormData(form).entries());
            // convert datetime-local to MySQL format
            if (data.scrim_start) {
              const dt = new Date(data.scrim_start);
              const pad = n => String(n).padStart(2,'0');
              data.scrim_start = dt.getFullYear() + '-' + pad(dt.getMonth()+1) + '-' + pad(dt.getDate()) + ' ' + pad(dt.getHours()) + ':' + pad(dt.getMinutes()) + ':00';
            }
            fetch('/VALPROJECT/scrim/api.php?action=create', {
              method: 'POST',
              headers: {'Content-Type':'application/json'},
              body: JSON.stringify(data)
            })
            .then(res => {
              if (!res.ok) throw new Error('HTTP ' + res.status);
              return res.json();
            })
            .then(json => {
              if (json.success) {
                location.reload();
              } else {
                alert(json.error || 'Failed to create');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create';
              }
            })
            .catch(err => {
              console.error(err);
              alert('Network/server error');
              submitBtn.disabled = false;
              submitBtn.textContent = 'Create';
            });
        });
    });
    </script>
</body>
</html>