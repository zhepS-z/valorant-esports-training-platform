<?php
session_start();
define('ACCESS', true);
require_once '../utils/apikey.php';
require_once '../auth/auth_check.php';
include '../utils/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}
$uid = (int)$_SESSION['user_id'];

// applications TO my posts (people who applied to LFP posts I created)
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
$res_owner = $stmt->get_result();
$apps_to_my_posts = $res_owner->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// my applications (I applied to others' posts)
$sql_mine = "SELECT a.app_id, a.post_id, a.status, a.created_at,
                    p.user_id AS post_owner, p.position AS post_position, p.rank AS post_rank,
                    po.first_name AS owner_fn, po.last_name AS owner_ln, po.profile_img AS owner_img,
                    p.id AS post_id
             FROM lfp_applications a
             JOIN lfp_posts p ON a.post_id = p.id
             LEFT JOIN users po ON p.user_id = po.user_id
             WHERE a.user_id = ?
             ORDER BY a.created_at DESC";
$stmt = $conn->prepare($sql_mine);
$stmt->bind_param('i', $uid);
$stmt->execute();
$res_mine = $stmt->get_result();
$my_apps = $res_mine->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>คำเชิญ / คำสมัคร (LFP applications)</title>
<link href="../css/LFT_LFP.css" rel="stylesheet">
<?php include '../../utils/link.php'; ?>
<style>
.card { background:#06222d; border-radius:8px; padding:14px; color:#fff; margin-bottom:12px; }
.avatar { width:48px;height:48px;border-radius:50%;object-fit:cover;margin-right:12px; }
.small { font-size:0.9rem; color:#cbd5df; }
.actions button { margin-right:8px; }
.empty { color:#99a7b2; padding:18px; background:transparent; border:1px dashed #123; border-radius:8px; }
</style>
</head>
<body>
<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>คำเชิญเข้าร่วมทีม (Team invitations)</h2>
    <a href="team_manage.php" class="btn btn-outline-light">กลับ</a>
  </div>

  <h4>ผู้สมัครในโพสต์ของฉัน</h4>
  <?php if (count($apps_to_my_posts) === 0): ?>
    <div class="empty">ไม่มีใครสมัครในโพสต์ของคุณ</div>
  <?php else: ?>
    <?php foreach($apps_to_my_posts as $a): ?>
      <div class="card d-flex align-items-center">
        <?php
          if (empty($a['profile_img'])) {
            $avatar = null;  // Use icon instead
          } else if (preg_match('#^https?://#i', $a['profile_img'])) {
            $avatar = $a['profile_img'];
          } else {
            $p = str_replace('\\', '/', $a['profile_img']);
            $p = str_replace('team/', '', $p);
            $p = ltrim($p, '/');
            $avatar = '/VALPROJECT/img/' . $p;
          }
        ?>
        <img src="<?= htmlspecialchars($avatar) ?>" class="avatar" alt="avatar">
        <div style="flex:1">
          <div class="d-flex justify-content-between">
            <div>
              <strong><?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?></strong>
              <div class="small">Riot: <?= htmlspecialchars($a['riot_id'] ?: '-') ?> · ตำแหน่ง: <?= htmlspecialchars($a['post_position']) ?> · Rank: <?= htmlspecialchars($a['post_rank'] ?: '-') ?></div>
              <div class="small">สมัครเมื่อ <?= date('j M Y H:i', strtotime($a['created_at'])) ?></div>
            </div>
            <div class="actions">
              <?php if ($a['status'] === 'pending'): ?>
                <button class="btn btn-sm btn-success invite-app" data-app="<?= (int)$a['app_id'] ?>">เชิญเข้าทีม</button>
                <button class="btn btn-sm btn-danger decline-app" data-app="<?= (int)$a['app_id'] ?>">ปฏิเสธ</button>
              <?php elseif ($a['status'] === 'accepted'): ?>
                <span class="badge bg-success">เชิญแล้ว</span>
              <?php else: ?>
                <span class="badge bg-secondary">ปฏิเสธแล้ว</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <hr>

  <h4>คำสมัครของฉัน</h4>
  <?php if (count($my_apps) === 0): ?>
    <div class="empty">คุณยังไม่ได้ส่งคำสมัคร</div>
  <?php else: ?>
    <?php foreach($my_apps as $m): ?>
      <div class="card d-flex align-items-center">
        <img src="<?= htmlspecialchars($m['owner_img'] ?: '../../img/profile/default.png') ?>" class="avatar" alt="owner">
        <div style="flex:1">
          <div class="d-flex justify-content-between">
            <div>
              <strong>โพสต์โดย <?= htmlspecialchars($m['owner_fn'].' '.$m['owner_ln']) ?></strong>
              <div class="small">ตำแหน่ง: <?= htmlspecialchars($m['post_position']) ?> · Rank: <?= htmlspecialchars($m['post_rank'] ?: '-') ?></div>
              <div class="small">ส่งเมื่อ <?= date('j M Y H:i', strtotime($m['created_at'])) ?></div>
            </div>
            <div>
              <?php if ($m['status'] === 'pending'): ?>
                <span class="badge bg-warning text-dark">รอดำเนินการ</span>
              <?php elseif ($m['status'] === 'accepted'): ?>
                <span class="badge bg-success">ได้รับเชิญ/รับแล้ว</span>
              <?php else: ?>
                <span class="badge bg-danger">ปฏิเสธ</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<script>
document.addEventListener('click', async function(e){
  // accept -> invite_applicant.php (existing endpoint)
  if (e.target.classList.contains('invite-app')) {
    const btn = e.target;
    if (!confirm('เชิญผู้สมัครนี้เข้าทีมหรือไม่?')) return;
    btn.disabled = true;
    try {
      const resp = await fetch('../api/invite_applicant.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `app_id=${encodeURIComponent(btn.dataset.app)}`
      });
      const txt = await resp.text();
      let j;
      try { j = JSON.parse(txt); } catch(err) { alert('Server error'); console.error(txt); btn.disabled=false; return; }
      alert(j.message || '');
      if (j.success) location.reload(); else btn.disabled=false;
    } catch(err) {
      console.error(err);
      alert('Network error');
      btn.disabled=false;
    }
  }

  // decline -> decline_application.php
  if (e.target.classList.contains('decline-app')) {
    const btn = e.target;
    if (!confirm('ยืนยันการปฏิเสธคำสมัคร?')) return;
    btn.disabled = true;
    try {
      const resp = await fetch('../api/decline_application.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `app_id=${encodeURIComponent(btn.dataset.app)}`
      });
      const txt = await resp.text();
      let j;
      try { j = JSON.parse(txt); } catch(err) { alert('Server error'); console.error(txt); btn.disabled=false; return; }
      alert(j.message || '');
      if (j.success) location.reload(); else btn.disabled=false;
    } catch(err) {
      console.error(err);
      alert('Network error');
      btn.disabled=false;
    }
  }
});
</script>
</body>
</html>
<?php $conn->close(); ?>