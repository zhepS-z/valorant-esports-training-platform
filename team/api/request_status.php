<?php
session_start();
define('ACCESS', true);
require_once '../../utils/apikey.php';
require_once '../../auth/auth_check.php';
include '../../utils/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}
$uid = (int)$_SESSION['user_id'];

// applications TO my LFP posts (people who applied to posts I created)
$sql_owner = "SELECT a.app_id, a.post_id, a.user_id AS applicant_id, a.status, a.created_at,
                     p.position AS post_position, p.rank AS post_rank,
                     u.first_name, u.last_name, u.profile_img, u.riot_id
              FROM lfp_applications a
              JOIN lfp_posts p ON a.post_id = p.id
              JOIN users u ON a.user_id = u.user_id
              WHERE p.user_id = ?
              ORDER BY a.created_at DESC";
$stmt = $conn->prepare($sql_owner);
$stmt->bind_param('i', $uid);
$stmt->execute();
$apps_to_my_posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- replace previous team_invites query: use lfp_applications as source for "Team Invites" view ---
/*
$sql_invites = "SELECT r.request_id, r.status, r.created_at,
                       t.team_id, t.team_name, t.team_logo, t.region,
                       u.first_name AS manager_fn, u.last_name AS manager_ln
                FROM team_join_requests r
                JOIN teams t ON r.team_id = t.team_id
                LEFT JOIN users u ON t.manager_id = u.user_id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql_invites);
$stmt->bind_param('i', $uid);
$stmt->execute();
$team_invites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
*/

// ใช้ผลจาก lfp_applications (ผู้สมัครที่มาสมัครโพสต์ของฉัน) เป็น "Team Invites"
$team_invites = $apps_to_my_posts;
// for debug: show SQL used to fetch these items
$sql_invites = $sql_owner;

// my team join requests (สถานะคำขอเข้าร่วมทีม) -> used as "คำสมัครของฉัน"
$sql_my_requests = "SELECT r.request_id, r.team_id, r.status, r.created_at, t.team_name, t.team_logo, t.region
                    FROM team_join_requests r
                    JOIN teams t ON r.team_id = t.team_id
                    WHERE r.user_id = ?
                    ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql_my_requests);
$stmt->bind_param('i', $uid);
$stmt->execute();
$my_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// normalize team logo paths so /valproject/team/uploads/team_logos/ -> /VALPROJECT/uploads/team_logos/
function resolve_team_logo_path(?string $path): string {
    if (empty($path)) return '';
    $p = $path;
    // Remove duplicated /valproject/ or /VALPROJECT/ at beginning
    $p = preg_replace('#^/VALPROJECT/VALPROJECT/#i', '/VALPROJECT/', $p);
    $p = preg_replace('#^/valproject/valproject/#i', '/VALPROJECT/', $p);
    // common legacy variants -> canonical
    $p = str_ireplace('/valproject/team/uploads/team_logos/', '/VALPROJECT/uploads/team_logos/', $p);
    $p = str_ireplace('valproject/team/uploads/team_logos/', '/VALPROJECT/uploads/team_logos/', $p);
    // relative variants
    $p = preg_replace('#(\.\./)?team/uploads/team_logos/#i', '/VALPROJECT/uploads/team_logos/', $p);
    $p = preg_replace('#^uploads/team_logos/#i', '/VALPROJECT/uploads/team_logos/', $p);
    // Normalize case: make sure it's /VALPROJECT/ (uppercase)
    $p = preg_replace('#^/valproject/#i', '/VALPROJECT/', $p);
    return $p;
}

