<?php
define('ACCESS', true);
include '../utils/db.php';
include 'admin_auth.php';

// --- เพิ่ม Ban / Unban handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_team_id'])) {
    $ban_id = intval($_POST['ban_team_id']);
    $duration = $_POST['ban_duration']; // '30','90','120','365','permanent'
    $reason = $conn->real_escape_string($_POST['ban_reason'] ?? '');
    $now = new DateTime('now');

    if ($duration === 'permanent') {
        $ban_until = '9999-12-31 23:59:59';
    } else {
        $days = intval($duration);
        $now->modify("+{$days} days");
        $ban_until = $now->format('Y-m-d H:i:s');
    }

    $sql = "UPDATE teams SET ban_until='{$ban_until}', ban_reason='{$reason}' WHERE team_id={$ban_id}";
    $conn->query($sql);
    header("Location: team_table.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unban_team_id'])) {
    $unban_id = intval($_POST['unban_team_id']);
    $conn->query("UPDATE teams SET ban_until=NULL, ban_reason=NULL WHERE team_id={$unban_id}");
    header("Location: team_table.php");
    exit;
}

// อัปเดตข้อมูลทีม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_team_id'])) {
    $edit_id = intval($_POST['edit_team_id']);
    $team_name = $conn->real_escape_string($_POST['team_name']);
    $manager_id = intval($_POST['manager_id']);
    $region = $conn->real_escape_string($_POST['region']);
    $rank = $conn->real_escape_string($_POST['rank']);
    $max_size = intval($_POST['max_size']);
    $description = $conn->real_escape_string($_POST['description']);
    $practice_schedule = $conn->real_escape_string($_POST['practice_schedule']);

    $sql_update = "UPDATE teams SET 
        team_name='$team_name',
        manager_id=$manager_id,
        region='$region',
        rank='$rank',
        max_size=$max_size,
        description='$description',
        practice_schedule='$practice_schedule'
        WHERE team_id=$edit_id";
    $conn->query($sql_update);
    header("Location: team_table.php");
    exit;
}

