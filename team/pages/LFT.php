<?php 
session_start(); // เริ่มต้นเซสชัน
define('ACCESS', true);
require_once '../../utils/apikey.php';  // โหลด API Key
require_once '../../auth/auth_check.php';
include '../../utils/db.php'; // ใช้ connection จาก db.php


// add: helper to get rank image path
function team_rank_img($rank) {
    $map = [
        'unranked'  => 'unranked.png',
        'iron'      => 'iron1.png',
        'bronze'    => 'bronze1.png',
        'silver'    => 'silver1.png',
        'gold'      => 'gold1.png',
        'platinum'  => 'platinum1.png',
        'diamond'   => 'diamond1.png',
        'ascendant' => 'ascendant1.png',
        'immortal'  => 'immortal1.png',
        'radiant'   => 'radiant.png'
    ];
    $k = strtolower(trim((string)$rank));
    if ($k === '' || !isset($map[$k])) $k = 'unranked';
    return '../../img/rank/' . $map[$k];
}

// NEW: helper to get team logo path (checks common column names)
function get_team_logo(array $team): ?string {
    foreach (['team_logo','logo','logo_path','logoUrl'] as $c) {
        if (!empty($team[$c])) return $team[$c];
    }
    return null;
}

// NEW: normalize stored logo path to web URL (ensure points to /VALPROJECT/uploads/...)
function logo_url(string $storedPath = null): ?string {
    if (empty($storedPath)) return null;
    // absolute URL -> return as-is
    if (preg_match('#^https?://#i', $storedPath)) return $storedPath;
    // convert backslashes and trim
    $p = ltrim(str_replace('\\','/',$storedPath), '/');
    // remove 'team/' prefix if present (legacy paths)
    $p = preg_replace('#^team/#i', '', $p);
    // if already root-relative to VALPROJECT, ensure leading slash
    if (stripos($p, 'valproject/') === 0) return '/' . $p;
    // if already starts with 'uploads/', prefix project root
    if (stripos($p, 'uploads/') === 0) return '/VALPROJECT/' . $p;
    // fallback: prefix project root
    return '/VALPROJECT/' . $p;
}