// debug helper (enable with ?debug=1)
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
$debug_info = [
    'tables' => [
        'lfp_applications',
        'lfp_posts',
        'users',
        'team_join_requests',
        'teams',
        'users (manager)'
    ],
    'sql' => [
        'sql_owner' => $sql_owner,
        'sql_invites' => $sql_invites,
        'sql_my_requests' => $sql_my_requests,
    ],
    'sources' => [
        'apps_to_my_posts' => 'lfp_applications JOIN lfp_posts JOIN users',
        'team_invites' => 'team_join_requests JOIN teams LEFT JOIN users (manager)',
        'my_requests' => 'team_join_requests JOIN teams'
    ],
    'counts' => [
        'apps_to_my_posts' => count($apps_to_my_posts),
        'team_invites' => count($team_invites),
        'my_requests' => count($my_requests),
    ],
    'samples' => [
        'apps_to_my_posts' => array_slice($apps_to_my_posts, 0, 10),
        'team_invites' => array_slice($team_invites, 0, 10),
        'my_requests' => array_slice($my_requests, 0, 10),
    ],
];

?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>จัดการคำสมัคร / สถานะ</title>
<link href="../css/LFT_LFP.css" rel="stylesheet">
<?php include '../../utils/link.php'; ?>
<style>

body{background:var(--bg);color:#eef7fb;}
.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}

/* ปรับการ์ด: รูปซ้าย ข้อมูลถัดจากรูป และปุ่ม/actions อยู่ขวาล่าง */
.card{
    background : #01182a;
    border-radius:10px;
    padding:16px;
    display:flex;
    gap:16px;
    align-items:stretch;     /* ให้ column ทั้งสามยืดเต็มความสูงการ์ด */
    box-sizing:border-box;
}

/* avatar left column */
.avatar,
.team-logo {
    width:72px;
    height:72px;
    aspect-ratio:1/1;
    flex-shrink:0;
    border-radius:8px;
    object-fit:cover;
    object-position:center;
    background:#0b2b3a;
    border:1px solid rgba(255,255,255,0.04);
}

/* content column: ข้อมูลหลัก อยู่ถัดจากรูป และกินพื้นที่ที่เหลือ */
.card-content{
    display:flex;
    flex-direction:column;
    justify-content:flex-start;
    flex:1 1 auto;
    min-width:0; /* allow truncation */
}

/* title / meta */
.card-top{overflow:hidden}
.card-top .title{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}
.card-top .meta{font-size:0.95rem;color:var(--muted);margin-top:6px}

/* actions column (ขวาสุด) — จัดชิดขวาล่าง */
.card-actions{
    display:flex;
    flex-direction:column;
    justify-content:flex-end; /* push actions to bottom */
    align-items:flex-end;     /* align to right */
    gap:8px;
    flex:0 0 auto;
    min-width:120px;          /* ให้พื้นที่เล็กน้อยสำหรับปุ่ม/ป้าย */
}

/* ถ้าต้องการ badge ใหญ่ ให้จัดกึ่งกลางแนวนอน */
.card-actions .badge{margin-left:auto}

