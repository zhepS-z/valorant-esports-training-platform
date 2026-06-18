<?php 
define('ACCESS', true);
include '../utils/db.php';
include 'admin_auth.php';

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'])) {
    $edit_id = intval($_POST['edit_user_id']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $riot_id = $conn->real_escape_string($_POST['riot_id']);
    $role = $conn->real_escape_string($_POST['role']);
    $region = $conn->real_escape_string($_POST['region']);

    $sql_update = "UPDATE users SET 
        first_name='$first_name',
        last_name='$last_name',
        email='$email',
        riot_id='$riot_id',
        role='$role',
        region='$region'
        WHERE user_id=$edit_id";
    $conn->query($sql_update);
    header("Location: user_table.php");
    exit;
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $delete_id = intval($_POST['delete_user_id']);
    $conn->query("DELETE FROM users WHERE user_id=$delete_id");
    header("Location: user_table.php");
    exit;
}

// Ban / Unban (from Ban modal or unban button)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['ban_user_id']) || isset($_POST['unban_user_id']))) {
    if (isset($_POST['ban_user_id'])) {
        $ban_id = intval($_POST['ban_user_id']);
        $option = $_POST['ban_option'] ?? '';
        $ban_until = null;
        $now = time();
        switch ($option) {
            case '30':
                $ban_until = date('Y-m-d H:i:s', strtotime('+30 days', $now));
                break;
            case '90':
                $ban_until = date('Y-m-d H:i:s', strtotime('+90 days', $now));
                break;
            case '120':
                $ban_until = date('Y-m-d H:i:s', strtotime('+120 days', $now));
                break;
            case '365':
                $ban_until = date('Y-m-d H:i:s', strtotime('+1 year', $now));
                break;
            case 'perm':
                $ban_until = '9999-12-31 23:59:59';
                break;
            default:
                $ban_until = null;
        }

        $reason = isset($_POST['ban_reason']) ? $conn->real_escape_string($_POST['ban_reason']) : null;
        $banned_by = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : null;

        if ($ban_until === null) {
            // unban action via ban modal (treat as unban)
            $conn->query("UPDATE users SET ban_until=NULL WHERE user_id=$ban_id");
            $ban_from_db = date('Y-m-d H:i:s', $now);
            $ban_until_db = date('Y-m-d H:i:s', $now);
            $r = $conn->real_escape_string('Unbanned' . ($reason ? (': '.$reason) : ''));
            $insert = "INSERT INTO ban_history (user_id, banned_by, ban_from, ban_until, reason) VALUES ($ban_id, " . ($banned_by ? $banned_by : "NULL") . ", '$ban_from_db', '$ban_until_db', '$r')";
            $conn->query($insert);
        } else {
            $ban_until_esc = $conn->real_escape_string($ban_until);
            $conn->query("UPDATE users SET ban_until='$ban_until_esc' WHERE user_id=$ban_id");
            $ban_from_db = date('Y-m-d H:i:s', $now);
            $r = $reason ? $conn->real_escape_string($reason) : null;
            $insert = "INSERT INTO ban_history (user_id, banned_by, ban_from, ban_until, reason) VALUES ($ban_id, " . ($banned_by ? $banned_by : "NULL") . ", '$ban_from_db', '$ban_until_esc', " . ($r ? "'$r'" : "NULL") . ")";
            $conn->query($insert);
        }
    }

    if (isset($_POST['unban_user_id'])) {
        $unban_id = intval($_POST['unban_user_id']);
        $conn->query("UPDATE users SET ban_until=NULL WHERE user_id=$unban_id");
        $nowts = date('Y-m-d H:i:s');
        $banned_by = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : null;
        $r = $conn->real_escape_string('Unbanned by admin');
        $insert = "INSERT INTO ban_history (user_id, banned_by, ban_from, ban_until, reason) VALUES ($unban_id, " . ($banned_by ? $banned_by : "NULL") . ", '$nowts', '$nowts', '$r')";
        $conn->query($insert);
    }

    header("Location: user_table.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>User Management | Valorant Esports</title>
    <?php include '../utils/link.php'; ?>
    <link rel="stylesheet" href="../css/admin.css">
    <style>

    </style>
</head>
<body>
    <div class="container">
        <br>
        <div class="page-header">
            <h1 class="page-title">User Management</h1>
        </div>
    
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Profile</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Riot ID</th>
                        <th>Role</th>
                        <th>Region</th>
                        <th>Email Verified</th>
                        <th>OTP Verified</th>
                        <th>Ban Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "SELECT * FROM users ORDER BY user_id DESC";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0):
                    $i = 1;
                    while($row = $result->fetch_assoc()):
                        $profile = $row['profile_img'] ? $row['profile_img'] : "https://ui-avatars.com/api/?name=".urlencode($row['first_name'].' '.$row['last_name'])."&background=1a1a1a&color=6366f1&rounded=true&size=64";
                        
                        // Role badge class
                        $roleClass = "badge badge-role";
                        if ($row['role'] == 'player') $roleClass .= " badge-player";
                        elseif ($row['role'] == 'coach') $roleClass .= " badge-coach";
                        elseif ($row['role'] == 'admin') $roleClass .= " badge-admin";
                        elseif ($row['role'] == 'manager') $roleClass .= " badge-manager";

                        // Ban status
                        $ban_until = $row['ban_until'] ?? null;
                        $is_banned = false;
                        $ban_label = 'Not banned';
                        if ($ban_until) {
                            if ($ban_until === '9999-12-31 23:59:59') {
                                $is_banned = true;
                                $ban_label = 'Permanent';
                            } else {
                                $until_ts = strtotime($ban_until);
                                if ($until_ts > time()) {
                                    $is_banned = true;
                                    $days = ceil(($until_ts - time()) / 86400);
                                    $ban_label = $days . ' day' . ($days>1?'s':'');
                                } else {
                                    $ban_label = 'Not banned';
                                    $is_banned = false;
                                }
                            }
                        }
                ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><img src="<?= htmlspecialchars($profile) ?>" class="profile-img" alt="profile"></td>
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['riot_id']) ?></td>
                        <td><span class="<?= $roleClass ?>"><?= htmlspecialchars(ucfirst($row['role'])) ?></span></td>
                        <td style="text-transform:uppercase"><?= htmlspecialchars($row['region']) ?></td>
                        <td><?= $row['email_verified'] ? '<i class="fa fa-check-circle text-success verification-icon"></i>' : '<i class="fa fa-times-circle text-danger verification-icon"></i>' ?></td>
                        <td><?= $row['otp_verified'] ? '<i class="fa fa-check-circle text-success verification-icon"></i>' : '<i class="fa fa-times-circle text-danger verification-icon"></i>' ?></td>
                        <td>
                            <?php if ($is_banned): ?>
                                <span class="badge badge-ban active"><?= htmlspecialchars($ban_label) ?></span>
                            <?php else: ?>
                                <span class="badge badge-ban"><?= htmlspecialchars($ban_label) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon btn-edit" title="Edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal"
                                    data-id="<?= $row['user_id'] ?>"
                                    data-first="<?= htmlspecialchars($row['first_name']) ?>"
                                    data-last="<?= htmlspecialchars($row['last_name']) ?>"
                                    data-email="<?= htmlspecialchars($row['email']) ?>"
                                    data-riot="<?= htmlspecialchars($row['riot_id']) ?>"
                                    data-role="<?= $row['role'] ?>"
                                    data-region="<?= $row['region'] ?>"
                                ><i class="fa fa-pen"></i></button>

                                <?php if ($is_banned): ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('ต้องการปลดแบนผู้ใช้นี้จริงหรือไม่?');">
                                        <input type="hidden" name="unban_user_id" value="<?= $row['user_id'] ?>">
                                        <button type="submit" class="btn-icon" title="Unban"><i class="fa fa-unlock"></i></button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-icon btn-ban" title="Ban"
                                        data-bs-toggle="modal"
                                        data-bs-target="#banModal"
                                        data-id="<?= $row['user_id'] ?>"
                                        data-name="<?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?>"
                                    ><i class="fa fa-ban"></i></button>
                                <?php endif; ?>

                                <button class="btn-icon btn-history" title="History" onclick="showHistory(<?= $row['user_id'] ?>)"><i class="fa fa-history"></i></button>

                                <form method="post" style="display:inline;" onsubmit="return confirm('ต้องการลบผู้ใช้นี้จริงหรือไม่?');">
                                    <input type="hidden" name="delete_user_id" value="<?= $row['user_id'] ?>">
                                    <button type="submit" class="btn-icon btn-delete" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="11" class="empty-state">
                            <i class="fa fa-users fa-3x mb-3" style="color: var(--text-secondary);"></i>
                            <p>No users found.</p>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form method="post" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="edit_user_id" id="edit_user_id">
            <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" id="edit_email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Riot ID</label>
                <input type="text" class="form-control" name="riot_id" id="edit_riot_id">
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-control" name="role" id="edit_role" required>
                    <option value="player">Player</option>
                    <option value="coach">Coach</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Region</label>
                <select class="form-control" name="region" id="edit_region" required>
                    <option value="na">NA</option>
                    <option value="eu">EU</option>
                    <option value="ap">AP</option>
                    <option value="kr">KR</option>
                    <option value="latam">LATAM</option>
                    <option value="br">BR</option>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Ban Modal -->
    <div class="modal fade" id="banModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form method="post" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Ban User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="ban_user_id" id="ban_user_id">
            <p id="ban_user_name" style="font-weight:600;margin-bottom:1rem;"></p>
            <div class="mb-3">
                <label class="form-label">Duration</label>
                <select class="form-control" name="ban_option" id="ban_option" required>
                    <option value="30">30 วัน</option>
                    <option value="90">90 วัน</option>
                    <option value="120">120 วัน</option>
                    <option value="365">1 ปี</option>
                    <option value="perm">ถาวร</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">หมายเหตุ (ไม่บังคับ)</label>
                <input type="text" class="form-control" name="ban_reason" id="ban_reason" placeholder="Optional reason">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Confirm Ban</button>
          </div>
        </form>
      </div>
    </div>

    <!-- History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Ban history</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="historyModalBody">
            <div class="text-center text-muted">Loading...</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

