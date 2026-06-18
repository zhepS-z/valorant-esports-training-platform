<?php
define('ACCESS', true);
include '../utils/db.php';
include 'admin_auth.php';

$mapDir = dirname(__DIR__) . '/img/maps/';
$mapButtonDir = dirname(__DIR__) . '/img/maps_button/';

// Add map
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_map'])) {
    $name = trim($conn->real_escape_string($_POST['name'] ?? ''));
    $result = $conn->query("SELECT MAX(display_order) as max_order FROM valorant_maps");
    $row = $result->fetch_assoc();
    $display_order = ($row['max_order'] !== null) ? $row['max_order'] + 1 : 1;

    if (!empty($name)) {
        $filename = strtolower(preg_replace('/[^a-zA-Z0-9\-_]/', '', $name)) . '.png';
        $filename = ($filename === '.png') ? 'map_' . time() . '.png' : $filename;

        if (!empty($_FILES['map_image']['name']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['map_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $ext;
                $filepath1 = $mapDir . $filename;
                $filepath2 = $mapButtonDir . $filename;
                if (move_uploaded_file($_FILES['map_image']['tmp_name'], $filepath1)) {
                    // ถ้ามีการอพโหลด map_button_image เลยใช้นั้น ไม่ให้ copy
                    if (empty($_FILES['map_button_image']['name'])) {
                        copy($filepath1, $filepath2); // ใช้รูปเดียวกันถ้าไม่มี map_button_image
                    }
                    
                    // หากมีการอพโหลด map_button_image ให้ประมวลผล
                    $button_filename = $filename; // default ใช้ชื่อเดียวกัน
                    if (!empty($_FILES['map_button_image']['name']) && $_FILES['map_button_image']['error'] === UPLOAD_ERR_OK) {
                        $btn_ext = strtolower(pathinfo($_FILES['map_button_image']['name'], PATHINFO_EXTENSION));
                        if (in_array($btn_ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                            $button_filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $btn_ext;
                            $filepath2_custom = $mapButtonDir . $button_filename;
                            if (move_uploaded_file($_FILES['map_button_image']['tmp_name'], $filepath2_custom)) {
                                // สำเร็จ
                            } else {
                                $button_filename = $filename; // fallback
                            }
                        }
                    }
                    
                    $btn_esc = $conn->real_escape_string($button_filename);
                    $conn->query("INSERT INTO valorant_maps (name, image_filename, button_image_filename, display_order) VALUES ('$name', '$filename', '$btn_esc', $display_order)");
                    header("Location: map_table.php?msg=added");
                    exit;
                }
            }
        } else {
            // ถ้าไม่มีไฟล์ ให้ค้นหาไฟล์จริงในโฟลเดอร์ เพื่อเก็บนามสกุลไฟล์ที่ถูกต้อง
            $button_filename = $filename; // default ชื่อเริ่มต้น (อาจเป็น .png)
            $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME); // ชื่อไฟล์ไม่มีนามสกุล
            
            // ค้นหาไฟล์จริงที่มีชื่อเดียวกัน (พร้อมนามสกุล)
            $foundFiles = glob($mapButtonDir . $filenameWithoutExt . '.*');
            if (!empty($foundFiles)) {
                $button_filename = basename($foundFiles[0]); // เอาชื่อไฟล์ที่พบครั้งแรก
            }
            
            $btn_esc = $conn->real_escape_string($button_filename);
            $conn->query("INSERT INTO valorant_maps (name, image_filename, button_image_filename, display_order) VALUES ('$name', '$filename', '$btn_esc', $display_order)");
            header("Location: map_table.php?msg=added");
            exit;
        }
    }
}

