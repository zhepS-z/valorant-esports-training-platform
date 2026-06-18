<?php
define('ACCESS', true);
include '../utils/db.php';
include 'admin_auth.php';

$uploadDir = dirname(__DIR__) . '/img/agents/';

// Add agent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_agent'])) {
    $name = trim($conn->real_escape_string($_POST['name'] ?? ''));
    $role = $conn->real_escape_string($_POST['role'] ?? 'Duelist');
    $image_url = trim($_POST['image_url'] ?? '');
    $result = $conn->query("SELECT MAX(display_order) as max_order FROM valorant_agents");
    $row = $result->fetch_assoc();
    $display_order = ($row['max_order'] !== null) ? $row['max_order'] + 1 : 1;

    if (!empty($name) && in_array($role, ['Controller', 'Sentinel', 'Initiator', 'Duelist'])) {
        // Handle file upload
        if (!empty($_FILES['agent_image']['name']) && $_FILES['agent_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['agent_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '', $name) . '_' . time() . '.' . $ext;
                $filepath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['agent_image']['tmp_name'], $filepath)) {
                    $image_url = 'img/agents/' . $filename;
                }
            }
        }
        if (empty($image_url)) $image_url = null;  // Will use icon as fallback
        $img_esc = $conn->real_escape_string($image_url);
        $conn->query("INSERT INTO valorant_agents (name, role, image_url, display_order) VALUES ('$name', '$role', '$img_esc', $display_order)");
        header("Location: agent_table.php?msg=added");
        exit;
    }
}

// Edit agent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_agent'])) {
    $id = intval($_POST['edit_agent_id']);
    $name = trim($conn->real_escape_string($_POST['edit_name'] ?? ''));
    $role = $conn->real_escape_string($_POST['edit_role'] ?? 'Duelist');
    $image_url = trim($_POST['edit_image_url'] ?? '');
    $old = $conn->query("SELECT display_order FROM valorant_agents WHERE id=$id")->fetch_assoc();
    $display_order = $old['display_order'] ?? 1;
    $is_active = isset($_POST['edit_is_active']) ? 1 : 0;

    if ($id > 0 && !empty($name)) {
        if (!empty($_FILES['edit_agent_image']['name']) && $_FILES['edit_agent_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['edit_agent_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $filename = preg_replace('/[^a-zA-Z0-9\-_]/', '', $name) . '_' . time() . '.' . $ext;
                $filepath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['edit_agent_image']['tmp_name'], $filepath)) {
                    $image_url = 'img/agents/' . $filename;
                }
            }
        }
        $img_esc = $conn->real_escape_string($image_url);
        $conn->query("UPDATE valorant_agents SET name='$name', role='$role', image_url='$img_esc', display_order=$display_order, is_active=$is_active WHERE id=$id");
        header("Location: agent_table.php?msg=updated");
        exit;
    }
}

// Delete agent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_agent_id'])) {
    $id = intval($_POST['delete_agent_id']);
    if ($id > 0) {
        $conn->query("DELETE FROM valorant_agents WHERE id=$id");
        header("Location: agent_table.php?msg=deleted");
        exit;
    }
}

