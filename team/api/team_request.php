<?php 
session_start();
define('ACCESS', true);
require_once '../../utils/apikey.php';
require_once '../../auth/auth_check.php';
include '../../utils/db.php';

// Check if user is a team manager
$manager_id = $_SESSION['user_id'];
$teamQuery = $conn->query("SELECT team_id FROM teams WHERE manager_id = $manager_id");
if ($teamQuery->num_rows === 0) {
    header("Location: ../error/403.php");
    exit();
}
$team_id = $teamQuery->fetch_assoc()['team_id'];

// Get team name for header
$teamNameQuery = $conn->query("SELECT team_name FROM teams WHERE team_id = $team_id");
$team_name = $teamNameQuery->fetch_assoc()['team_name'];

// Get join requests for this team
$requestsQuery = $conn->query("
    SELECT r.request_id, r.status, r.created_at, 
           u.user_id, u.first_name, u.last_name, u.profile_img
    FROM team_join_requests r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.team_id = $team_id
    ORDER BY r.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำขอเข้าร่วมทีม - <?= htmlspecialchars($team_name) ?></title>
    <link href="../css/LFT_LFP.css" rel="stylesheet">
    <?php include '../../utils/link.php'; ?>
    <style>
        .request-card {
            background: linear-gradient(135deg, #01182a 0%, #0f1923 100%);
            border-radius: 8px;
            border-left: 4px solid var(--valorant-red);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .request-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 70, 85, 0.1);
        }
        
        .request-status {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-pending {
            color: #FFC107;
        }
        
        .status-accepted {
            color: #28A745;
        }
        
        .status-declined {
            color: #DC3545;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid var(--valorant-red);
            object-fit: cover;
        }
        
        .action-btn {
            min-width: 100px;
        }
        
        .request-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .role-badge {
            background-color: #2a3b4c;
            color: var(--valorant-light);
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .empty-state {
            background-color: #01182a;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: var(--valorant-red);
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-valorant"><i class="fas fa-user-plus me-2"></i>คำขอเข้าร่วมทีม <span style="color:var(--valorant-red)"><?= htmlspecialchars($team_name) ?></span></h2>
            <a href="/valproject/team/pages/team_manage.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>กลับไปหน้าจัดการทีม
            </a>
        </div>
        
        <?php if ($requestsQuery->num_rows > 0): ?>
            <div class="row">
                <?php while($request = $requestsQuery->fetch_assoc()): ?>
                    <?php
                        // แปลงสถานะเป็นข้อความภาษาไทย
                        $statusLabel = ($request['status'] === 'pending') ? 'รออนุมัติ' : (($request['status'] === 'accepted') ? 'รับเข้าทีมแล้ว' : 'ถูกปฏิเสธ');
                    ?>
                    <div class="col-md-6 mb-4">
                        <div class="request-card p-4">
                            <div class="d-flex align-items-start mb-3">
                                <?php
                                  if (empty($request['profile_img'])) {
                                    $avatar_req = null;  // Use icon instead
                                  } else if (preg_match('#^https?://#i', $request['profile_img'])) {
                                    $avatar_req = $request['profile_img'];
                                  } else {
                                    $p_req = str_replace('\\', '/', $request['profile_img']);
                                    $p_req = str_replace('team/', '', $p_req);
                                    $p_req = ltrim($p_req, '/');
                                    $avatar_req = '/VALPROJECT/img/' . $p_req;
                                  }
                                ?>
                                <?php if ($avatar_req): ?>
                                <img src="<?= htmlspecialchars($avatar_req) ?>" 
                                     class="user-avatar me-3" 
                                     alt="<?= htmlspecialchars($request['first_name']) ?>">
                                <?php else: ?>
                                <div class="user-avatar me-3 bg-secondary d-flex align-items-center justify-content-center" style="width:60px;height:60px;border-radius:50%;">
                                  <i class="fas fa-user-circle" style="font-size:40px;color:white;"></i>
                                </div>
                                <?php endif; ?>
                                     
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></h5>
                                            <div class="d-flex align-items-center mb-2">
                                                <!-- <span class="role-badge"><?= htmlspecialchars($request['primary_role']) ?></span> -->
                                            </div>
                                        </div>
                                        <span class="request-status status-<?= htmlspecialchars($request['status']) ?>">
                                            <?= htmlspecialchars($statusLabel) ?>
                                        </span>
                                    </div>
                                    
                                    <p class="request-date mb-3">
                                        <i class="far fa-clock me-1"></i>
                                        ส่งคำขอเมื่อ <?= date('j M Y H:i', strtotime($request['created_at'])) ?>
                                    </p>
                                    
                                    <?php if ($request['status'] == 'pending'): ?>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-success action-btn accept-request" 
                                                    data-request-id="<?= $request['request_id'] ?>">
                                                <i class="fas fa-check me-1"></i> รับเข้าทีม
                                            </button>
                                            <button class="btn btn-danger action-btn decline-request" 
                                                    data-request-id="<?= $request['request_id'] ?>">
                                                <i class="fas fa-times me-1"></i> ปฏิเสธ
                                            </button>
                                            <button class="btn btn-secondary action-btn open-chat-btn"
                                                    data-user-id="<?= $request['user_id'] ?>">
                                                <i class="fas fa-comments me-1"></i> Chat
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex gap-2">
                                            <?php if ($request['status'] == 'accepted'): ?>
                                                <span class="badge bg-success">เป็นสมาชิกแล้ว</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">คำขอถูกปฏิเสธ</span>
                                            <?php endif; ?>
                                            <button class="btn btn-secondary action-btn btn-sm open-chat-btn"
                                                    data-user-id="<?= $request['user_id'] ?>">
                                                <i class="fas fa-comments me-1"></i> Chat
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3 class="text-valorant mb-3">ยังไม่มีคำขอเข้าร่วมทีม</h3>
                <p class="text-muted">ขณะนี้ยังไม่มีผู้เล่นส่งคำขอเข้าร่วมทีมของคุณ</p>
                <a href="find_teams.php" class="btn btn-custom mt-3">
                    <i class="fas fa-search me-2"></i>ค้นหาผู้เล่นเพื่อเชิญ
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle accept request
            document.querySelectorAll('.accept-request').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestId = this.dataset.requestId;
                    if (confirm('ยืนยันการรับผู้ใช้นี้เข้าทีมหรือไม่?')) {
                        processRequest(requestId, 'accepted');
                    }
                });
            });
            
            // Handle decline request
            document.querySelectorAll('.decline-request').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestId = this.dataset.requestId;
                    if (confirm('ยืนยันการปฏิเสธคำขอนี้หรือไม่?')) {
                        processRequest(requestId, 'declined');
                    }
                });
            });

            // Chat button - open chat page in new tab
            document.querySelectorAll('.open-chat-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    var userId = this.dataset.userId;
                    var message = encodeURIComponent('สวัสดี ต้องการคุยเรื่องการเข้าร่วมทีม');
                    window.open('/VALPROJECT/chat/index.php?chat_with=' + encodeURIComponent(userId) + '&message=' + message, '_blank');
                });
            });
            
            function processRequest(requestId, action) {
                fetch('process_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&action=${action}`
                })
                .then(response => {
                    // ตรวจสอบ response status
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        // ทำการรีโหลดหน้าเพื่ออัปเดตสถานะ
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + (data?.message || 'ไม่สามารถดำเนินการได้'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดขณะประมวลผลคำขอ: ' + error.message);
                });
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>