$region = isset($_GET['region']) ? $_GET['region'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 4;
$offset = ($page - 1) * $perPage;

// เปลี่ยนการสร้างเงื่อนไขเพื่อรวม is_published
$conditions = ["teams.is_published = 1"];
if ($region !== 'all') {
    $conditions[] = "teams.region = '" . $conn->real_escape_string($region) . "'";
}

// NEW: handle search query
$search = isset($_GET['search']) ? trim($conn->real_escape_string($_GET['search'])) : '';
if (!empty($search)) {
    $conditions[] = "teams.team_name LIKE '%$search%'";
}

$where = 'WHERE ' . implode(' AND ', $conditions);

// Query teams
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM teams $where LIMIT $perPage OFFSET $offset";
$result = $conn->query($sql);

// จำนวนหน้าทั้งหมด
$totalRows = $conn->query("SELECT FOUND_ROWS() as total")->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// ตรวจสอบว่าผู้ใช้มีทีมอยู่หรือไม่ (สำหรับการซ่อน/ปิดปุ่มสร้างทีม)
$userHasTeam = false;
$myTeamId = null;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $uRes = $conn->query("SELECT team_id FROM users WHERE user_id = $uid LIMIT 1");
    if ($uRes && $uRes->num_rows > 0) {
        $myTeamId = $uRes->fetch_assoc()['team_id'];
        if (!empty($myTeamId)) $userHasTeam = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../css/LFT_LFP.css" rel="stylesheet">
    <title>Find Teams</title>

    <style>
    .team-card {
        position: relative;
    }

    /* Team logo at top-right — ensure full logo fits inside box */
    .team-card .team-logo {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 56px;
        height: 56px;
        display: block;
        object-fit: contain;
        /* fit whole image without cropping */
        object-position: center;
        /* center the image inside the box */
        border-radius: 6px;
        z-index: 6;
        background: #222;
        padding: 6px;
        /* optional inner padding so logo doesn't touch edges */
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.6);
    }

    /* Rank badge moved below the logo (right-aligned) */
    .team-card .team-rank {
        position: absolute;
        top: 90px;
        /* logo top (16) + logo height (56) + 6px gap */
        right: 16px;
        height: 32px;
        width: auto;
        z-index: 5;
    }

    /* delete button bottom-right of card */
    .team-card .delete-post-bottom {
        position: absolute;
        bottom: 16px;
        right: 16px;
        z-index: 15;
    }

    /* Player avatar: circular and fit */
    .player-avatar {
        width: 40px;
        height: 40px;
        object-fit: cover;
        /* crop to fill circle */
        object-position: center;
        border-radius: 50%;
        display: inline-block;
    }
    </style>
    <?php include '../../utils/link.php'; ?>
</head>

<body>
    <br>
    <div class="container">
        <!-- Search and Filter Section -->
        <div class="search-box">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-light border-dark"><i
                                    class="fas fa-search"></i></span>
                            <input type="text" class="form-control bg-dark text-light border-dark" name="search"
                                placeholder="Search teams..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-custom" type="submit">Search</button>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex gap-2">


                        <?php if (isset($_SESSION['user_id']) && $userHasTeam): ?>
                        <!-- ถ้ามีทีมแล้ว: แสดงปุ่ม disabled -->
                        <button class="btn btn-secondary w-100" disabled
                            title="คุณมีทีมแล้ว — ไม่สามารถสร้างทีมเพิ่มเติมได้">
                            <i class="fas fa-user-friends me-2"></i>In Team
                        </button>
                        <?php else: ?>

                        <!-- ถ้ายังไม่มีทีม: ปกติ -->
                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal"
                            data-bs-target="#createTeamModal">
                            <i class="fas fa-plus me-2"></i>Team
                        </button>
                        <?php endif; ?>

                        <!-- new button for LFP post -->
                        <button type="button" class="btn btn-outline-success w-100" data-bs-toggle="modal"
                            data-bs-target="#createPostModal">
                            <i class="fas fa-bullhorn me-2"></i> Post
                        </button>
                    </div>
                </div>
        </div>
        </form>

        <!-- Teams Listing -->
        <div class="row">
            <?php

        // ใช้เงื่อนไข $where ที่สร้างไว้ก่อนหน้านี้ (รวม is_published)
        // Pagination (already กำหนดข้างบน) - reuse $perPage, $offset
        // Query teams (ใช้ $where ที่มี is_published)
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM teams $where LIMIT $perPage OFFSET $offset";
        $result = $conn->query($sql);

        // จำนวนหน้าทั้งหมด
        $totalRows = $conn->query("SELECT FOUND_ROWS() as total")->fetch_assoc()['total'];
        $totalPages = ceil($totalRows / $perPage);

        if ($result && $result->num_rows > 0):
            while($team = $result->fetch_assoc()):
                // $teamCache = compute_team_average_rank_cached($team['team_id'], 6*3600); // 6 ชั่วโมง TTL
                // $avgTier = $teamCache['avg_tier'] ?? $team['rank'];

                // ใช้ rank ของหัวหน้าทีม (จาก riot_id + region) เป็นค่าแรก ถ้าไม่มีค่อย fallback ไปที่ค่าในตาราง teams
                $avgTier = $team['rank'];
                $liveRankImg = null;
                $managerId = (int)($team['manager_id'] ?? 0);
                if ($managerId) {
                    $mgrRes = $conn->query("SELECT riot_id, region FROM users WHERE user_id = $managerId LIMIT 1");
                    if ($mgrRes && $mgrRes->num_rows > 0) {
                        $mgr = $mgrRes->fetch_assoc();
                        $riotId = trim($mgr['riot_id'] ?? '');
                        // ถ้า user row ไม่มี region ให้ใช้ region ของทีมเป็น fallback
                        $mgrRegion = !empty($mgr['region']) ? $mgr['region'] : ($team['region'] ?? '');
                        if ($riotId !== '' && $mgrRegion !== '') {
                            $playerInfo = fetch_player_rank($riotId, $mgrRegion);
                            if (is_array($playerInfo)) {
                                if (!empty($playerInfo['tier'])) {
                                    $avgTier = $playerInfo['tier'];
                                }
                                if (!empty($playerInfo['img'])) {
                                    $liveRankImg = $playerInfo['img'];
                                }
                            }
                        }
                    }
                }
        ?>
            <div class="col-lg-6 mb-4">
                <div class="team-card p-4">
                    <!-- rank image (top-right) -->
                    <?php
                        // prepare team logo URL and rank image
                        $teamLogoStored = get_team_logo($team);
                        $teamLogoUrl = $teamLogoStored ? logo_url($teamLogoStored) : null;
                        $rankImg = $liveRankImg ?? team_rank_img($avgTier);
                    ?>
                    <!-- Team logo (top-right) -->
                    <?php if ($teamLogoUrl): ?>
                    <img src="<?= htmlspecialchars($teamLogoUrl) ?>" alt="Team logo" class="team-logo">
                    <?php else: ?>
                    <!-- optional fallback: small transparent placeholder or default image path -->
                    <img src="/VALPROJECT/img/default_team_logo.png" alt="Team logo" class="team-logo">
                    <?php endif; ?>

                    <!-- Rank badge below the logo -->
                    <img src="<?= htmlspecialchars($rankImg) ?>" alt="<?= htmlspecialchars($avgTier ?: 'Unranked') ?>"
                        class="team-rank">

                    <!-- Unpublish button for manager (top-right) -->
                    <?php if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$team['manager_id']): ?>
                    <button class="btn btn-sm btn-outline-danger unpublish-btn delete-post-bottom"
                        data-team-id="<?= (int)$team['team_id'] ?>" title="Delete Post">
                        <i class="fas fa-times me-1"></i> Delete Post
                    </button>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="team-name m-0"><?= htmlspecialchars($team['team_name']) ?></h3>
                        <span class="badge region-badge"><?= htmlspecialchars($team['region']) ?></span>
                    </div>
                    <p class="mb-3"><?= nl2br(htmlspecialchars($team['description'])) ?></p>
                    <div class="mb-3">
                        <h6 class="filter-label">Current Roster:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php
                            // Query สมาชิกในทีม
                            // include users.user_id so we can detect manager
                            $membersSql = "SELECT users.user_id, users.profile_img, team_members.role_in_team, users.first_name, users.last_name 
               FROM team_members 
               JOIN users ON team_members.user_id = users.user_id 
               WHERE team_members.team_id = " . (int)$team['team_id'];

                            $membersRes = $conn->query($membersSql);

                            $filledRoles = [];
                            if ($membersRes && $membersRes->num_rows > 0) {
                                while ($member = $membersRes->fetch_assoc()) {
                                    $role = $member['role_in_team'];
                                    $filledRoles[] = $role;
                        ?>
                            <div class="d-flex align-items-center me-3">
                                <?php
                                    // always show user's profile image for each member (including manager)
                                    // normalize profile_img path to absolute URL
                                    $profileImgPath = $member['profile_img'] ?? null;
                                    if (empty($profileImgPath)) {
                                        $avatar = null;  // Use icon instead
                                    } else if (preg_match('#^https?://#i', $profileImgPath)) {
                                        $avatar = $profileImgPath;
                                    } else {
                                        // remove 'team/' prefix if present and convert to absolute path
                                        $p = str_replace('\\', '/', $profileImgPath);
                                        $p = str_replace('team/', '', $p);
                                        $p = ltrim($p, '/');
                                        $avatar = '/VALPROJECT/img/' . $p;
                                    }
                                ?>
                                <img src="<?= htmlspecialchars($avatar) ?>" class="player-avatar me-2" alt="Player">
                                <span><?= htmlspecialchars($role) ?></span>
                            </div>
                            <br>
                            <?php
                                }
                            }
                            // Do not display "Looking for ..." per request — intentionally left blank
                        ?>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span><i class="fas fa-users me-1"></i>
                                <?= (int)$team['current_size'] ?>/<?= (int)$team['max_size'] ?></span>
                        </div>
                        <?php
                        // เฉพาะผู้เล่นที่ยังไม่มีทีมเท่านั้นที่เห็นปุ่มนี้
                        if (isset($_SESSION['user_id'])) {
                            $myUserId = $_SESSION['user_id'];
                            $myTeamId = null;
                            $userRes = $conn->query("SELECT team_id FROM users WHERE user_id = $myUserId");
                            if ($userRes && $userRes->num_rows > 0) {
                                $myTeamId = $userRes->fetch_assoc()['team_id'];
                            }
                            // ยังไม่มีทีมและไม่ใช่ manager ของทีมนี้
                            if (!$myTeamId && $team['manager_id'] != $myUserId) {
                        ?>
                        <button class="btn btn-sm btn-primary request-join-btn"
                            data-team-id="<?= $team['team_id'] ?>">Request to Join</button>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
            endwhile;
        else:
        ?>
            <div class="col-12">
                <div class="alert alert-warning">No teams found.</div>
            </div>
            <?php endif; ?>
        </div>
        <!-- Pagination -->
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?region=<?= urlencode($region) ?>&page=<?= $page - 1 ?>"
                        aria-label="Previous">
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
                    <a class="page-link" href="?region=<?= urlencode($region) ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?region=<?= urlencode($region) ?>&page=<?= $page + 1 ?>"
                        aria-label="Next">
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



    <!-- Create Team Modal -->
    <div class="modal fade" id="createTeamModal" tabindex="-1" aria-labelledby="createTeamModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="createTeamModalLabel">Create Team</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <!-- เพิ่ม enctype เพื่อรองรับการอัปโหลดไฟล์ -->
                <form id="createTeamForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="filter-label">Team Name</label>
                            <input type="text" class="form-control bg-secondary text-light border-dark" name="team_name"
                                required>
                        </div>

                        <!-- New: Team Abbreviation -->
                        <div class="mb-3">
                            <label class="filter-label">Team Abbreviation (ชื่อย่อ)</label>
                            <input type="text" class="form-control bg-secondary text-light border-dark" name="abbr"
                                maxlength="4" minlength="2" pattern="^[A-Za-z0-9]{2,4}$"
                                placeholder="2-4 characters (A-Z,0-9)" required>
                            <div class="form-text text-muted">ตัวอย่าง: AB1 — 2–4 ตัวอักษรหรือตัวเลข
                                (ไม่อนุญาตช่องว่าง/สัญลักษณ์)</div>
                        </div>

                        <div class="mb-3">
                            <label class="filter-label">Team Size (5-7 Member)</label>
                            <select class="form-select bg-secondary text-light border-dark" name="max_size" required>
                                <option value="5">5 — Players 5</option>
                                <option value="6">6 — Players 5 + Coach 1</option>
                                <option value="7" selected>7 — Players 5 + Coach 1 + Substitute 1 (Max)</option>
                            </select>
                            <div class="form-text text-muted">แนะนำ: ปกติทีมประกอบด้วย Players 5, Coach 1, Substitute 1
                                — Max 7 คน</div>
                        </div>

                        <!-- New: Team Logo upload -->
                        <div class="mb-3">
                            <label class="filter-label">Team Logo (optional)</label>
                            <input type="file" class="form-control bg-secondary text-light border-dark" name="logo"
                                accept="image/png, image/jpeg, image/webp">
                            <div class="form-text text-muted">ไฟล์ที่รองรับ: PNG, JPG, WebP — แนะนำขนาด 256x256</div>
                        </div>

                        <!-- Looking For Roles removed per request -->
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Post (LFT) Modal - moved Description here (label shows Description) -->
    <div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="createPostModalLabel">Create LFT Post</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="createPostForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="filter-label">Description</label>
                            <!-- now use description field (will update teams.description too) -->
                            <textarea class="form-control bg-secondary text-light border-dark" name="description"
                                rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });

    // ตัวแปร client-side ให้ตรวจสอบก่อนส่งฟอร์ม
    var userHasTeam = <?= (isset($userHasTeam) && $userHasTeam) ? 'true' : 'false' ?>;

    document.getElementById('createTeamForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (userHasTeam) {
            alert('คุณมีทีมอยู่แล้ว ไม่สามารถสร้างทีมใหม่ได้');
            return;
        }
        var form = this;
        var formData = new FormData(form);
        fetch('../api/create_team.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Team created successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(() => alert('Something went wrong.'));
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.request-join-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Send join request to this team?')) return;
                fetch('../api/request_join.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'team_id=' + encodeURIComponent(btn.dataset.teamId)
                    })
                    .then(async function(res) {
                        if (!res.ok) {
                            const text = await res.text();
                            throw new Error('HTTP ' + res.status + ': ' + text);
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert('Request sent!');
                            btn.disabled = true;
                            btn.textContent = 'Requested';
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(function(err) {
                        console.error('Request error:', err);
                        alert('Error: ' + err.message);
                    });
            });
        });
    });

    document.getElementById('createPostForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        // ensure we request publish when creating a post
        formData.append('is_published', '1');
        // use dedicated LFP endpoint (description field)
        fetch('../api/create_lft_post.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Post created successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(() => alert('Something went wrong.'));

    }); // <-- เพิ่มบรรทัดนี้ ปิด createPostForm listener ให้ครบ

    // Add: delete post handler (attach to buttons with .delete-post-btn and data-post-id)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest ? e.target.closest('.delete-post-btn') : null;
        if (!btn) return;
        if (!confirm('ยืนยันการลบโพสนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) return;
        var postId = btn.dataset.postId;
        if (!postId) {
            alert('Invalid post id');
            return;
        }

        fetch('../api/delete_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'post_id=' + encodeURIComponent(postId)
            })
            .then(async function(res) {
                var text = await res.text();
                var json = null;
                try {
                    json = text ? JSON.parse(text) : null;
                } catch (err) {
                    console.error('Invalid JSON from delete_post.php:', text);
                    alert('Server error');
                    return;
                }
                if (!res.ok) {
                    alert('Server error: ' + (json?.message || ('HTTP ' + res.status)));
                    return;
                }
                if (json.success) {
                    alert('Deleted successfully');
                    // ถ้าต้องการลบ element ทางฝั่ง client โดยไม่ reload: 
                    // btn.closest('.post-card')?.remove();
                    location.reload();
                } else {
                    alert('Error: ' + (json.message || 'Unable to delete'));
                }
            })
            .catch(function() {
                alert('Something went wrong.');
            });
    });

    // Unpublish / delete post by team (only manager can)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest ? e.target.closest('.unpublish-btn') : null;
        if (!btn) return;
        if (!confirm('ยืนยันยกเลิกโพสและซ่อนทีมนี้จากหน้า?')) return;
        var teamId = btn.dataset.teamId;
        if (!teamId) {
            alert('Invalid team id');
            return;
        }

        // ส่ง team_id และ is_published=0
        var body = 'team_id=' + encodeURIComponent(teamId) + '&is_published=0';

        fetch('../api/delete_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body
            })
            .then(res => res.json())
            .then(json => {
                if (json.success) {
                    alert('Team unpublished / post deleted.');
                    location.reload();
                } else {
                    alert('Error: ' + (json.message || 'Unable to unpublish'));
                }
            })
            .catch(() => alert('Something went wrong.'));
    });
    </script>
