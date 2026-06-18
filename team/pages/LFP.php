<?php
session_start();
define('ACCESS', true);
require_once '../../utils/apikey.php';
require_once '../../auth/auth_check.php';
include '../../utils/db.php';

$hasTeam = false;
if (isset($_SESSION['user_id'])) {
    // ตรวจสอบ users.team_id ก่อน
    $stmt = $conn->prepare("SELECT team_id FROM users WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($u['team_id'])) {
        $hasTeam = true;
    } else {
        // ตรวจสอบ fallback: ตาราง team_members (ในกรณีที่การเป็นสมาชิกถูกเก็บไว้ที่นี่เท่านั้น)
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM team_members WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $cnt = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!empty($cnt['c']) && $cnt['c'] > 0) $hasTeam = true;
    }

    // ดึง region ของผู้ใช้ปัจจุบันจากฐานข้อมูล (ใช้ใน modal)
    $userRegion = null;
    $stmt = $conn->prepare("SELECT region FROM users WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $userRegion = $r['region'] ?? null;
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

// การค้นหา
$search = isset($_GET['search']) ? trim($conn->real_escape_string($_GET['search'])) : '';
$conditions = [];
if (!empty($search)) {
    $conditions[] = "p.position LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.riot_id LIKE '%$search%'";
}

// สร้างเงื่อนไข WHERE
$where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// ปรับแต่ง SQL Query
$sql = "SELECT p.*, p.rank AS post_rank, u.first_name, u.last_name, u.profile_img, u.region AS author_region, u.riot_id AS author_riot_id, t.rank AS author_rank
        FROM lfp_posts p
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN teams t ON u.team_id = t.team_id
        $where
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$res = $stmt->get_result();

// -> ใหม่: โหลดผลลัพธ์ลงใน array เพื่อหลีกเลี่ยงปัญหา pointer/การบริโภค
$posts = [];
if ($res instanceof mysqli_result) {
    $posts = $res->fetch_all(MYSQLI_ASSOC);
}

$total = $conn->query("SELECT COUNT(*) as c FROM lfp_posts")->fetch_assoc()['c'];
$totalPages = ceil($total / $perPage);

// DEBUG: ถ้าเรียก ?debug=1 ให้แสดงข้อมูลภายในแล้วหยุด (ไม่แสดง UI ปกติ)
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    // รีเซ็ต pointer ของ $res แล้วเก็บโพสต์เป็น array เพื่อแสดง
    $postsDebug = [];
    if ($res instanceof mysqli_result) {
        $res->data_seek(0);
        while ($r = $res->fetch_assoc()) $postsDebug[] = $r;
    }

    echo '<pre style="color:#0f0;background:#000;padding:12px;">';
    echo "hasTeam: "; var_export($hasTeam); echo "\n";
    echo "userRegion: "; var_export($userRegion); echo "\n";
    echo "total posts: "; var_export($total); echo "\n";
    echo "totalPages: "; var_export($totalPages); echo "\n\n";
    echo "sample posts:\n"; print_r($postsDebug);
    echo "\n__rank_cache (live API cache):\n";
    global $__rank_cache; print_r($__rank_cache);
    echo '</pre>';
    exit;
}

// เพิ่ม: ตัวช่วยในการแมปชื่อ rank -> เส้นทางรูปภาพ
function rank_to_img($rank, $userId) {
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
    // ใช้ user_id เป็น seed เพื่อให้ไอคอน variant คงที่ต่อผู้ใช้
    $seed = crc32($userId);
    $img = '../../img/rank/' . $map[$k];
    return $img;
}

// ตัวช่วยในการทำให้เส้นทาง profile_img เป็น URL แบบสมบูรณ์
function profile_img_url($storedPath = null) {
    if (empty($storedPath)) return null;  // Use icon instead
    // ถ้าเป็น URL แบบสมบูรณ์อยู่แล้ว ให้คืนค่าเดิม
    if (preg_match('#^https?://#i', $storedPath)) return $storedPath;
    // แปลง backslashes เป็น forward slashes
    $p = str_replace('\\', '/', $storedPath);
    // ลบ slashes ที่นำหน้าและคำนำหน้า 'team/' ถ้ามี
    $p = ltrim(str_replace('team/', '', $p), '/');
    // คืนค่าเป็นเส้นทางสมบูรณ์จาก root ของโปรเจกต์
    return '/VALPROJECT/img/' . $p;
}

// ตัวช่วย HTTP แบบง่ายโดยใช้ api key ของโปรเจกต์
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

// แคชต่อคำขอเพื่อหลีกเลี่ยงการเรียก API ซ้ำขณะเรนเดอร์หน้า
$__rank_cache = [];