// Edit map
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_map'])) {
    $id = intval($_POST['edit_map_id']);
    $name = trim($conn->real_escape_string($_POST['edit_name'] ?? ''));
    $old = $conn->query("SELECT display_order FROM valorant_maps WHERE id=$id")->fetch_assoc();
    $display_order = $old['display_order'] ?? 1;
    $is_active = isset($_POST['edit_is_active']) ? 1 : 0;

    if ($id > 0 && !empty($name)) {
        $old = $conn->query("SELECT image_filename, button_image_filename FROM valorant_maps WHERE id=$id")->fetch_assoc();
        $filename = $old['image_filename'] ?? strtolower(preg_replace('/[^a-zA-Z0-9\-_]/', '', $name)) . '.png';
        $button_filename = $old['button_image_filename'] ?? $filename;

        if (!empty($_FILES['edit_map_image']['name']) && $_FILES['edit_map_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['edit_map_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $ext;
                $filepath1 = $mapDir . $filename;
                $filepath2 = $mapButtonDir . $button_filename;
                if (move_uploaded_file($_FILES['edit_map_image']['tmp_name'], $filepath1)) {
                    // ถ้าไม่มี edit_map_button_image ให้ copy map image ไป button
                    if (empty($_FILES['edit_map_button_image']['name'])) {
                        copy($filepath1, $filepath2);
                        $button_filename = $filename;
                    }
                }
            }
        } else {
            // ถ้าไม่มีการอพโหลด edit_map_image ใหม่ ให้ตรวจสอบว่า button_filename มีนามสกุลถูกไหม
            // โดยค้นหาไฟล์จริงในโฟลเดอร์
            $btnFileWithoutExt = pathinfo($button_filename, PATHINFO_FILENAME);
            $foundFiles = glob($mapButtonDir . $btnFileWithoutExt . '.*');
            if (!empty($foundFiles)) {
                $button_filename = basename($foundFiles[0]); // เอาไฟล์ที่พบ
            }
        }
        
        // หากมีการอพโหลด edit_map_button_image ให้ประมวลผล
        if (!empty($_FILES['edit_map_button_image']['name']) && $_FILES['edit_map_button_image']['error'] === UPLOAD_ERR_OK) {
            $btn_ext = strtolower(pathinfo($_FILES['edit_map_button_image']['name'], PATHINFO_EXTENSION));
            if (in_array($btn_ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                $button_filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $btn_ext;
                $filepath2 = $mapButtonDir . $button_filename;
                move_uploaded_file($_FILES['edit_map_button_image']['tmp_name'], $filepath2);
            }
        }
        $fn_esc = $conn->real_escape_string($filename);
        $btn_esc = $conn->real_escape_string($button_filename);
        $conn->query("UPDATE valorant_maps SET name='$name', image_filename='$fn_esc', button_image_filename='$btn_esc', display_order=$display_order, is_active=$is_active WHERE id=$id");
        header("Location: map_table.php?msg=updated");
        exit;
    }
}

// Delete map
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_map_id'])) {
    $id = intval($_POST['delete_map_id']);
    if ($id > 0) {
        $conn->query("DELETE FROM valorant_maps WHERE id=$id");
        header("Location: map_table.php?msg=deleted");
        exit;
    }
}

// Auto-fix file extensions หากมี parameter refresh_ext
if (isset($_GET['refresh_ext']) && $_GET['refresh_ext'] === '1') {
    $res = $conn->query("SELECT id, image_filename, button_image_filename FROM valorant_maps");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            // Fix image_filename
            $imgFileWithoutExt = pathinfo($row['image_filename'], PATHINFO_FILENAME);
            $foundImgFiles = glob($mapDir . $imgFileWithoutExt . '.*');
            if (!empty($foundImgFiles)) {
                $correctImgFilename = basename($foundImgFiles[0]);
                $correctImgFilename = $conn->real_escape_string($correctImgFilename);
                $conn->query("UPDATE valorant_maps SET image_filename = '$correctImgFilename' WHERE id = " . intval($row['id']));
            }
            
            // Fix button_image_filename
            $btnFileWithoutExt = pathinfo($row['button_image_filename'], PATHINFO_FILENAME);
            $foundFiles = glob($mapButtonDir . $btnFileWithoutExt . '.*');
            if (!empty($foundFiles)) {
                $correctFilename = basename($foundFiles[0]);
                $correctFilename = $conn->real_escape_string($correctFilename);
                $conn->query("UPDATE valorant_maps SET button_image_filename = '$correctFilename' WHERE id = " . intval($row['id']));
            }
        }
    }
    header("Location: map_table.php?msg=refreshed");
    exit;
}