<script>
var editModal = document.getElementById('editModal');
if (editModal) {
  editModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    document.getElementById('edit_user_id').value = button.getAttribute('data-id');
    document.getElementById('edit_first_name').value = button.getAttribute('data-first');
    document.getElementById('edit_last_name').value = button.getAttribute('data-last');
    document.getElementById('edit_email').value = button.getAttribute('data-email');
    document.getElementById('edit_riot_id').value = button.getAttribute('data-riot');
    document.getElementById('edit_role').value = button.getAttribute('data-role');
    document.getElementById('edit_region').value = button.getAttribute('data-region');
  });
}

var banModal = document.getElementById('banModal');
if (banModal) {
  banModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    document.getElementById('ban_user_id').value = button.getAttribute('data-id');
    document.getElementById('ban_user_name').textContent = button.getAttribute('data-name');
    document.getElementById('ban_option').value = '30';
    document.getElementById('ban_reason').value = '';
  });
}

function showHistory(userId) {
  var modalEl = document.getElementById('historyModal');
  var body = document.getElementById('historyModalBody');
  body.innerHTML = '<div class="text-center text-muted">Loading...</div>';
  var myModal = new bootstrap.Modal(modalEl);
  myModal.show();

  fetch('ban_history.php?user_id=' + encodeURIComponent(userId))
    .then(function(res){ return res.text(); })
    .then(function(html){
      body.innerHTML = html;
    })
    .catch(function(err){
      body.innerHTML = '<div class="text-danger">Error loading history</div>';
      console.error(err);
    });
}

// Add subtle hover animations to table rows
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.table tbody tr');
    rows.forEach(row => {
        row.style.transition = 'transform 0.2s ease, background 0.2s ease';
    });
});
</script>
</body>
</html>