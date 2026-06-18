<?php
define('ACCESS', true);
include '../utils/db.php';
include 'admin_auth.php';

// อัปเดตข้อมูล pending user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_pending_id'])) {
    $edit_id = intval($_POST['edit_pending_id']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $riot_id = $conn->real_escape_string($_POST['riot_id']);
    $role = $conn->real_escape_string($_POST['role']);
    $region = $conn->real_escape_string($_POST['region']);

    $sql_update = "UPDATE pending_users SET 
        first_name='$first_name',
        last_name='$last_name',
        email='$email',
        riot_id='$riot_id',
        role='$role',
        region='$region'
        WHERE id=$edit_id";
    $conn->query($sql_update);
    header("Location: pending_user.php");
    exit;
}

// ลบ pending user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pending_id'])) {
    $delete_id = intval($_POST['delete_pending_id']);
    $conn->query("DELETE FROM pending_users WHERE id=$delete_id");
    header("Location: pending_user.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Users Management | Valorant Esports</title>
    <?php include '../utils/link.php'; ?>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>
    <div class="container">        <br>

        <div class="page-header">
            <h1 class="page-title">Pending Users Management</h1>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Riot ID</th>
                        <th>Role</th>
                        <th>Region</th>
                        <th>OTP Code</th>
                        <th>OTP Expiry</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "SELECT * FROM pending_users ORDER BY id DESC";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0):
                    $i = 1;
                    while($row = $result->fetch_assoc()):
                        // Role badge class
                        $roleClass = "badge badge-role";
                        if ($row['role'] == 'player') $roleClass .= " badge-player";
                        elseif ($row['role'] == 'coach') $roleClass .= " badge-coach";
                        elseif ($row['role'] == 'admin') $roleClass .= " badge-admin";
                        elseif ($row['role'] == 'manager') $roleClass .= " badge-manager";
                ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['riot_id']) ?></td>
                        <td><span class="<?= $roleClass ?>"><?= htmlspecialchars(ucfirst($row['role'])) ?></span></td>
                        <td style="text-transform:uppercase"><?= htmlspecialchars($row['region']) ?></td>
                        <td><?= htmlspecialchars($row['otp_code']) ?></td>
                        <td><?= htmlspecialchars($row['otp_expiry']) ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon btn-edit" title="Edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal"
                                    data-id="<?= $row['id'] ?>"
                                    data-first="<?= htmlspecialchars($row['first_name']) ?>"
                                    data-last="<?= htmlspecialchars($row['last_name']) ?>"
                                    data-email="<?= htmlspecialchars($row['email']) ?>"
                                    data-riot="<?= htmlspecialchars($row['riot_id']) ?>"
                                    data-role="<?= $row['role'] ?>"
                                    data-region="<?= $row['region'] ?>"
                                ><i class="fa fa-pen"></i></button>
                                
                                <form method="post" style="display:inline;" onsubmit="return confirm('ต้องการลบผู้ใช้นี้จริงหรือไม่?');">
                                    <input type="hidden" name="delete_pending_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn-icon btn-delete" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fa fa-users fa-3x mb-3" style="color: var(--text-secondary);"></i>
                            <p>No pending users found.</p>
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
            <h5 class="modal-title">Edit Pending User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="edit_pending_id" id="edit_pending_id">
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

<script>
var editModal = document.getElementById('editModal');
if (editModal) {
  editModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    document.getElementById('edit_pending_id').value = button.getAttribute('data-id');
    document.getElementById('edit_first_name').value = button.getAttribute('data-first');
    document.getElementById('edit_last_name').value = button.getAttribute('data-last');
    document.getElementById('edit_email').value = button.getAttribute('data-email');
    document.getElementById('edit_riot_id').value = button.getAttribute('data-riot');
    document.getElementById('edit_role').value = button.getAttribute('data-role');
    document.getElementById('edit_region').value = button.getAttribute('data-region');
  });
}
</script>
</body>
</html>