/* ปรับขนาดปุ่มให้เหมาะสม */
.btn.success{background:#15a85a;color:#fff;padding:8px 12px;border-radius:6px;border:0}
.btn.danger{background:#d9534f;color:#fff;padding:8px 12px;border-radius:6px;border:0}
.btn.ghost{background:transparent;border:1px solid rgba(255,255,255,0.04);color:var(--muted);padding:6px 10px;border-radius:6px}

.row{
    display:flex;
    flex-direction:column;
    gap:18px;
    align-items:stretch;
    flex-wrap:nowrap;
}
.col{
    flex: 0 0 100%;
    min-width: 0;
    width: 100%;
}
.section-title{font-weight:600;margin:12px 0 8px;color:#dff5ff}
.controls{display:flex;gap:8px;align-items:center}

.badge{padding:6px 10px;border-radius:8px;background:rgba(255,255,255,0.03);color:var(--muted);font-size:0.85rem}
.small{font-size:0.9rem;color:var(--muted)}
.actions{display:flex;gap:8px;align-items:center}
.empty{padding:20px;border:1px dashed rgba(255,255,255,0.03);border-radius:10px;color:var(--muted);text-align:center}
.top-bar{display:flex;gap:12px;align-items:center;margin-bottom:16px}
.tab{padding:8px 12px;border-radius:8px;cursor:pointer;background:transparent;border:1px solid transparent;color:var(--muted)}
.tab.active{background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.04);color:#e6f7fb}
.team-logo{width:64px;height:64px;border-radius:8px;object-fit:cover;border:1px solid rgba(255,255,255,0.04)}
.status-pending{color:#ffd24d}
.status-accepted{color:#15a85a}
.status-declined{color:#d9534f}
@media(max-width:720px){
  .header{flex-direction:column;align-items:flex-start;gap:12px}
  .row{flex-direction:column}
  /* บังคับให้การ์ดยังคงเป็นแนวนอน แม้บนจอเล็ก เพื่อไม่ให้ข้อความอยู่ใต้รูป */
  .card{
    padding:12px;
    gap:12px;
    flex-direction:row;        /* keep row layout */
    align-items:center;        /* center vertically */
    flex-wrap:nowrap;          /* prevent wrapping that pushes content below image */
    overflow:hidden;
  }
  .avatar{width:56px;height:56px}
  /* actions: ย่อขนาดพื้นที่ แต่ยังคงอยู่ด้านขวา */
  .card-actions{
    flex-direction:column;
    justify-content:flex-end;
    align-items:flex-end;
    min-width:80px;
    margin-top:0;
  }
  /* ให้ content ย่อและตัดข้อความด้วย ellipsis แทนการซ้อนบรรทัดลงล่าง */
  .card-content{flex:1 1 auto;min-width:0;overflow:hidden}
  .card-top .title{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .card-top .meta{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
}

/* ...existing code ... */
</style>
</head>
<body>
  <br>
<div class="container">

<?php if($debug): ?>
  
  <div class="debug-panel" role="note" aria-live="polite">
    <div style="font-weight:700;margin-bottom:6px">DEBUG: data sources & samples</div>
    <div><strong>Tables:</strong> <?= htmlspecialchars(implode(', ', $debug_info['tables'])) ?></div>

    <div><strong>SQL (owner):</strong>
      <pre><?= htmlspecialchars($debug_info['sql']['sql_owner']) ?></pre>
    </div>

    <div><strong>SQL (invites):</strong>
      <pre><?= htmlspecialchars($debug_info['sql']['sql_invites']) ?></pre>
    </div>

    <div><strong>SQL (my_requests):</strong>
      <pre><?= htmlspecialchars($debug_info['sql']['sql_my_requests']) ?></pre>
    </div>

    <div><strong>Counts:</strong>
      <pre><?= htmlspecialchars(json_encode($debug_info['counts'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
    </div>

    <div><strong>Sources:</strong>
      <pre><?= htmlspecialchars(json_encode($debug_info['sources'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
    </div>

    <div><strong>Samples (up to 10 rows each):</strong>
      <pre><?= htmlspecialchars(json_encode($debug_info['samples'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
    </div>

    <div style="margin-top:8px;text-align:right">
      <a href="?debug=0" style="color:#9fe">hide</a>
    </div>
  </div>
<?php endif; ?>

  <div class="header">
    <div>
      <h1 style="margin:0 0 6px">จัดการคำสมัคร / สถานะ</h1>
      <div class="small">ดูผู้สมัครโพสต์ LFP และสถานะคำขอของคุณ</div>
    </div>
    <div class="controls">
      <a class="btn ghost" href="team_manage.php">ย้อนกลับ</a>
    </div>
  </div>

  <div class="top-bar">
    <div class="tab active" data-tab="apps-to-my-posts">คำเชิญเข้าร่วมทีม (Team Invites)</div>
    <div class="tab" data-tab="my-requests">คำสมัครของฉัน</div>
  </div>

  <div id="area">
    <!-- LFP applications to my posts -->
    <div id="apps-to-my-posts" class="tab-area">
      <h3 class="section-title">คำเชิญเข้าร่วมทีม (Team Invites)</h3>
      <?php if(count($team_invites) === 0): ?>
        <div class="empty">ยังไม่มีคำเชิญเข้าร่วมทีม</div>
      <?php else: ?>
        <div class="row">
          <?php foreach($team_invites as $inv): ?>
            <div class="col">
              <div class="card">
                <?php
                  if (empty($inv['profile_img'])) {
                    $avatar = null;  // Use icon instead
                  } else if (preg_match('#^https?://#i', $inv['profile_img'])) {
                    $avatar = $inv['profile_img'];
                  } else {
                    $p = str_replace('\\', '/', $inv['profile_img']);
                    $p = str_replace('team/', '', $p);
                    $p = ltrim($p, '/');
                    $avatar = '/VALPROJECT/img/' . $p;
                  }
                ?>
                <img src="<?= htmlspecialchars($avatar) ?>" class="avatar" alt="">
                <div class="card-content">
                  <div class="card-top">
                    <span class="title"><?= htmlspecialchars(($inv['first_name'] ?? '') . ' ' . ($inv['last_name'] ?? '')) ?></span>
                    <div class="meta">
                      <span class="small">· <?= htmlspecialchars($inv['post_position'] ?? '') ?> <?= ($inv['post_rank'] ? '· '.htmlspecialchars($inv['post_rank']) : '') ?></span>
                      <div class="small">Riot ID: <?= htmlspecialchars($inv['riot_id'] ?? '-') ?></div>
                      <div class="small">สมัครเมื่อ <?= date('j M Y H:i', strtotime($inv['created_at'])) ?></div>
                    </div>
                  </div>
                </div>

                <div class="card-actions">
                  <?php if(($inv['status'] ?? 'pending') === 'pending'): ?>
                    <div class="actions">
                      <button class="btn success invite-app" data-app="<?= (int)($inv['app_id'] ?? 0) ?>">ยอมรับ</button>
                      <button class="btn danger decline-app" data-app="<?= (int)($inv['app_id'] ?? 0) ?>">ปฏิเสธ</button>
                    </div>
                  <?php elseif(($inv['status'] ?? '') === 'accepted'): ?>
                    <div class="badge">เชิญ/ตอบรับแล้ว</div>
                  <?php else: ?>
                    <div class="badge">ปฏิเสธแล้ว</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- my team join requests (คำสมัครของฉัน) -->
    <div id="my-requests" class="tab-area" style="display:none">
      <h3 class="section-title">คำสมัครของฉัน</h3>
      <div class="small" style="margin-bottom:8px">แหล่งข้อมูล: team_join_requests JOIN teams · จำนวน <?= count($my_requests) ?></div>
      <?php if(count($my_requests) === 0): ?>
        <div class="empty">คุณยังไม่ได้ส่งคำขอเข้าร่วมทีม</div>
      <?php else: ?>
        <div class="row">
          <?php foreach($my_requests as $m):
              $status = $m['status'];
              $label = $status === 'pending' ? 'รออนุมัติ' : ($status === 'accepted' ? 'เข้าร่วมแล้ว' : 'ถูกปฏิเสธ');
              $statusClass = $status === 'pending' ? 'status-pending' : ($status === 'accepted' ? 'status-accepted' : 'status-declined');
          ?>
            <div class="col">
              <div class="card">
                <img src="<?= htmlspecialchars(resolve_team_logo_path($m['team_logo']) ?: '../../../img/profile/default.png') ?>" class="avatar" alt="">
                <div style="flex:1">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                      <div style="font-weight:600"><?= htmlspecialchars($m['team_name']) ?> <small class="small">· <?= htmlspecialchars(strtoupper($m['region'] ?? '')) ?></small></div>
                      <div class="small">ส่งเมื่อ <?= date('j M Y H:i', strtotime($m['created_at'])) ?></div>
                    </div>
                    <div style="text-align:right">
                      <div class="<?= $statusClass ?>" style="font-weight:600; margin-bottom:8px"><?= $label ?></div>
                      <?php if($status === 'pending'): ?>
                        <div>
                          <button class="btn danger cancel-request" data-id="<?= (int)$m['request_id'] ?>">ยกเลิกคำขอ</button>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(() => {
  // tabs
  document.querySelectorAll('.tab').forEach(t=>{
    t.addEventListener('click', ()=>{
      document.querySelectorAll('.tab').forEach(x=>x.classList.remove('active'));
      t.classList.add('active');
      document.querySelectorAll('.tab-area').forEach(a=>a.style.display='none');
      const id = t.dataset.tab;
      document.getElementById(id).style.display = '';
    });
  });

  async function postJSON(url, body) {
    const resp = await fetch(url, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams(body)
    });
    const txt = await resp.text();
    try { return JSON.parse(txt); } catch(e) { throw new Error('Invalid JSON: ' + txt); }
  }

  // invite applicant from LFP applications
  document.addEventListener('click', async (e) => {
    if (e.target.classList.contains('invite-app')) {
      const btn = e.target;
      const app = btn.dataset.app;
      if (!confirm('เชิญผู้สมัครนี้เข้าทีมหรือไม่?')) return;
      btn.disabled = true;
      try {
        const j = await postJSON('invite_applicant.php', { app_id: app });
        alert(j.message || '');
        if (j.success) location.reload(); else btn.disabled = false;
      } catch(err) { console.error(err); alert('Network / Server error'); btn.disabled = false; }
    }

    // decline application (LFP)
    if (e.target.classList.contains('decline-app')) {
      const btn = e.target;
      const app = btn.dataset.app;
      if (!confirm('ปฏิเสธคำสมัครนี้หรือไม่?')) return;
      btn.disabled = true;
      try {
        const j = await postJSON('decline_application.php', { app_id: app });
        alert(j.message || '');
        if (j.success) location.reload(); else btn.disabled = false;
      } catch(err) { console.error(err); alert('Network / Server error'); btn.disabled = false; }
    }

    // cancel my join request
    if (e.target.classList.contains('cancel-request')) {
      const btn = e.target;
      const id = btn.dataset.id;
      if (!confirm('ต้องการยกเลิกคำขอนี้หรือไม่?')) return;
      btn.disabled = true;
      try {
        const j = await postJSON('cancel_request.php', { request_id: id });
        alert(j.message || '');
        if (j.success) location.reload(); else btn.disabled = false;
      } catch(err) { console.error(err); alert('Network / Server error'); btn.disabled = false; }
    }

    // accept team invite
    if (e.target.classList.contains('accept-invite')) {
      const btn = e.target;
      const id = btn.dataset.id;
      if (!confirm('เข้าร่วมทีมนี้หรือไม่?')) return;
      btn.disabled = true;
      try {
        const j = await postJSON('accept_invite.php', { request_id: id });
        alert(j.message || '');
        if (j.success) location.reload(); else btn.disabled = false;
      } catch(err) { console.error(err); alert('Network / Server error'); btn.disabled = false; }
    }

    // decline team invite
    if (e.target.classList.contains('decline-invite')) {
      const btn = e.target;
      const id = btn.dataset.id;
      if (!confirm('ปฏิเสธคำเชิญนี้หรือไม่?')) return;
      btn.disabled = true;
      try {
        const j = await postJSON('decline_invite.php', { request_id: id });
        alert(j.message || '');
        if (j.success) location.reload(); else btn.disabled = false;
      } catch(err) { console.error(err); alert('Network / Server error'); btn.disabled = false; }
    }
  });
})();
</script>
<br>
</body>
</html>
<?php $conn->close(); ?>