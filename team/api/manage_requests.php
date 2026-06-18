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

// check if user is a team manager (may be used to show team requests)
$team_id = null;
$team_name = null;
$team_row = $conn->query("SELECT team_id, team_name FROM teams WHERE manager_id = $uid")->fetch_assoc();
if ($team_row) {
    $team_id = (int)$team_row['team_id'];
    $team_name = $team_row['team_name'];
}

// Team join requests (for managers)
$team_requests = [];
if ($team_id) {
    $sql = "SELECT r.request_id, r.status, r.created_at, u.user_id, u.first_name, u.last_name, u.profile_img
            FROM team_join_requests r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.team_id = ?
            ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $team_id);
    $stmt->execute();
    $team_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

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

// my applications (I applied to others' posts)
$sql_mine = "SELECT a.app_id, a.post_id, a.status, a.created_at,
                    p.user_id AS post_owner, p.position AS post_position, p.rank AS post_rank,
                    po.first_name AS owner_fn, po.last_name AS owner_ln, po.profile_img AS owner_img
             FROM lfp_applications a
             JOIN lfp_posts p ON a.post_id = p.id
             LEFT JOIN users po ON p.user_id = po.user_id
             WHERE a.user_id = ?
             ORDER BY a.created_at DESC";
$stmt = $conn->prepare($sql_mine);
$stmt->bind_param('i', $uid);
$stmt->execute();
$my_apps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>จัดการคำขอ / คำสมัคร</title>
<link href="../css/LFT_LFP.css" rel="stylesheet">
<?php include '../../utils/link.php'; ?>
<style>
:root{--bg:#081521;--card:#072631;--muted:#9fb0bb;--accent:#ff5860}
body{background:var(--bg);color:#eef7fb;font-family:Inter,system-ui,Segoe UI,Roboto,sans-serif}
.container{max-width:1100px;margin:36px auto;padding:0 16px}
.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.card{background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0)); border-radius:10px;padding:14px;border:1px solid rgba(255,255,255,0.03); margin-bottom:12px; display:flex; gap:12px; align-items:center}
.avatar{width:56px;height:56px;border-radius:8px;object-fit:cover;border:1px solid rgba(255,255,255,0.04)}
meta-small{font-size:0.9rem;color:var(--muted)}
.row{display:flex;gap:18px;flex-wrap:wrap}
.col{flex:1 1 320px;min-width:280px}
.section-title{font-weight:600;margin:12px 0 8px;color:#dff5ff}
.controls{display:flex;gap:8px;align-items:center}
.btn{background:#0b3d4f;color:#e6f7fb;padding:8px 12px;border-radius:8px;border:0;cursor:pointer}
.btn.ghost{background:transparent;border:1px solid rgba(255,255,255,0.04)}
.btn.success{background:#15a85a}
.btn.danger{background:#d9534f}
.badge{padding:6px 10px;border-radius:8px;background:rgba(255,255,255,0.03);color:var(--muted);font-size:0.85rem}
.small{font-size:0.9rem;color:var(--muted)}
.actions{display:flex;gap:8px;align-items:center}
.empty{padding:20px;border:1px dashed rgba(255,255,255,0.03);border-radius:10px;color:var(--muted);text-align:center}
.top-bar{display:flex;gap:12px;align-items:center}
.tab{padding:8px 12px;border-radius:8px;cursor:pointer;background:transparent;border:1px solid transparent;color:var(--muted)}
.tab.active{background:rgba(255,255,255,0.02);border-color:rgba(255,255,255,0.04);color:#e6f7fb}
@media(max-width:720px){.header{flex-direction:column;align-items:flex-start;gap:12px}.row{flex-direction:column}}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div>
      <h1 style="margin:0 0 6px">จัดการคำขอ / คำสมัคร</h1>
      <div class="small">ดูคำขอเข้าร่วมทีม, ผู้สมัครโพสต์ LFP และคำสมัครของคุณ</div>
    </div>
    <div class="controls">
      <a class="btn ghost" href="team_manage.php">ย้อนกลับ</a>
      <?php if($team_id): ?>
        <div class="badge">ทีม: <?= htmlspecialchars($team_name) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="top-bar" style="margin-bottom:12px">
    <div class="tab active" data-tab="team-requests">คำขอเข้าทีม</div>
    <div class="tab" data-tab="apps-to-my-posts">ผู้สมัครโพสต์ของฉัน</div>
    <div class="tab" data-tab="my-apps">คำสมัครของฉัน</div>
  </div>

  <div id="area">
    <!-- team join requests (manager only) -->
    <div id="team-requests" class="tab-area">
      <h3 class="section-title">คำขอเข้าร่วมทีม</h3>
      <?php if(!$team_id): ?>
        <div class="empty">คุณยังไม่ได้เป็นผู้จัดการทีม — หน้านี้จะแสดงคำขอเมื่อคุณเป็นผู้จัดการ</div>
      <?php elseif(count($team_requests) === 0): ?>
        <div class="empty">ยังไม่มีคำขอเข้าร่วมทีม</div>
      <?php else: ?>
        <div class="row">
          <?php foreach($team_requests as $r): ?>
            <div class="col">
              <div class="card">
                <?php
                  if (empty($r['profile_img'])) {
                    $avatar_r = null;  // Use icon instead
                  } else if (preg_match('#^https?://#i', $r['profile_img'])) {
                    $avatar_r = $r['profile_img'];
                  } else {
                    $p_r = str_replace('\\', '/', $r['profile_img']);
                    $p_r = str_replace('team/', '', $p_r);
                    $p_r = ltrim($p_r, '/');
                    $avatar_r = '/VALPROJECT/img/' . $p_r;
                  }
                ?>
                <img src="<?= htmlspecialchars($avatar_r) ?>" class="avatar" alt="">
                <div style="flex:1">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                      <div style="font-weight:600"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></div>
                      <div class="small">ส่งคำขอเมื่อ <?= date('j M Y H:i', strtotime($r['created_at'])) ?></div>
                    </div>
                    <div style="text-align:right">
                      <?php if($r['status']==='pending'): ?>
                        <div class="small" style="margin-bottom:8px">สถานะ: <strong>รออนุมัติ</strong></div>
                        <div class="actions">
                          <button class="btn success accept-join" data-id="<?= (int)$r['request_id'] ?>">รับ</button>
                          <button class="btn danger decline-join" data-id="<?= (int)$r['request_id'] ?>">ปฏิเสธ</button>
                        </div>
                      <?php elseif($r['status']==='accepted'): ?>
                        <div class="badge">รับแล้ว</div>
                      <?php else: ?>
                        <div class="badge">ปฏิเสธแล้ว</div>
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

    <!-- applications to my LFP posts -->
    <div id="apps-to-my-posts" class="tab-area" style="display:none">
      <h3 class="section-title">ผู้สมัครในโพสต์ของฉัน (LFP)</h3>
      <?php if(count($apps_to_my_posts) === 0): ?>
        <div class="empty">ยังไม่มีผู้สมัครในโพสต์ของคุณ</div>
      <?php else: ?>
        <div class="row">
          <?php foreach($apps_to_my_posts as $a): ?>
            <div class="col">
              <div class="card">
                <?php
                  if (empty($a['profile_img'])) {
                    $avatar_a = null;  // Use icon instead
                  } else if (preg_match('#^https?://#i', $a['profile_img'])) {
                    $avatar_a = $a['profile_img'];
                  } else {
                    $p_a = str_replace('\\', '/', $a['profile_img']);
                    $p_a = str_replace('team/', '', $p_a);
                    $p_a = ltrim($p_a, '/');
                    $avatar_a = '/VALPROJECT/img/' . $p_a;
                  }
                ?>
                <img src="<?= htmlspecialchars($avatar_a) ?>" class="avatar" alt="">
                <div style="flex:1">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                      <div style="font-weight:600"><?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?></div>
                      <div class="small">Riot: <?= htmlspecialchars($a['riot_id'] ?: '-') ?> · ตำแหน่ง: <?= htmlspecialchars($a['post_position']) ?> · Rank: <?= htmlspecialchars($a['post_rank'] ?: '-') ?></div>
                      <div class="small">สมัครเมื่อ <?= date('j M Y H:i', strtotime($a['created_at'])) ?></div>
                    </div>
                    <div style="text-align:right">
                      <?php if($a['status']==='pending'): ?>
                        <div class="actions">
                          <button class="btn success invite-app" data-app="<?= (int)$a['app_id'] ?>">เชิญ</button>
                          <button class="btn danger decline-app" data-app="<?= (int)$a['app_id'] ?>">ปฏิเสธ</button>
                        </div>
                      <?php elseif($a['status']==='accepted'): ?>
                        <div class="badge">เชิญแล้ว</div>
                      <?php else: ?>
                        <div class="badge">ปฏิเสธแล้ว</div>
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

    <!-- my applications -->
    <div id="my-apps" class="tab-area" style="display:none">
      <h3 class="section-title">คำสมัครของฉัน</h3>
      <?php if(count($my_apps) === 0): ?>
        <div class="empty">คุณยังไม่ได้ส่งคำสมัคร</div>
      <?php else: ?>
        <div class="row">
          <?php foreach($my_apps as $m): ?>
            <div class="col">
              <div class="card">
                <img src="<?= htmlspecialchars($m['owner_img'] ?: '../../../img/profile/default.png') ?>" class="avatar" alt="">
                <div style="flex:1">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                      <div style="font-weight:600">โพสต์โดย <?= htmlspecialchars($m['owner_fn'].' '.$m['owner_ln']) ?></div>
                      <div class="small">ตำแหน่ง: <?= htmlspecialchars($m['post_position']) ?> · Rank: <?= htmlspecialchars($m['post_rank'] ?: '-') ?></div>
                      <div class="small">ส่งเมื่อ <?= date('j M Y H:i', strtotime($m['created_at'])) ?></div>
                    </div>
                    <div style="text-align:right">
                      <?php if($m['status']==='pending'): ?>
                        <div class="badge" style="background:rgba(255,193,7,0.12);color:#ffd24d">รอดำเนินการ</div>
                      <?php elseif($m['status']==='accepted'): ?>
                        <div class="badge">ได้รับเชิญ/รับแล้ว</div>
                      <?php else: ?>
                        <div class="badge">ปฏิเสธ</div>
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

  // generic POST helper
  async function postJSON(url, body) {
    const resp = await fetch(url, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams(body)
    });
    const txt = await resp.text();
    try { return JSON.parse(txt); } catch(e) { throw new Error('Invalid JSON: ' + txt); }
  }

  // accept/decline team join (manager)
  document.addEventListener('click', async (e) => {
    if (e.target.classList.contains('accept-join') || e.target.classList.contains('decline-join')) {
      const id = e.target.dataset.id;
      const action = e.target.classList.contains('accept-join') ? 'accepted' : 'declined';
      if (!confirm(action === 'accepted' ? 'รับผู้ใช้นี้เข้าทีมหรือไม่?' : 'ปฏิเสธคำขอนี้?')) return;
      e.target.disabled = true;
      try {
        const j = await postJSON('process_request.php', { request_id: id, action: action });
        alert(j.message || '');
        if (j.success) location.reload(); else e.target.disabled = false;
      } catch(err) { console.error(err); alert('Network / Server error'); e.target.disabled = false; }
    }

    // invite applicant from LFP applications
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
  });
})();
</script>
</body>
</html>
<?php $conn->close();