// ดึงรูปภาพ rank ปัจจุบันของผู้เล่นจาก API henrikdev โดยใช้ riot_id และ region
function fetch_player_rank_img($riot_id, $region, $userId) {
  global $__rank_cache;
  if (empty($riot_id) || empty($region)) return null;
  $key = $region . '::' . $riot_id;
  if (isset($__rank_cache[$key])) return $__rank_cache[$key];

  // riot_id คาดหวังในรูปแบบ Name#Tag — เข้ารหัสสำหรับ URL
  $parts = explode('#', $riot_id);
  if (count($parts) !== 2) { $__rank_cache[$key] = null; return null; }
  $name = urlencode(trim($parts[0]));
  $tag  = urlencode(trim($parts[1]));
  $name = str_replace('+', '%20', $name);
  $tag = str_replace('+', '%20', $tag);
  $tag = str_replace('#', '%23', $tag);

  $url = "https://api.henrikdev.xyz/valorant/v1/mmr/" . urlencode($region) . "/$name/$tag";
  $resp = call_api($url);
  if (!$resp) { $__rank_cache[$key] = null; return null; }
  $json = json_decode($resp, true);
  if (!is_array($json) || empty($json['status']) || $json['status'] != 200) { $__rank_cache[$key] = null; return null; }
  $img = $json['data']['images']['small'] ?? null;
  $__rank_cache[$key] = $img;
  return $img;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../../css/LFT_LFP.css" rel="stylesheet">
    <title>Recruit Players</title>
    <?php include '../../utils/link.php'; ?>
    <style>
    /* position rank image at top-right of card */
    .team-card {
        position: relative;
    }

    .team-card .rank-badge {
        position: absolute;
        top: 16px;
        right: 16px;
        height: 56px;
        width: auto;
        z-index: 5;
    }
    </style>
</head>

<body>
    <div class="container">
        <br>

        <!-- search form -->
        <div class="mb-4">
            <div class="search-box">
                <form method="GET" action="">
                    <div class="row align-items-center">
                        <!-- Search Box -->
                        <div class="col-md-8 mb-3 mb-md-0">
                            <div class="input-group">
                                <span class="input-group-text bg-dark text-light border-dark"><i
                                        class="fas fa-search"></i></span>
                                <input type="text" class="form-control bg-dark text-light border-dark" name="search"
                                    placeholder="Search posts..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <!-- Buttons -->
                        <div class="col-md-4 d-flex justify-content-end gap-2">
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (!$hasTeam): ?>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                data-bs-target="#createLFPModal">Create LFP</button>
                            <?php else: ?>
                            <button class="btn btn-secondary" disabled
                                title="คุณมีทีมอยู่แล้ว จึงไม่สามารถสร้างโพสต์ได้"><i
                                    class="fas fa-user-friends me-2"></i> In Team</button>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <?php foreach($posts as $post): ?>
            <div class="col-md-6 mb-3">
                <div class="team-card p-3">
                    <?php
          // prefer live rank image (from riot id + region) when available
          $liveImg = null;
          if (!empty($post['author_riot_id']) && !empty($post['author_region'])) {
            $liveImg = fetch_player_rank_img($post['author_riot_id'], $post['author_region'], $post['user_id']);
          }
          $postImg = $liveImg ?? rank_to_img($post['post_rank'] ?? '', $post['user_id'] ?? 0);
        ?>
                    <img src="<?= htmlspecialchars($postImg) ?>"
                        alt="<?= htmlspecialchars(($post['post_rank'] ?: 'Unranked')) ?>" class="rank-badge">
                    <div class="d-flex align-items-center mb-2">
                        <img src="<?= htmlspecialchars(profile_img_url($post['profile_img'] ?? null)) ?>"
                            class="player-avatar me-2" style="width:48px;height:48px;border-radius:6px">
                        <div>
                            <strong><?= htmlspecialchars($post['position']) ?></strong>
                            <!-- removed previous inline rank img here -->
                            <?php if(!empty($post['author_region'])): ?>
                            <small
                                class="text-muted ms-2"><?= strtoupper(htmlspecialchars($post['author_region'])) ?></small>
                            <?php endif; ?>
                            <?php if(!empty($post['author_riot_id'])):
                            $href = '../leaderboard/leaderboardplayer.php?riot_id=' . urlencode($post['author_riot_id']) . '&region=' . urlencode($post['author_region']);
                         ?>
                            <small class="d-block"><a class="text-white" href="<?= htmlspecialchars($href) ?>">Riot ID:
                                    <?= htmlspecialchars($post['author_riot_id']) ?></a></small>
                            <?php endif; ?>
                            <br>
                            <small>By <?= htmlspecialchars($post['first_name'].' '.$post['last_name']) ?> ·
                                <?= htmlspecialchars($post['created_at']) ?></small>
                        </div>
                    </div>
                    <p><?= nl2br(htmlspecialchars($post['experience'])) ?></p>
                    <div class="d-flex justify-content-between">
                        <div>
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id']==$post['user_id']): ?>
                            <button class="btn btn-outline-primary btn-sm view-applicants-btn"
                                data-post="<?= $post['id'] ?>">View Applicants</button>
                            <button class="btn btn-danger btn-sm delete-post-btn"
                                data-post="<?= $post['id'] ?>">Delete</button>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $post['user_id']): ?>
                            <?php if($hasTeam): ?>
                            <button class="btn btn-primary btn-sm apply-btn"
                                data-post="<?= $post['id'] ?>">Invite</button>
                            <?php else: ?>
                            <button class="btn btn-primary btn-sm" disabled
                                title="คุณต้องมีทีมเพื่อเชิญ">Invite</button>
                            <?php endif; ?>
                            <button class="btn btn-secondary btn-sm open-chat-btn" data-post="<?= $post['id'] ?>"
                                data-author="<?= $post['user_id'] ?>">Chat</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- pagination -->
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
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
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
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

    <!-- Create LFP Modal -->
    <div class="modal fade" id="createLFPModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="createLFPForm" class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Create Recruit Post</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label>Position</label>
                        <select name="position" class="form-select bg-secondary text-light" required>
                            <option>Duelist</option>
                            <option>Initiator</option>
                            <option>Controller</option>
                            <option>Sentinel</option>
                            <option>Flex</option>
                        </select>
                    </div>

                    <!-- added Rank -->
                    <div class="mb-2">
                        <label>Rank</label>
                        <select name="rank" class="form-select bg-secondary text-light">
                            <option value="">Any / ไม่ระบุ</option>
                            <option>Unranked</option>
                            <option>Iron</option>
                            <option>Bronze</option>
                            <option>Silver</option>
                            <option>Gold</option>
                            <option>Platinum</option>
                            <option>Diamond</option>
                            <option>Ascendant</option>
                            <option>Immortal</option>
                            <option>Radiant</option>
                        </select>
                    </div>

                    <!-- region: show user's region (read-only, taken from profile) -->
                    <div class="mb-2">
                        <label>Region</label>
                        <input type="text" class="form-control bg-secondary text-light"
                            value="<?= htmlspecialchars(strtoupper($userRegion ?? 'ไม่ระบุ')) ?>" readonly>
                        <div><small class="text-danger">Region จะถูกดึงจากโปรไฟล์ของคุณ — ไม่สามารถแก้ไขที่นี่</small>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label>Experience / Requirements</label>
                        <textarea name="experience" class="form-control bg-secondary text-light" rows="4"
                            required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success" type="submit">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Applicants Modal (dynamic) -->
    <div class="modal fade" id="applicantsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Applicants</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="applicantsBody">
                    <!-- filled by JS -->
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // create post
        document.getElementById('createLFPForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fetch('../api/create_lfp.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json()).then(j => {
                    alert(j.message);
                    if (j.success) location.reload();
                }).catch(() => alert('Error'));
        });

        // apply (no "Sending..." text)
        document.querySelectorAll('.apply-btn:not([disabled])').forEach(btn => {
            btn.addEventListener('click', async () => {
                var postId = btn.dataset.post;
                if (!confirm('เชิญผู้เล่นคนนี้เข้าทีมหรือไม่?')) return;
                // disable only (no text change)
                btn.disabled = true;

                try {
                    var fd = new FormData();
                    fd.append('post_id', postId);
                    const resp = await fetch('../api/apply_lfp.php', {
                        method: 'POST',
                        body: fd
                    });
                    const txt = await resp.text();
                    console.log('apply_lfp raw response:', resp.status, txt);
                    if (!resp.ok) {
                        alert('Server error: ' + resp.status);
                        btn.disabled = false;
                        return;
                    }
                    let j;
                    try {
                        j = JSON.parse(txt);
                    } catch (e) {
                        alert('Invalid JSON response from server. ดู Console/Network');
                        console.error('Invalid JSON:', txt);
                        btn.disabled = false;
                        return;
                    }
                    alert(j.message || 'No message');
                    if (!j.success) {
                        btn.disabled = false;
                    }
                } catch (err) {
                    console.error('Network / fetch error:', err);
                    alert('Network error — ดู Console/Network');
                    btn.disabled = false;
                }
            });
        });

        // view applicants (manager)
        document.querySelectorAll('.view-applicants-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                var postId = btn.dataset.post;
                window.location.href = '/VALPROJECT/team/api/request_status.php?post_id=' + encodeURIComponent(postId);
            });
        });

        // chat open - open chat page in new tab
        document.querySelectorAll('.open-chat-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                var userId = btn.dataset.author;
                // Open chat page in new tab with chat_with parameter and auto message
                var message = encodeURIComponent('สวัสดี สนใจเข้าร่วมทีมมั้ย');
                window.open('/VALPROJECT/chat/index.php?chat_with=' + encodeURIComponent(
                    userId) + '&message=' + message, '_blank');
            });
        });

        // delete post (owner)
        document.querySelectorAll('.delete-post-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!confirm('ลบโพสต์นี้จริงหรือไม่?')) return;
                var postId = btn.dataset.post;
                fetch('../api/delete_lfp.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            post_id: postId
                        })
                    })
                    .then(async r => {
                        const txt = await r.text();
                        try {
                            const j = JSON.parse(txt);
                            alert(j.message);
                            if (j.success) {
                                var col = btn.closest('.col-md-6');
                                if (col) col.remove();
                            }
                        } catch (e) {
                            console.error('Non-JSON response:', txt);
                            alert('Server error — ดู Console / Network for details');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Network error');
                    });
            });
        });

    });
    </script>
    <script>
    console.log('LFP posts (from server):', <?php echo json_encode($posts, JSON_UNESCAPED_UNICODE); ?>);
    </script>
</body>

</html>