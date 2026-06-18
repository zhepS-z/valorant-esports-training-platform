<?php
define('ACCESS', true);
include '../utils/db.php';
include 'admin_auth.php';

// อัปเดตข้อมูลสมาชิกทีม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_member_id'])) {
    $edit_id = intval($_POST['edit_member_id']);
    $team_id = intval($_POST['team_id']);
    $user_id = intval($_POST['user_id']);
    $role_in_team = $conn->real_escape_string($_POST['role_in_team']);

    $sql_update = "UPDATE team_members SET 
        team_id=$team_id,
        user_id=$user_id,
        role_in_team='$role_in_team'
        WHERE id=$edit_id";
    $conn->query($sql_update);
    header("Location: team_member.php");
    exit;
}

// ลบสมาชิกทีม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_member_id'])) {
    $delete_id = intval($_POST['delete_member_id']);
    $conn->query("DELETE FROM team_members WHERE id=$delete_id");
    header("Location: team_member.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Members Management | Valorant Esports</title>
    <?php include '../utils/link.php'; ?>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        /* เพิ่มสไตล์ที่เหมือนกับ user_table.php */
        .table tbody tr:hover {
            transform: scale(1.01);
        }
        .empty-state {
            text-align: center;
            color: var(--text-secondary);
        }
        .action-buttons .btn-icon {
            margin-right: 5px;
        }
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container">        <br>

        <div class="page-header">
            <h1 class="page-title">Team Members Management</h1>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Team</th>
                        <th>User</th>
                        <th>Role in Team</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "SELECT m.*, t.team_name, u.first_name, u.last_name 
                        FROM team_members m
                        LEFT JOIN teams t ON m.team_id = t.team_id
                        LEFT JOIN users u ON m.user_id = u.user_id
                        ORDER BY m.id DESC";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0):
                    $i = 1;
                    while($row = $result->fetch_assoc()):
                        $user = $row['first_name'] ? htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) : '-';
                        $team = $row['team_name'] ? htmlspecialchars($row['team_name']) : '-';
                ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= $team ?></td>
                        <td><?= $user ?></td>
                        <td><?= htmlspecialchars($row['role_in_team']) ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon btn-edit" title="Edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal"
                                    data-id="<?= $row['id'] ?>"
                                    data-team="<?= $row['team_id'] ?>"
                                    data-user="<?= $row['user_id'] ?>"
                                    data-role="<?= htmlspecialchars($row['role_in_team']) ?>"
                                ><i class="fa fa-pen"></i></button>
                                
                                <form method="post" style="display:inline;" onsubmit="return confirm('ต้องการลบสมาชิกนี้จริงหรือไม่?');">
                                    <input type="hidden" name="delete_member_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn-icon btn-delete" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="fa fa-users fa-3x mb-3" style="color: var(--text-secondary);"></i>
                            <p>No team members found.</p>
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
            <h5 class="modal-title">Edit Team Member</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="edit_member_id" id="edit_member_id">
            <div class="mb-3">
                <label class="form-label">Team</label>
                <select class="form-control" name="team_id" id="edit_team_id" required>
                    <option value="">-- Select Team --</option>
                    <?php
                    $teams = $conn->query("SELECT team_id, team_name FROM teams");
                    while($team = $teams->fetch_assoc()):
                    ?>
                    <option value="<?= $team['team_id'] ?>"><?= htmlspecialchars($team['team_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">User</label>
                <select class="form-control" name="user_id" id="edit_user_id" required>
                    <option value="">-- Select User --</option>
                    <?php
                    $users = $conn->query("SELECT user_id, first_name, last_name FROM users");
                    while($user = $users->fetch_assoc()):
                    ?>
                    <option value="<?= $user['user_id'] ?>"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Role in Team</label>
                <input type="text" class="form-control" name="role_in_team" id="edit_role_in_team">
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
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.table tbody tr');
    rows.forEach(row => {
        row.style.transition = 'transform 0.2s ease, background 0.2s ease';
    });
});

var editModal = document.getElementById('editModal');
if (editModal) {
  editModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    document.getElementById('edit_member_id').value = button.getAttribute('data-id');
    document.getElementById('edit_team_id').value = button.getAttribute('data-team');
    document.getElementById('edit_user_id').value = button.getAttribute('data-user');
    document.getElementById('edit_role_in_team').value = button.getAttribute('data-role');
  });
}
</script>
</body>
</html>