// ลบทีม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_team_id'])) {
    $delete_id = intval($_POST['delete_team_id']);
    // ตั้งค่า team_id ของ user ที่อยู่ในทีมนี้ให้เป็น NULL ก่อน
    $conn->query("UPDATE users SET team_id=NULL WHERE team_id=$delete_id");
    $conn->query("DELETE FROM teams WHERE team_id=$delete_id");
    header("Location: team_table.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">        

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management | Valorant Esports</title>
    <?php include '../utils/link.php'; ?>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="container"><br>
        <h2>Team Management</h2>
        <div class="table-container">
            <table class="table align-middle table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Team Name</th>
                        <th>Manager</th>
                        <th>Region</th>
                        <th>Rank</th>
                        <th>Current Size</th>
                        <th>Max Size</th>
                        <th>Description</th>
                        <th>Practice Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "SELECT t.*, u.first_name, u.last_name FROM teams t LEFT JOIN users u ON t.manager_id = u.user_id ORDER BY t.team_id DESC";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0):
                    $i = 1;
                    while($row = $result->fetch_assoc()):
                        $manager = $row['first_name'] ? htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) : '-';
                        $ban_until = $row['ban_until'];
                        $now_ts = time();
                        $status_label = '<span class="text-success">Active</span>';
                        $is_banned = false;
                        if ($ban_until) {
                            $ban_ts = strtotime($ban_until);
                            if ($ban_ts > $now_ts) {
                                $is_banned = true;
                                if ($ban_ts > strtotime('2100-01-01')) {
                                    $status_label = '<span class="badge bg-danger">Banned (Permanent)</span>';
                                } else {
                                    $remaining = ceil(($ban_ts - $now_ts) / 86400);
                                    $status_label = '<span class="badge bg-danger">Banned (' . $remaining . 'd)</span>';
                                }
                            }
                        }
                ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($row['team_name']) ?></td>
                        <td><?= $manager ?></td>
                        <td style="text-transform:uppercase"><?= htmlspecialchars($row['region']) ?></td>
                        <td><?= htmlspecialchars($row['rank']) ?></td>
                        <td><?= intval($row['current_size']) ?></td>
                        <td><?= intval($row['max_size']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars($row['practice_schedule']) ?></td>
                        <td><?= $status_label ?></td>
                        <td>
                            <button class="btn-minimal" title="Edit"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?= $row['team_id'] ?>"
                                data-name="<?= htmlspecialchars($row['team_name']) ?>"
                                data-manager="<?= $row['manager_id'] ?>"
                                data-region="<?= $row['region'] ?>"
                                data-rank="<?= $row['rank'] ?>"
                                data-max="<?= $row['max_size'] ?>"
                                data-desc="<?= htmlspecialchars($row['description']) ?>"
                                data-schedule="<?= htmlspecialchars($row['practice_schedule']) ?>"
                            ><i class="fa fa-pen"></i></button>

                            <?php if ($is_banned): ?>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Unban this team?');">
                                    <input type="hidden" name="unban_team_id" value="<?= $row['team_id'] ?>">
                                    <button type="submit" class="btn-minimal" title="Unban"><i class="fa fa-unlock"></i></button>
                                </form>
                            <?php else: ?>
                                <button class="btn-minimal" title="Ban"
                                    data-bs-toggle="modal"
                                    data-bs-target="#banModal"
                                    data-id="<?= $row['team_id'] ?>"
                                    data-name="<?= htmlspecialchars($row['team_name']) ?>"
                                ><i class="fa fa-ban"></i></button>
                            <?php endif; ?>

                            <form method="post" style="display:inline;" onsubmit="return confirm('ต้องการลบทีมนี้จริงหรือไม่?');">
                                <input type="hidden" name="delete_team_id" value="<?= $row['team_id'] ?>">
                                <button type="submit" class="btn-minimal" title="Delete">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted">No teams found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form method="post" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editModalLabel">Edit Team</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="edit_team_id" id="edit_team_id">
            <div class="mb-2">
                <label>Team Name</label>
                <input type="text" class="form-control" name="team_name" id="edit_team_name" required>
            </div>
            <div class="mb-2">
                <label>Manager</label>
                <select class="form-control" name="manager_id" id="edit_manager_id" required>
                    <option value="">-- Select Manager --</option>
                    <?php
                    $managers = $conn->query("SELECT user_id, first_name, last_name FROM users WHERE role='manager' OR role='admin'");
                    while($mgr = $managers->fetch_assoc()):
                    ?>
                    <option value="<?= $mgr['user_id'] ?>"><?= htmlspecialchars($mgr['first_name'] . ' ' . $mgr['last_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-2">
                <label>Region</label>
                <select class="form-control" name="region" id="edit_region" required>
                    <option value="na">NA</option>
                    <option value="eu">EU</option>
                    <option value="ap">AP</option>
                    <option value="kr">KR</option>
                    <option value="latam">LATAM</option>
                    <option value="br">BR</option>
                </select>
            </div>
            <div class="mb-2">
                <label>Rank</label>
                <select class="form-control" name="rank" id="edit_rank" required>
                    <option value="Unranked">Unranked</option>
                    <option value="Iron">Iron</option>
                    <option value="Bronze">Bronze</option>
                    <option value="Silver">Silver</option>
                    <option value="Gold">Gold</option>
                    <option value="Platinum">Platinum</option>
                    <option value="Diamond">Diamond</option>
                    <option value="Immortal">Immortal</option>
                    <option value="Radiant">Radiant</option>
                </select>
            </div>
            <div class="mb-2">
                <label>Max Size</label>
                <input type="number" class="form-control" name="max_size" id="edit_max_size" min="1" max="10" required>
            </div>
            <div class="mb-2">
                <label>Description</label>
                <textarea class="form-control" name="description" id="edit_description"></textarea>
            </div>
            <div class="mb-2">
                <label>Practice Schedule</label>
                <input type="text" class="form-control" name="practice_schedule" id="edit_practice_schedule">
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
    <div class="modal fade" id="banModal" tabindex="-1" aria-labelledby="banModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form method="post" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="banModalLabel">Ban Team</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="ban_team_id" id="ban_team_id">
            <div class="mb-2">
                <label>Team</label>
                <input type="text" class="form-control" id="ban_team_name" readonly>
            </div>
            <div class="mb-2">
                <label>Duration</label>
                <select class="form-control" name="ban_duration" required>
                    <option value="30">30 days</option>
                    <option value="90">90 days</option>
                    <option value="120">120 days</option>
                    <option value="365">1 year</option>
                    <option value="permanent">Permanent</option>
                </select>
            </div>
            <div class="mb-2">
                <label>Reason (optional)</label>
                <textarea class="form-control" name="ban_reason"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Ban Team</button>
          </div>
        </form>
      </div>
    </div>

    <script>
    // เติมข้อมูลลง Edit modal
    var editModal = document.getElementById('editModal');
    if (editModal) {
      editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('edit_team_id').value = button.getAttribute('data-id');
        document.getElementById('edit_team_name').value = button.getAttribute('data-name');
        document.getElementById('edit_manager_id').value = button.getAttribute('data-manager');
        document.getElementById('edit_region').value = button.getAttribute('data-region');
        document.getElementById('edit_rank').value = button.getAttribute('data-rank');
        document.getElementById('edit_max_size').value = button.getAttribute('data-max');
        document.getElementById('edit_description').value = button.getAttribute('data-desc');
        document.getElementById('edit_practice_schedule').value = button.getAttribute('data-schedule');
      });
    }

    // เติมข้อมูลลง Ban modal
    var banModal = document.getElementById('banModal');
    if (banModal) {
      banModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('ban_team_id').value = button.getAttribute('data-id');
        document.getElementById('ban_team_name').value = button.getAttribute('data-name');
      });
    }
    </script>
</body>
</html>