</body>

</html>
<?php
// per-request cache and HTTP helper (same approach as LFP.php)
$__rank_cache = [];

function call_api($url) {
    global $api_key;
    $opts = [
        "http" => [
            "header" => "Authorization: $api_key\r\nAccept: */*\r\n",
            "timeout" => 5
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return null;
    return $resp;
}

/**
 * Fetch player's current rank info from henrikdev API.
 * Returns array|null: ['tier' => 'Gold', 'img' => 'https://...'] or null on failure.
 */
function fetch_player_rank($riot_id, $region) {
    global $__rank_cache;
    if (empty($riot_id) || empty($region)) return null;
    $key = $region . '::' . $riot_id;
    if (isset($__rank_cache[$key])) return $__rank_cache[$key];

    $parts = explode('#', $riot_id);
    if (count($parts) !== 2) { $__rank_cache[$key] = null; return null; }
    $name = urlencode(trim($parts[0]));
    $tag  = urlencode(trim($parts[1]));
    $name = str_replace('+', '%20', $name);
    $tag  = str_replace('+', '%20', $tag);
    $tag  = str_replace('#', '%23', $tag);

    $url = "https://api.henrikdev.xyz/valorant/v1/mmr/" . urlencode($region) . "/$name/$tag";
    $resp = call_api($url);
    if (!$resp) { $__rank_cache[$key] = null; return null; }
    $json = json_decode($resp, true);
    if (!is_array($json) || empty($json['status']) || $json['status'] != 200) { $__rank_cache[$key] = null; return null; }

    $res = [
        'tier' => $json['data']['currenttierpatched'] ?? null,
        'img'  => $json['data']['images']['small'] ?? null
    ];
    $__rank_cache[$key] = $res;
    return $res;
}
// end fetch_player_rank function
?>
<?php
// move/ensure connection closed HERE after all DB usage
$conn->close();
?>