$agents = [];
$res = $conn->query("SELECT * FROM valorant_agents ORDER BY display_order ASC, name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) $agents[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>จัดการ Agent | Valorant Esports Admin</title>
    <?php include '../utils/link.php'; ?>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="container">
        <br>
        <div class="page-header">
            <h1 class="page-title">จัดการ Agent (VALORANT)</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fa fa-plus me-2"></i>เพิ่ม Agent
            </button>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?= $_GET['msg'] === 'deleted' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
            <?php
            if ($_GET['msg'] === 'added') echo 'เพิ่ม Agent สำเร็จ';
            elseif ($_GET['msg'] === 'updated') echo 'แก้ไข Agent สำเร็จ';
            elseif ($_GET['msg'] === 'deleted') echo 'ลบ Agent สำเร็จ';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>รูป</th>
                        <th>ชื่อ</th>
                        <th>Role</th>
                        <th>สถานะ</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($agents)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fa fa-user-secret fa-3x mb-3" style="color: var(--text-secondary);"></i>
                            <p>ยังไม่มี Agent ในระบบ</p>
                            <p class="small">กรุณา Run ไฟล์ database/migrate_agents_maps.sql เพื่อ seed ข้อมูลเริ่มต้น</p>
                        </td>
                    </tr>
                <?php else: ?>
                <?php foreach ($agents as $i => $a): 
                    $imgSrc = (strpos($a['image_url'], 'http') === 0) ? $a['image_url'] : '../' . $a['image_url'];
                ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><img src="<?= htmlspecialchars($imgSrc) ?>" alt="" class="profile-img" style="width:48px;height:48px;object-fit:cover;border-radius:50%;" onerror="this.outerHTML='<div style=\"display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:50%;background:#6c757d;\"><i class=\\\"fas fa-user-ninja\\\" style=\\\"color:white;font-size:24px;\\\"></i></div></td>
                        <td><?= htmlspecialchars($a['name']) ?></td>
                        <td><span class="badge badge-role"><?= htmlspecialchars($a['role']) ?></span></td>
                        <td>
                            <?php if ($a['is_active']): ?>
                                <span class="badge badge-player">เปิดใช้งาน</span>
                            <?php else: ?>
                                <span class="badge badge-ban">ปิด</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-icon btn-edit" title="แก้ไข" data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="<?= $a['id'] ?>"
                                data-name="<?= htmlspecialchars($a['name']) ?>"
                                data-role="<?= htmlspecialchars($a['role']) ?>"
                                data-image="<?= htmlspecialchars($a['image_url']) ?>"
                                data-active="<?= $a['is_active'] ?>"
                            ><i class="fa fa-pen"></i></button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('ต้องการลบ Agent นี้หรือไม่?');">
                                <input type="hidden" name="delete_agent_id" value="<?= $a['id'] ?>">
                                <button type="submit" class="btn-icon btn-delete" title="ลบ"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่ม Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_agent" value="1">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ Agent</label>
                        <input type="text" class="form-control" name="name" required placeholder="เช่น Iso, Vyse">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-control" name="role" required>
                            <option value="Controller">Controller</option>
                            <option value="Sentinel">Sentinel</option>
                            <option value="Initiator">Initiator</option>
                            <option value="Duelist">Duelist</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL รูปภาพ (Valorant API หรือลิงก์อื่น)</label>
                        <input type="url" class="form-control" name="image_url" placeholder="https://media.valorant-api.com/agents/xxx/displayicon.png">
                        <small class="text-muted">หรืออัปโหลดรูปด้านล่าง</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อัปโหลดรูป (ถ้ามี)</label>
                        <input type="file" class="form-control" name="agent_image" accept="image/png,image/jpeg,image/jpg,image/webp">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไข Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_agent" value="1">
                    <input type="hidden" name="edit_agent_id" id="edit_agent_id">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ Agent</label>
                        <input type="text" class="form-control" name="edit_name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-control" name="edit_role" id="edit_role">
                            <option value="Controller">Controller</option>
                            <option value="Sentinel">Sentinel</option>
                            <option value="Initiator">Initiator</option>
                            <option value="Duelist">Duelist</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL รูปภาพ</label>
                        <input type="text" class="form-control" name="edit_image_url" id="edit_image_url">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อัปโหลดรูปใหม่ (แทนที่)</label>
                        <input type="file" class="form-control" name="edit_agent_image" accept="image/png,image/jpeg,image/jpg,image/webp">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="edit_is_active" id="edit_is_active">
                        <label class="form-check-label" for="edit_is_active">เปิดใช้งาน</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('editModal')?.addEventListener('show.bs.modal', function(e) {
        var btn = e.relatedTarget;
        document.getElementById('edit_agent_id').value = btn.getAttribute('data-id');
        document.getElementById('edit_name').value = btn.getAttribute('data-name');
        document.getElementById('edit_role').value = btn.getAttribute('data-role');
        document.getElementById('edit_image_url').value = btn.getAttribute('data-image');
        document.getElementById('edit_is_active').checked = btn.getAttribute('data-active') == '1';
    });
    </script>
</body>
</html>
