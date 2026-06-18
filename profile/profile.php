<?php 
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
require_once '../utils/db.php'; // เชื่อมต่อฐานข้อมูล
require_once '../utils/game_assets.php'; // โหลดฟังก์ชันดึง agent จาก DB

session_start();

// สมมติว่า riot_id เก็บใน session ตอน login
$riot_id = isset($_SESSION['riot_id']) ? $_SESSION['riot_id'] : null;

$user = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'riot_id' => '',
    'role' => '',
    'team_id' => '',
    'region' => ''
];

// เพิ่มการอัปโหลดรูปโปรไฟล์
$profile_img = $user['profile_img'] ?? 'img/default_avatar.png';

// ส่วนนี้เพิ่มสำหรับการอัปเดตข้อมูล
$update_error = '';
$update_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    // อ่าน $_POST['first_name'] ... เฉพาะฟอร์มแก้ไขโปรไฟล์
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $riot_id_new = trim($_POST['riot_id']);
    $current_password = $_POST['current_password'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ตรวจสอบความถูกต้องของข้อมูล
    $validation_errors = [];

    // ตรวจสอบฟิลด์บังคับต่างๆ
    if (empty($first_name)) {
        $validation_errors[] = 'กรุณากรอก Name';
    }
    if (empty($last_name)) {
        $validation_errors[] = 'กรุณากรอก Lastname';
    }
    if (empty($riot_id_new)) {
        $validation_errors[] = 'กรุณากรอก Riot ID';
    }
    if (empty($email)) {
        $validation_errors[] = 'กรุณากรอก Email';
    }

    // ตรวจสอบ Email ไม่ซ้ำ (ถ้ามีการเปลี่ยนแปลง)
    if (!empty($email) && $email !== $user['email']) {
        $stmt_check = $conn->prepare("SELECT riot_id FROM users WHERE email = ? AND riot_id != ?");
        $stmt_check->bind_param("ss", $email, $riot_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $validation_errors[] = 'Email นี้ถูกใช้งานแล้ว';
        }
        $stmt_check->close();
    }

    // ตรวจสอบ Riot ID ไม่ซ้ำ (ถ้ามีการเปลี่ยนแปลง)
    if (!empty($riot_id_new) && $riot_id_new !== $user['riot_id']) {
        $stmt_check = $conn->prepare("SELECT riot_id FROM users WHERE riot_id = ? AND riot_id != ?");
        $stmt_check->bind_param("ss", $riot_id_new, $riot_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $validation_errors[] = 'Riot ID นี้ถูกใช้งานแล้ว';
        }
        $stmt_check->close();
    }

    // ถ้าผู้ใช้กรอกรหัสผ่านใหม่ ต้องตรวจสอบหลาย ๆ อย่าง
    if (!empty($password) || !empty($confirm_password) || !empty($current_password)) {
        // ต้องกรอกทั้ง 3 ฟิลด์
        if (empty($current_password)) {
            $validation_errors[] = 'กรุณากรอก Current Password';
        }
        if (empty($password)) {
            $validation_errors[] = 'กรุณากรอก New Password';
        }
        if (empty($confirm_password)) {
            $validation_errors[] = 'กรุณากรอก Confirm New Password';
        }

        // ตรวจสอบ Current Password ถูกต้อง
        if (!empty($current_password)) {
            $stmt_pwd = $conn->prepare("SELECT password FROM users WHERE riot_id = ?");
            $stmt_pwd->bind_param("s", $riot_id);
            $stmt_pwd->execute();
            $result_pwd = $stmt_pwd->get_result();
            if ($result_pwd && $result_pwd->num_rows > 0) {
                $pwd_row = $result_pwd->fetch_assoc();
                if (!password_verify($current_password, $pwd_row['password'])) {
                    $validation_errors[] = 'Current Password ไม่ถูกต้อง';
                }
            }
            $stmt_pwd->close();
        }

        // ตรวจสอบ New Password กับ Confirm New Password ตรงกัน
        if (!empty($password) && !empty($confirm_password) && $password !== $confirm_password) {
            $validation_errors[] = 'New Password กับ Confirm New Password ไม่ตรงกัน';
        }

        // ตรวจสอบ New Password ไม่ควรเหมือน Current Password
        if (!empty($current_password) && !empty($password) && $current_password === $password) {
            $validation_errors[] = 'รหัสผ่านใหม่ต้องไม่เหมือนกับรหัสผ่านเดิม';
        }
    }

    // ถ้าไม่มีข้อผิดพลาด ให้ทำการอัปเดต
    if (empty($validation_errors)) {
        // ถ้ามีการกรอกรหัสผ่านใหม่และตรงกัน ให้เปลี่ยนรหัสผ่าน
        if (!empty($password) && $password === $confirm_password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, riot_id=?, password=? WHERE riot_id=?");
            $stmt->bind_param("ssssss", $first_name, $last_name, $email, $riot_id_new, $password_hash, $riot_id);
        } else {
            // ไม่เปลี่ยนรหัสผ่าน
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, riot_id=? WHERE riot_id=?");
            $stmt->bind_param("sssss", $first_name, $last_name, $email, $riot_id_new, $riot_id);
        }
        
        if ($stmt->execute()) {
            $update_success = 'บันทึกข้อมูลสำเร็จ';
            
            // อัปโหลดไฟล์โปรไฟล์ (ทำการเปลี่ยนเฉพาะเมื่อ update profile สำเร็จ)
            if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowed)) {
                    $newName = '../img/profile/' . uniqid('profile_', true) . '.' . $ext;
                    move_uploaded_file($_FILES['profile_img']['tmp_name'], $newName);
                    // อัปเดต path ใน database
                }
            } elseif (isset($_POST['agent_img'])) {
                // เลือกรูป agent
                $profile_img = $_POST['agent_img'];
                $stmt_avatar = $conn->prepare("UPDATE users SET profile_img=? WHERE riot_id=?");
                $stmt_avatar->bind_param("ss", $profile_img, $riot_id_new);
                $stmt_avatar->execute();
                $stmt_avatar->close();
            }

            // อัปเดต session ถ้า riot_id เปลี่ยน
            if ($riot_id !== $riot_id_new) {
                $_SESSION['riot_id'] = $riot_id_new;
                $riot_id = $riot_id_new;
            }

            // โหลดข้อมูลใหม่ (รวม profile_img)
            $stmt_reload = $conn->prepare("SELECT first_name, last_name, email, riot_id, role, team_id, region, profile_img FROM users WHERE riot_id = ?");
            $stmt_reload->bind_param("s", $riot_id);
            $stmt_reload->execute();
            $result_reload = $stmt_reload->get_result();
            if ($result_reload && $result_reload->num_rows > 0) {
                $user = $result_reload->fetch_assoc();
                $profile_img = $user['profile_img'] ?: 'img/default_avatar.png';
            }
            $stmt_reload->close();
        } else {
            $update_error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
        }
        $stmt->close();
    } else {
        $update_error = implode(', ', $validation_errors);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_avatar'])) {
    // เฉพาะอัปเดตรูปโปรไฟล์ ไม่ต้องอ่าน $_POST['first_name'] ฯลฯ
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $newName = '../img/profile/' . uniqid('profile_', true) . '.' . $ext;
            move_uploaded_file($_FILES['profile_img']['tmp_name'], $newName);
            $profile_img = $newName;
            $stmt = $conn->prepare("UPDATE users SET profile_img=? WHERE riot_id=?");
            $stmt->bind_param("ss", $profile_img, $riot_id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (isset($_POST['agent_img'])) {
        $profile_img = $_POST['agent_img'];
        $stmt = $conn->prepare("UPDATE users SET profile_img=? WHERE riot_id=?");
        $stmt->bind_param("ss", $profile_img, $riot_id);
        $stmt->execute();
        $stmt->close();
    }
    // โหลดข้อมูลใหม่
    $stmt = $conn->prepare("SELECT first_name, last_name, email, riot_id, role, team_id, region, profile_img FROM users WHERE riot_id = ?");
    $stmt->bind_param("s", $riot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $profile_img = $user['profile_img'] ?: 'img/default_avatar.png';
    }
    $stmt->close();
} else if ($riot_id) {
    $stmt = $conn->prepare("SELECT first_name, last_name, email, riot_id, role, team_id, region, profile_img FROM users WHERE riot_id = ?");
    $stmt->bind_param("s", $riot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $profile_img = $user['profile_img'] ?: 'img/default_avatar.png';
    }
    $stmt->close();
} 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_avatar'])) {
    $default_avatar = 'img/default_avatar.png';
    $stmt = $conn->prepare("UPDATE users SET profile_img=? WHERE riot_id=?");
    $stmt->bind_param("ss", $default_avatar, $riot_id);
    $stmt->execute();
    $stmt->close();
    $profile_img = $default_avatar;
}