$maps = [];
$res = $conn->query("SELECT * FROM valorant_maps ORDER BY display_order ASC, name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) $maps[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>จัดการ Map | Valorant Esports Admin</title>
    <?php include '../utils/link.php'; ?>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="container">
        <br>
        <div class="page-header">
            <h1 class="page-title">จัดการ Map (VALORANT)</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fa fa-plus me-2"></i>เพิ่ม Map
            </button>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?= $_GET['msg'] === 'deleted' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
            <?php
            if ($_GET['msg'] === 'added') echo 'เพิ่ม Map สำเร็จ';
            elseif ($_GET['msg'] === 'updated') echo 'แก้ไข Map สำเร็จ';
            elseif ($_GET['msg'] === 'deleted') echo 'ลบ Map สำเร็จ';
            elseif ($_GET['msg'] === 'refreshed') echo 'รีเฟรชนามสกุลไฟล์สำเร็จ ✓';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-body">
                <p class="mb-0"><small class="text-muted">💡 ถ้ารูป button ไม่แสดงถูก (นามสกุลไฟล์ผิด) ให้กดปุ่มด้านล่าง</small></p>
                <a href="?refresh_ext=1" class="btn btn-sm btn-info mt-2" onclick="return confirm('รีเฟรชนามสกุลไฟล์ทั้งหมด?')">
                    <i class="fa fa-sync me-1"></i>รีเฟรชนามสกุลไฟล์
                </a>
            </div>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>รูป Full</th>
                        <th>รูป Preview</th>
                        <th>ชื่อ Map</th>
                        <th>ไฟล์</th>
                        <th>สถานะ</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($maps)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fa fa-map fa-3x mb-3" style="color: var(--text-secondary);"></i>
                            <p>ยังไม่มี Map ในระบบ</p>
                            <p class="small">กรุณา Run ไฟล์ database/migrate_agents_maps.sql เพื่อ seed ข้อมูลเริ่มต้น</p>
                        </td>
                    </tr>
                <?php else: ?>
                <?php foreach ($maps as $i => $m): 
                    $imgPath = '../img/maps/' . $m['image_filename'];
                    $btnFile = $m['button_image_filename'] ?? $m['image_filename']; // ใช้ button_image_filename ถ้ามี
                    $imgButtonPath = '../img/maps_button/' . $btnFile;
                    $imgExists = file_exists($mapDir . $m['image_filename']);
                    $btnExists = file_exists($mapButtonDir . $btnFile);
                ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <?php if ($imgExists): ?>
                                <img src="<?= htmlspecialchars($imgPath) ?>" alt="" style="width:140px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--border);" title="<?= htmlspecialchars($m['name']) ?>">
                            <?php else: ?>
                                <span class="text-muted small">ไม่มีไฟล์</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($btnExists): ?>
                                <div style="position:relative;width:72px;height:72px;border-radius:12px;overflow:hidden;border:1px solid var(--border);background:#1a1a1a;">
                                    <img src="<?= htmlspecialchars($imgButtonPath) ?>" alt="" style="width:100%;height:100%;object-fit:cover;" title="<?= htmlspecialchars($m['name']) ?>">
                                    <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(to top, rgba(0,0,0,0.9), transparent);padding:2px 4px;color:#fff;font-size:10px;font-weight:600;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?= htmlspecialchars(substr($m['name'], 0, 8)) ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">ไม่มี</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($m['name']) ?></td>
                        <td><code><?= htmlspecialchars($m['image_filename']) ?></code></td>
                        <td>
                            <?php if ($m['is_active']): ?>
                                <span class="badge badge-player">เปิดใช้งาน</span>
                            <?php else: ?>
                                <span class="badge badge-ban">ปิด</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-icon btn-edit" title="แก้ไข" data-bs-toggle="modal" data-bs-target="#editModal"
                                data-id="<?= $m['id'] ?>"
                                data-name="<?= htmlspecialchars($m['name']) ?>"
                                data-filename="<?= htmlspecialchars($m['image_filename']) ?>"
                                data-active="<?= $m['is_active'] ?>"
                            ><i class="fa fa-pen"></i></button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('ต้องการลบ Map นี้หรือไม่?');">
                                <input type="hidden" name="delete_map_id" value="<?= $m['id'] ?>">
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
                    <h5 class="modal-title">เพิ่ม Map</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_map" value="1">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ Map</label>
                        <input type="text" class="form-control" name="name" required placeholder="เช่น Kasbah, District">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รูป Map Full (จำเป็น)</label>
                        <input type="file" class="form-control" name="map_image" accept="image/png,image/jpeg,image/jpg,image/webp" required>
                        <small class="text-muted">สำหรับแสดงในหน้า strategy</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รูป Map Button Preview (ไม่บังคับ)</label>
                        <input type="file" class="form-control" name="map_button_image" accept="image/png,image/jpeg,image/jpg,image/webp">
                        <small class="text-muted">หากไม่อพโหลด จะสำเร็จการทำงานโดยคัดลอกจากรูป Map Full</small>
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
                    <h5 class="modal-title">แก้ไข Map</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_map" value="1">
                    <input type="hidden" name="edit_map_id" id="edit_map_id">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ Map</label>
                        <input type="text" class="form-control" name="edit_name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อัปโหลดรูป Map Full ใหม่ (ไม่บังคับ)</label>
                        <input type="file" class="form-control" name="edit_map_image" accept="image/png,image/jpeg,image/jpg,image/webp">
                        <small class="text-muted">สำหรับแสดงในหน้า strategy</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">อัปโหลดรูป Map Button Preview ใหม่ (ไม่บังคับ)</label>
                        <input type="file" class="form-control" name="edit_map_button_image" accept="image/png,image/jpeg,image/jpg,image/webp">
                        <small class="text-muted">สำหรับแสดงในตารางและแดชบอร์ด</small>
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
        document.getElementById('edit_map_id').value = btn.getAttribute('data-id');
        document.getElementById('edit_name').value = btn.getAttribute('data-name');
        document.getElementById('edit_is_active').checked = btn.getAttribute('data-active') == '1';
    });
    </script>
</body>
</html>