// Fetch team info and normalize logo path
$team_name = '';
$team_logo = '../img/default_team.png';
$is_manager = false;
if (!empty($user['team_id'])) {
    $teamId = (int)$user['team_id'];
    $stmt = $conn->prepare("SELECT team_name, team_logo, manager_id FROM teams WHERE team_id = ?");
    $stmt->bind_param("i", $teamId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $team_name = $row['team_name'] ?? '';
        $manager_id = (int)($row['manager_id'] ?? 0);
        $is_manager = ($manager_id === (int)$_SESSION['user_id']);

        $rawLogo = $row['team_logo'] ?? '';
        if (!empty($rawLogo)) {
            // if raw already includes ../ or is an absolute web path, use as-is; else prepend ../
            if (strpos($rawLogo, '../') === 0 || strpos($rawLogo, 'http') === 0 || strpos($rawLogo, '/') === 0) {
                // absolute paths starting with / or http or relative paths
                $team_logo = $rawLogo;
            } else {
                // relative path without leading /, add ../
                $team_logo = '../' . $rawLogo;
            }
        }
    }
    $stmt->close();
}
$user['team_name'] = $team_name;
$user['team_logo'] = $team_logo;
$user['is_manager'] = $is_manager;

function getRegionFullName($region) {
    switch ($region) {
        case 'ap': return 'Asia Pacific';
        case 'na': return 'North America';
        case 'eu': return 'Europe';
        case 'kr': return 'Korea';
        case 'br': return 'Brazil';
        case 'latam': return 'LATAM';
        default: return $region;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile<?= $user['riot_id'] ? ' ' . htmlspecialchars($user['riot_id']) : '' ?></title>
    <link href="../css/profile.css" rel="stylesheet">
    <?php include '../utils/link.php'; ?>
</head>

<body>
    <br>
    <div class="container profile-container">
        <div class="sidebar">
            <?php
            // ตรวจสอบว่ามีรูปโปรไฟล์ไหม และไฟล์มีอยู่จริงหรือไม่
            $showSvg = false;
            if (empty($profile_img) || !file_exists(__DIR__ . '/' . $profile_img)) {
                $showSvg = true;
            }
            ?>
            <?php if ($showSvg): ?>
                    <img src="../img/person.png" alt="Default Avatar" style="width:100px;height:100px;object-fit:cover;">

            <?php else: ?>
                <img src="<?= htmlspecialchars($profile_img) ?>" alt="Avatar" id="profileAvatar" style="width:100px;height:100px;object-fit:cover;border-radius:50%;border:2px solid #fff;">
            <?php endif; ?>
            <form method="post" style="margin-top:10px;">
                <button type="submit" name="remove_avatar" class="edit-btn" style="background:#c0392b;">Remove Avatar</button>
            </form>
            <!-- เลือก agent หรืออัปโหลด -->
            <form method="post" enctype="multipart/form-data" id="avatarForm" style="display:none;">

                <div style="margin:10px 0;">
                    <label style="color:white;">or upload a profile image</label>
                    <input type="file" name="profile_img" accept="image/*" style="color:white;" id="profileImgInput">
                </div>
                <!-- เพิ่ม img preview -->
                <div style="text-align:center;">
                    <img id="avatarPreview" src="<?= htmlspecialchars($profile_img) ?>" alt="Preview" style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:2px solid #fff;display:none;">
                </div>
                <button type="submit" name="edit_avatar" class="edit-btn" style="margin-top:10px;">Save Avatar</button>
            </form>
            <button type="button" class="edit-btn" id="changeAvatarBtn" style="margin-top:10px;">Change Avatar</button>
        </div>
        <div class="main-content">
            <div class="profile-settings">
                <h2>Profiles</h2>
                <?php if (!empty($update_error)): ?>
                    <div style="background-color:#c0392b; color:#fff; padding:10px; border-radius:5px; margin-bottom:15px;">
                        <?= htmlspecialchars($update_error) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($update_success)): ?>
                    <div style="background-color:#27ae60; color:#fff; padding:10px; border-radius:5px; margin-bottom:15px;">
                        <?= htmlspecialchars($update_success) ?>
                    </div>
                <?php endif; ?>
                <form method="post" id="profileForm">
                    <div class="form-row">
                        <div class="form-group" style="flex:1">
                            <label>Name</label>
                            <input type="text" name="first_name" placeholder="first name"
                                value="<?= htmlspecialchars($user['first_name']) ?>" readonly required>
                        </div>
                        <div class="form-group" style="flex:1">
                            <label>Lastname</label>
                            <input type="text" name="last_name" placeholder="surname"
                                value="<?= htmlspecialchars($user['last_name']) ?>" readonly required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Riot ID</label>
                        <input type="text" name="riot_id" placeholder="game-profile"
                            value="<?= htmlspecialchars($user['riot_id']) ?>" readonly required>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="flex:1">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="enter email"
                                value="<?= htmlspecialchars($user['email']) ?>" readonly required>
                        </div>
                    </div>
                    <!-- เพิ่มส่วนนี้ -->
                    <div class="form-row" id="passwordRow" style="display:none;">
                        <div class="form-group" style="flex:1">
                            <label>Current Password</label>
                            <input type="password" name="current_password" placeholder="Current Password"
                                style="background-color:#fff; color:#000;">
                        </div>
                        <div class="form-group" style="flex:1">
                            <label>New Password</label>
                            <input type="password" name="password" placeholder="New Password"
                                style="background-color:#fff; color:#000;">
                        </div>
                        <div class="form-group" style="flex:1">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm New Password"
                                style="background-color:#fff; color:#000;">
                        </div>
                    </div>
                    <!-- /เพิ่มส่วนนี้ -->
                    <button type="button" class="edit-btn" id="editBtn">Edit Profile</button>
                    <button type="submit" name="edit_profile" class="edit-btn" id="saveBtn"
                        style="display:none;">Save</button>
                </form>
            </div>
            <div class="game-profile-settings">
                <div class="game-profile-header">
                    <h2>Game Profile</h2>
                </div>
                <div class="form-group">
                    <label>Team</label>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <?php if (!empty($user['team_id']) && !empty($user['team_name'])): ?>
                            <a href="#" style="display:flex;align-items:center;text-decoration:none;pointer-events:none;cursor:default;">
                                <img src="<?= htmlspecialchars($user['team_logo'] ?? '../img/default_team.png') ?>"
                                     alt="Team Logo"
                                     style="width:56px;height:56px;object-fit:contain;border-radius:10px;border:1px solid rgba(255,255,255,0.06);background:#fff;">
                                <div style="margin-left:12px;">
                                    <div style="font-weight:700;color:#ffffff;font-size:16px;line-height:1;">
                                        <?= htmlspecialchars($user['team_name']) ?>
                                    </div>

                                </div>
                            </a>
                        <?php else: ?>
                            <div style="color:rgba(255,255,255,0.6);">Not in a team</div>
                        <?php endif; ?>
                    </div>
                 </div>
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" placeholder="additional details" value="<?= htmlspecialchars($user['role']) ?>"
                        readonly>
                </div>
                <div class="form-group">
                    <label>Region</label>
                    <input type="text" placeholder="additional details"
                        value="<?= htmlspecialchars(getRegionFullName($user['region'])) ?>" readonly>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Show avatar form when click change avatar
    document.getElementById('changeAvatarBtn').onclick = function() {
        document.getElementById('avatarForm').style.display = '';
        this.style.display = 'none';
    };
    // Highlight selected agent
    document.querySelectorAll('#avatarForm input[type="radio"][name="agent_img"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('#avatarForm img').forEach(function(img) {
                img.style.border = '2px solid #fff';
            });
            if (this.checked) {
                this.nextElementSibling.style.border = '2px solid #56287a';
                // แสดง preview agent
                document.getElementById('avatarPreview').src = this.value;
                document.getElementById('avatarPreview').style.display = '';
            }
        });
    });
    document.getElementById('editBtn').onclick = function() {
        let inputs = document.querySelectorAll(
            '#profileForm input[name="first_name"], #profileForm input[name="last_name"], #profileForm input[name="riot_id"], #profileForm input[name="email"]'
        );
        inputs.forEach(input => {
            input.removeAttribute('readonly');
            input.style.backgroundColor = '#fff';
            input.style.color = '#000';
        });
        document.getElementById('editBtn').style.display = 'none';
        document.getElementById('saveBtn').style.display = '';
        document.getElementById('passwordRow').style.display = '';
    };

    // แสดงตัวอย่างรูปโปรไฟล์เมื่อเลือกไฟล์
    document.getElementById('profileImgInput').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('avatarPreview');
                img.src = e.target.result;
                img.style.display = 'inline-block'; // เปลี่ยนจาก 'block' เป็น 'inline-block'
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('avatarPreview').style.display = 'none';
        }
    });
    </script>
</body>

</html>