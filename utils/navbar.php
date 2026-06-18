<?php
// Get the current file name
$current_page = basename($_SERVER['PHP_SELF']);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/VALPROJECT/utils/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$notifications = [];

if ($user_id && isset($conn)) {
    // 1) Pending join requests
    $sql = "SELECT r.request_id, r.status, r.created_at, t.team_id, t.team_name, u.user_id, u.first_name, u.last_name, u.profile_img
            FROM team_join_requests r
            JOIN teams t ON r.team_id = t.team_id
            JOIN users u ON r.user_id = u.user_id
            WHERE t.manager_id = ? AND r.status = 'pending'
            ORDER BY r.created_at DESC
            LIMIT 6";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $notifications[] = [
                'type' => 'join_request',
                'id' => $row['request_id'],
                'team_id' => $row['team_id'],
                'team_name' => $row['team_name'],
                'from_user' => $row['user_id'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'profile' => $row['profile_img'] ?: '/valproject/img/avatar_default.png',
                'created_at' => $row['created_at']
            ];
        }
        $stmt->close();
    }

    // 2) Scrim reservations
    $sql2 = "SELECT n.id AS notif_id, n.reservation_id, n.created_at AS notif_created,
                    r.user_id AS reserver_id, u.first_name, u.last_name, u.profile_img,
                    s.scrim_id, s.scrim_start, t.team_id, t.team_name,
                    rt.team_id AS reserver_team_id, rt.team_name AS reserver_team_name, rt.team_logo AS reserver_team_logo
             FROM scrim_reservation_notifications n
             JOIN scrim_reservations r ON r.reservation_id = n.reservation_id
             JOIN users u ON u.user_id = r.user_id
             JOIN scrims s ON s.scrim_id = n.scrim_id
             JOIN teams t ON t.team_id = n.team_id
             LEFT JOIN teams rt ON rt.team_id = u.team_id
             WHERE n.manager_id = ? AND n.status = 'pending'
             ORDER BY n.created_at DESC
             LIMIT 6";
    if ($stmt2 = $conn->prepare($sql2)) {
        $stmt2->bind_param('i', $user_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($row = $res2->fetch_assoc()) {
            $reserverName = $row['reserver_team_name'] ?: ($row['first_name'].' '.$row['last_name']);
            if (!empty($row['reserver_team_logo'])) {
                $logoFile = basename($row['reserver_team_logo']);
                $reserverProfile = '/VALPROJECT/uploads/team_logos/' . $logoFile;
            } else {
                $reserverProfile = !empty($row['profile_img']) ? $row['profile_img'] : '/VALPROJECT/img/avatar_default.png';
            }

            $notifications[] = [
                'type' => 'scrim_reservation',
                'id' => $row['notif_id'],
                'reservation_id' => $row['reservation_id'],
                'team_id' => $row['team_id'],
                'team_name' => $row['team_name'],
                'from_user' => $row['reserver_id'],
                'reserver_name' => $reserverName,
                'reserver_profile' => $reserverProfile,
                'scrim_id' => $row['scrim_id'],
                'scrim_start' => $row['scrim_start'],
                'created_at' => $row['notif_created']
            ];
        }
        $stmt2->close();
    }

    // 3) LFP Applications
    $sql3 = "SELECT a.app_id, a.post_id, a.user_id, a.status, a.created_at,
                   p.position, p.rank, p.user_id AS post_owner_id,
                   u.first_name, u.last_name, u.profile_img
            FROM lfp_applications a
            JOIN lfp_posts p ON a.post_id = p.id
            JOIN users u ON a.user_id = u.user_id
            WHERE p.user_id = ? AND a.status = 'pending'
            ORDER BY a.created_at DESC
            LIMIT 6";
    if ($stmt3 = $conn->prepare($sql3)) {
        $stmt3->bind_param('i', $user_id);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        while ($row = $res3->fetch_assoc()) {
            $notifications[] = [
                'type' => 'lfp_application',
                'id' => $row['app_id'],
                'post_id' => $row['post_id'],
                'from_user' => $row['user_id'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'profile' => $row['profile_img'] ?: '/valproject/img/avatar_default.png',
                'position' => $row['position'],
                'rank' => $row['rank'],
                'created_at' => $row['created_at']
            ];
        }
        $stmt3->close();
    }

    // 4) User notifications
    $sql4 = "SELECT id, type, title, body, meta, is_read, created_at FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6";
    if ($stmt4 = $conn->prepare($sql4)) {
        $stmt4->bind_param('i', $user_id);
        $stmt4->execute();
        $res4 = $stmt4->get_result();
        while ($row = $res4->fetch_assoc()) {
            $meta = json_decode($row['meta'] ?? '{}', true) ?: [];
            $notifications[] = [
                'type' => 'user_notification',
                'id' => $row['id'],
                'title' => $row['title'],
                'body'  => $row['body'],
                'meta'  => $meta,
                'is_read' => (int)$row['is_read'],
                'created_at' => $row['created_at']
            ];
        }
        $stmt4->close();
    }
}

// Count unread notifications
$unread_count = 0;
if ($user_id && isset($conn)) {
    $sql_count = "SELECT 
        (SELECT COUNT(*) FROM team_join_requests r 
         JOIN teams t ON r.team_id = t.team_id 
         WHERE t.manager_id = ? AND r.status = 'pending') +
        (SELECT COUNT(*) FROM scrim_reservation_notifications 
         WHERE manager_id = ? AND status = 'pending') +
        (SELECT COUNT(*) FROM lfp_applications a
         JOIN lfp_posts p ON a.post_id = p.id
         WHERE p.user_id = ? AND a.status = 'pending') +
        (SELECT COUNT(*) FROM user_notifications 
         WHERE user_id = ? AND is_read = 0) AS total";
    
    if ($stmt = $conn->prepare($sql_count)) {
        $stmt->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $unread_count = (int)$row['total'];
        }
        $stmt->close();
    }
}
?>

<style>
/* Notification Badge Styles */
.notification-badge-wrapper {
    position: relative;
    display: inline-block;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -8px;
    background: #ff4444;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    animation: pulse-badge 2s ease-in-out infinite;
    z-index: 1;
}

.notification-badge.hidden {
    display: none;
}

/* Animation for badge */
@keyframes pulse-badge {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

@keyframes badge-pop {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

.notification-badge.new {
    animation: badge-pop 0.3s ease-out;
}

@keyframes shake-bell {
    0%, 100% { transform: rotate(0deg); }
    10%, 30%, 50%, 70%, 90% { transform: rotate(-10deg); }
    20%, 40%, 60%, 80% { transform: rotate(10deg); }
}

.notification-icon.shake {
    animation: shake-bell 0.5s ease-in-out;
}
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container container-valproject">
        <a class="navbar-brand" href="/valproject/">
            <img src="/VALPROJECT/img/LOGO/logo.png" alt="Logo" height="30" class="d-inline-block align-top">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mynavbar"
            aria-controls="mynavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mynavbar">
            <form action="/valproject/leaderboard/leaderboardplayer.php" method="get"
                class="d-flex align-items-center me-auto" style="max-width: 400px; width: 100%;">
                <div class="input-group">
                    <input type="text" name="riot_id" class="form-control" placeholder="Riot ID (e.g. TenZ#NA1)"
                        style="width: 60%;" required>
                    <select name="region" class="form-select" style="width: 20%; padding-left: 5;" required>
                        <option value="na">NA</option>
                        <option value="eu">EU</option>
                        <option value="ap">AP</option>
                        <option value="kr">KR</option>
                        <option value="latam">LATAM</option>
                        <option value="br">BR</option>
                    </select>
                    <button type="submit" class="btn btn-custom" style="width: 10%;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </form>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a href="/VALPROJECT/chat/index.php" class="btn nav-link text-light" title="Chat" id="chatBtn">
                        <span class="notification-badge-wrapper">
                            <i class="fa-solid fa-paper-plane notification-icon" id="chatIcon"></i>
                            <span class="notification-badge hidden" id="chatBadge"></span>
                        </span>
                    </a>
                </li>

                <li class="nav-item">
                    <button id="notifBtn" class="btn nav-link text-light" type="button" title="Notifications">
                        <i class="fa-solid fa-bell"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- notification popup -->
<div id="notifPopup" aria-hidden="true" role="dialog" aria-label="Notifications">
    <div class="header">
        <strong>Notifications</strong>
        <button type="button" id="notifClose" class="btn btn-sm btn-close btn-close-white" aria-label="Close"></button>
    </div>
    <div class="body">
        <div class="list-group" id="notifList">
            <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $n): ?>
            <?php if ($n['type'] === 'scrim_reservation'): ?>
            <div class="list-group-item d-flex align-items-start" data-notif-id="<?= $n['id'] ?>">
                <div class="me-2">
                    <img src="<?= htmlspecialchars($n['reserver_profile']) ?>" alt="team logo" class="rounded-circle"
                        width="40" height="40">
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong><?= htmlspecialchars($n['reserver_name']) ?></strong> reserved a slot for your team
                            <strong><?= htmlspecialchars($n['team_name']) ?></strong>
                            <div class="small text-white-50">Scrim: #<?= intval($n['scrim_id']) ?> —
                                <?= htmlspecialchars(date('j M H:i', strtotime($n['scrim_start']))) ?></div>
                        </div>
                        <small
                            class="text-white"><?= htmlspecialchars(date('j M H:i', strtotime($n['created_at']))) ?></small>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-success me-2 notif-respond"
                            data-reservation="<?= intval($n['reservation_id']) ?>"
                            data-response="accept">Accept</button>
                        <button class="btn btn-sm btn-danger notif-respond"
                            data-reservation="<?= intval($n['reservation_id']) ?>"
                            data-response="decline">Decline</button>
                    </div>
                </div>
            </div>
            <?php elseif ($n['type'] === 'lfp_application'): ?>
            <a href="/VALPROJECT/team/api/request_status.php" 
               class="list-group-item list-group-item-action d-flex align-items-start" 
               data-notif-id="<?= $n['id'] ?>">
                <div class="me-2">
                    <img src="<?= htmlspecialchars($n['profile']) ?>" alt="avatar" class="rounded-circle" width="40" height="40">
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong><?= htmlspecialchars($n['name']) ?></strong> invited you to join the team
                        </div>
                        <small class="text-white"><?= htmlspecialchars(date('j M H:i', strtotime($n['created_at']))) ?></small>
                    </div>
                    <div class="text-white small">Click to view details</div>
                </div>
            </a>
            <?php elseif ($n['type'] === 'user_notification'): ?>
            <div class="list-group-item d-flex align-items-start" data-notif-id="<?= $n['id'] ?>">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong><?= htmlspecialchars($n['title']) ?></strong>
                        </div>
                        <small
                            class="text-white"><?= htmlspecialchars(date('j M H:i', strtotime($n['created_at']))) ?></small>
                    </div>
                    <div class="text-white small"><?= htmlspecialchars($n['body']) ?></div>
                </div>
            </div>
            <?php else: ?>
            <a href="<?= $n['type'] === 'join_request' ? '/VALPROJECT/team/api/team_request.php?team_id=' . urlencode($n['team_id']) : '/VALPROJECT/team/pages/invitations.php' ?>"
                class="list-group-item list-group-item-action d-flex align-items-start" data-notif-id="<?= $n['id'] ?>">
                <div class="me-2">
                    <img src="<?= htmlspecialchars($n['profile']) ?>" alt="avatar" class="rounded-circle" width="40"
                        height="40">
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div>
                            <?php if ($n['type'] === 'join_request'): ?>
                            <strong><?= htmlspecialchars($n['name']) ?></strong> ขอเข้าร่วมทีม
                            <strong><?= htmlspecialchars($n['team_name']) ?></strong>
                            <?php else: ?>
                            <strong><?= htmlspecialchars($n['name']) ?></strong> เชิญคุณเข้าร่วมทีม
                            <strong><?= htmlspecialchars($n['team_name']) ?></strong>
                            <?php endif; ?>
                        </div>
                        <small
                            class="text-white"><?= htmlspecialchars(date('j M H:i', strtotime($n['created_at']))) ?></small>
                    </div>
                    <div class="text-white small">
                        <?php if ($n['type'] === 'join_request'): ?>
                        คลิกเพื่อดูและจัดการคำขอ
                        <?php else: ?>
                        คลิกเพื่อตอบรับ/ปฏิเสธคำเชิญ
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="list-group-item text-center text-white-50">
                ไม่มีการแจ้งเตือนใหม่
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
#notifPopup {
    position: fixed;
    display: none;
    background: #011A26;
    border: 1px solid #0B2A36;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    min-width: 380px;
    max-width: 450px;
    max-height: 500px;
    z-index: 1000;
    overflow: hidden;
    flex-direction: column;
}

#notifPopup.show {
    display: flex;
}

#notifPopup .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #0B2A36;
    color: #fff;
}

#notifPopup .body {
    overflow-y: auto;
    flex: 1;
}

#notifPopup .list-group-item {
    background: transparent;
    border: none;
    border-bottom: 1px solid #0B2A36;
    color: #fff;
    padding: 1rem;
}

#notifPopup .list-group-item:last-child {
    border-bottom: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapping = {
        notifBtn: 'notifPopup'
    };

    function getPopupByBtn(btnEl) {
        if (!btnEl || !btnEl.id) return null;
        var id = mapping[btnEl.id];
        return id ? document.getElementById(id) : null;
    }

    function positionPopup(popup, btnEl) {
        if (!popup || !btnEl) return;
        popup.style.display = 'flex';
        popup.style.visibility = 'hidden';

        var popupW = popup.offsetWidth || 380;
        var rect = btnEl.getBoundingClientRect();

        var left = rect.right - popupW;
        var margin = 8;
        if (left < margin) left = margin;
        if (left + popupW > window.innerWidth - margin) left = window.innerWidth - popupW - margin;
        var top = rect.bottom + 8;

        popup.style.left = Math.round(left) + 'px';
        popup.style.top = Math.round(top) + 'px';

        popup.style.visibility = '';
        if (!popup.classList.contains('show')) popup.style.display = 'none';
    }

    function openPopup(popup, btnEl) {
        if (!popup) return;
        popup.classList.add('show');
        popup.setAttribute('aria-hidden', 'false');
        positionPopup(popup, btnEl);
    }

    function closePopup(popup) {
        if (!popup) return;
        popup.classList.remove('show');
        popup.setAttribute('aria-hidden', 'true');
    }

    function closeAllPopups() {
        Object.keys(mapping).forEach(function(btnId) {
            var p = document.getElementById(mapping[btnId]);
            if (p) closePopup(p);
        });
    }

    // Button click handler
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('#notifBtn');
        if (btn) {
            e.stopPropagation();
            var popup = getPopupByBtn(btn);
            if (!popup) return;
            if (popup.classList.contains('show')) {
                closePopup(popup);
            } else {
                closeAllPopups();
                openPopup(popup, btn);
                
                // ✅ เมื่อเปิด popup ให้บันทึกเวลาและซ่อน badge
                setTimeout(() => {
                    const badge = document.getElementById('notifBadge');
                    if (badge && !badge.classList.contains('hidden')) {
                        // บันทึกเวลาที่เปิดดู
                        localStorage.setItem('notif_last_view_time', new Date().toISOString());
                        updateNotificationBadge(0);
                    }
                }, 500);
            }
            return;
        }

        var clickInsidePopup = false;
        Object.keys(mapping).forEach(function(btnId) {
            var p = document.getElementById(mapping[btnId]);
            if (p && p.contains(e.target)) clickInsidePopup = true;
        });
        if (!clickInsidePopup) closeAllPopups();
    });

    // Close button
    var notifClose = document.getElementById('notifClose');
    if (notifClose) notifClose.addEventListener('click', function(e) {
        e.stopPropagation();
        closePopup(document.getElementById('notifPopup'));
    });

    // Esc key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeAllPopups();
    });

    // Reposition on resize/scroll
    window.addEventListener('resize', function() {
        var p = document.getElementById('notifPopup');
        if (p && p.classList.contains('show')) {
            positionPopup(p, document.getElementById('notifBtn'));
        }
    });

    // สร้าง badge element
    const notifBtn = document.getElementById('notifBtn');
    if (notifBtn) {
        const icon = notifBtn.querySelector('i');
        if (icon) {
            const wrapper = document.createElement('span');
            wrapper.className = 'notification-badge-wrapper';
            icon.parentNode.insertBefore(wrapper, icon);
            wrapper.appendChild(icon);
            
            const badge = document.createElement('span');
            badge.className = 'notification-badge';
            badge.id = 'notifBadge';
            
            // ✅ ตรวจสอบว่า user เปิดดูไปแล้วหรือยัง
            const lastViewTime = localStorage.getItem('notif_last_view_time');
            const serverUnreadCount = <?= $unread_count ?>;
            
            if (lastViewTime) {
                // ถ้าเคยเปิดดูแล้ว ให้ซ่อน badge
                badge.textContent = '0';
                badge.classList.add('hidden');
            } else {
                // ถ้ายังไม่เคยเปิดดู ใช้จำนวนจาก server
                badge.textContent = serverUnreadCount;
                if (serverUnreadCount === 0) {
                    badge.classList.add('hidden');
                }
            }
            
            wrapper.appendChild(badge);
            icon.classList.add('notification-icon');
        }
    }
});
</script>

<!-- NOTIFICATION SOCKET (Port 3000) -->
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<script>
const notificationSocket = io('http://<?= $_SERVER['HTTP_HOST'] ?>:3000');
const userId = <?= json_encode($user_id) ?>;

// ฟังก์ชันอัพเดท badge count
function updateNotificationBadge(count) {
    const badge = document.getElementById('notifBadge');
    const icon = document.querySelector('#notifBtn .notification-icon');
    
    if (!badge) return;
    
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.classList.remove('hidden');
        badge.classList.add('new');
        
        if (icon) {
            icon.classList.add('shake');
            setTimeout(() => icon.classList.remove('shake'), 500);
        }
        
        setTimeout(() => badge.classList.remove('new'), 300);
    } else {
        badge.classList.add('hidden');
    }
}

notificationSocket.on('connect', () => {
    console.log('✅ Connected to Notification Server');
    notificationSocket.emit('identify_user', { userId: userId });
});

// ✅ รับ notification real-time
notificationSocket.on('notification_received', (notif) => {
    console.log('🔔 New notification:', notif);

    const notifList = document.getElementById('notifList');
    if (!notifList) return;

    const emptyMsg = notifList.querySelector('.text-center.text-white-50');
    if (emptyMsg) emptyMsg.remove();

    let notifEl = document.createElement('div');

    // ✅ Join Request
    if (notif.type === 'join_request' && notif.meta && notif.meta.team_id) {
        notifEl.innerHTML = `
            <a href="/VALPROJECT/team/api/team_request.php?team_id=${notif.meta.team_id}" 
               class="list-group-item list-group-item-action d-flex align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <strong>${notif.title}</strong>
                        <small class="text-white">Just now</small>
                    </div>
                    <div class="text-white small">${notif.body}</div>
                </div>
            </a>
        `;
    }
    // ✅ Team Invitation
    else if (notif.type === 'invite' && notif.meta) {
        notifEl.innerHTML = `
            <a href="/VALPROJECT/team/pages/invitations.php" 
               class="list-group-item list-group-item-action d-flex align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <strong>${notif.title}</strong>
                        <small class="text-white">Just now</small>
                    </div>
                    <div class="text-white small">${notif.body}</div>
                </div>
            </a>
        `;
    }
    // ✅ Scrim Reservation
    else if (notif.type === 'scrim_reservation' && notif.meta) {
        notifEl.className = 'list-group-item d-flex align-items-start';
        notifEl.innerHTML = `
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                    <strong>${notif.title}</strong>
                    <small class="text-white">Just now</small>
                </div>
                <div class="text-white small">${notif.body}</div>
                <div class="mt-2">
                    <button class="btn btn-sm btn-success me-2 notif-respond"
                        data-reservation="${notif.meta.reservation_id || ''}"
                        data-response="accept">Accept</button>
                    <button class="btn btn-sm btn-danger notif-respond"
                        data-reservation="${notif.meta.reservation_id || ''}"
                        data-response="decline">Decline</button>
                </div>
            </div>
        `;
    }
    // ✅ LFP Application
    else if (notif.type === 'lfp_application' && notif.meta) {
        notifEl.innerHTML = `
            <a href="/VALPROJECT/team/api/request_status.php" 
               class="list-group-item list-group-item-action d-flex align-items-start">
                <div class="me-2">
                    <img src="${notif.meta.profile || '/VALPROJECT/img/avatar_default.png'}" 
                         alt="avatar" class="rounded-circle" width="40" height="40">
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <strong>${notif.title}</strong>
                        <small class="text-white">Just now</small>
                    </div>
                    <div class="text-white small">${notif.body}</div>
                </div>
            </a>
        `;
    }
    // ✅ User Notification (ทั่วไป)
    else {
        notifEl.className = 'list-group-item d-flex align-items-start';
        notifEl.innerHTML = `
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                    <strong>${notif.title}</strong>
                    <small class="text-white">Just now</small>
                </div>
                <div class="text-white small">${notif.body}</div>
            </div>
        `;
    }

    // เพิ่มไปด้านบน
    const firstItem = notifList.querySelector('.list-group-item, a.list-group-item');
    if (firstItem) {
        notifList.insertBefore(notifEl, firstItem);
    } else {
        notifList.appendChild(notifEl);
    }

    // 🔔 อัพเดท badge count
    const badge = document.getElementById('notifBadge');
    if (badge) {
        // ลบเวลาที่เคยเปิดดู เพราะมี notification ใหม่
        localStorage.removeItem('notif_last_view_time');
        const currentCount = parseInt(badge.textContent) || 0;
        updateNotificationBadge(currentCount + 1);
    }

    // เล่นเสียง
    const sound = new Audio('/VALPROJECT/sounds/notification.mp3');
    sound.play().catch(e => console.log('Sound error:', e));
});

notificationSocket.on('disconnect', () => {
    console.log('❌ Disconnected from Notification Server');
});
</script>

<script>
// Handle scrim reservation response
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.notif-respond');
    if (!btn) return;
    e.preventDefault();
    var resId = btn.dataset.reservation;
    var response = btn.dataset.response;
    if (!resId || !response) return;
    if (!confirm('Confirm ' + response + ' reservation #' + resId + '?')) return;

    fetch('/VALPROJECT/scrim/api.php?action=respond_reservation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            reservation_id: parseInt(resId, 10),
            response: response
        })
    })
    .then(r => r.json())
    .then(j => {
        if (j.success) {
            var item = btn.closest('.list-group-item');
            if (item) {
                item.remove();
                
                // 🔔 ลด badge count
                const badge = document.getElementById('notifBadge');
                if (badge) {
                    const currentCount = Math.max(0, (parseInt(badge.textContent) || 0) - 1);
                    updateNotificationBadge(currentCount);
                }
            }
        } else {
            alert(j.error || 'Failed');
        }
    })
    .catch(() => alert('Network error'));
});
</script>

<script>
// ✅ Poll เพื่ออัพเดท Notification ทุก 5 วินาที
let lastCheckTime = new Date().toISOString();

function isNotifShown(n) {
    const shownNotifs = JSON.parse(localStorage.getItem('shownNotifIds') || '[]');
    const notifKey = n.type + ':' + n.id;
    return shownNotifs.includes(notifKey);
}

function markNotifShown(n) {
    const shownNotifs = JSON.parse(localStorage.getItem('shownNotifIds') || '[]');
    const notifKey = n.type + ':' + n.id;
    if (!shownNotifs.includes(notifKey)) {
        shownNotifs.push(notifKey);
        if (shownNotifs.length > 100) {
            shownNotifs.shift();
        }
        localStorage.setItem('shownNotifIds', JSON.stringify(shownNotifs));
    }
}

function checkNewNotifications() {
    fetch('/VALPROJECT/notifications/check_notifications.php?last_check=' + encodeURIComponent(lastCheckTime))
        .then(r => r.json())
        .then(data => {
            if (data.success && data.notifications.length > 0) {
                const newNotifs = data.notifications.filter(n => !isNotifShown(n));
                if (newNotifs.length > 0) {
                    addNewNotifications(newNotifs);
                    lastCheckTime = newNotifs[0].created_at;
                }
            }
        })
        .catch(err => console.error('Polling error:', err));
}

function addNewNotifications(newNotifs) {
    const notifList = document.getElementById('notifList');
    if (!notifList) return;

    const emptyMsg = notifList.querySelector('.text-center.text-white-50');
    if (emptyMsg) emptyMsg.remove();

    newNotifs.forEach(n => {
        markNotifShown(n);
        let notifEl = document.createElement('div');

        // ✅ 1) Join Request
        if (n.type === 'join_request') {
            notifEl.innerHTML = `
                <a href="/VALPROJECT/team/api/team_request.php?team_id=${n.team_id}" 
                   class="list-group-item list-group-item-action d-flex align-items-start">
                    <div class="me-2">
                        <img src="${n.profile}" alt="avatar" class="rounded-circle" width="40" height="40">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${n.name}</strong> ขอเข้าร่วมทีม
                                <strong>${n.team_name}</strong>
                            </div>
                            <small class="text-white">Just now</small>
                        </div>
                        <div class="text-white small">คลิกเพื่อดูและจัดการคำขอ</div>
                    </div>
                </a>
            `;
        }
        // ✅ 2) Team Invitation
        else if (n.type === 'invite') {
            notifEl.innerHTML = `
                <a href="/VALPROJECT/team/pages/invitations.php" 
                   class="list-group-item list-group-item-action d-flex align-items-start">
                    <div class="me-2">
                        <img src="${n.profile}" alt="avatar" class="rounded-circle" width="40" height="40">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${n.name}</strong> เชิญคุณเข้าร่วมทีม
                                <strong>${n.team_name}</strong>
                            </div>
                            <small class="text-white">Just now</small>
                        </div>
                        <div class="text-white small">คลิกเพื่อตอบรับ/ปฏิเสธคำเชิญ</div>
                    </div>
                </a>
            `;
        }
        // ✅ 3) Scrim Reservation
        else if (n.type === 'scrim_reservation') {
            notifEl.className = 'list-group-item d-flex align-items-start';
            notifEl.innerHTML = `
                <div class="me-2">
                    <img src="${n.reserver_profile}" alt="team logo" class="rounded-circle" width="40" height="40">
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>${n.reserver_name}</strong> reserved a slot for your team
                            <strong>${n.team_name}</strong>
                            <div class="small text-white-50">Scrim: #${n.scrim_id}</div>
                        </div>
                        <small class="text-white">Just now</small>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-success me-2 notif-respond"
                            data-reservation="${n.reservation_id}"
                            data-response="accept">Accept</button>
                        <button class="btn btn-sm btn-danger notif-respond"
                            data-reservation="${n.reservation_id}"
                            data-response="decline">Decline</button>
                    </div>
                </div>
            `;
        }
        // ✅ 4) LFP Application
        else if (n.type === 'lfp_application') {
            notifEl.innerHTML = `
                <a href="/VALPROJECT/team/api/request_status.php" 
                   class="list-group-item list-group-item-action d-flex align-items-start">
                    <div class="me-2">
                        <img src="${n.profile}" alt="avatar" class="rounded-circle" width="40" height="40">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${n.name}</strong> สมัครตำแหน่ง
                                <strong>${n.position}</strong>
                            </div>
                            <small class="text-white">Just now</small>
                        </div>
                        <div class="text-white small">คลิกเพื่อดูและจัดการใบสมัคร</div>
                    </div>
                </a>
            `;
        }
        // ✅ 5) User Notification (ทั่วไป)
        else {
            notifEl.className = 'list-group-item d-flex align-items-start';
            notifEl.innerHTML = `
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <strong>${n.title}</strong>
                        <small class="text-white">Just now</small>
                    </div>
                    <div class="text-white small">${n.body}</div>
                </div>
            `;
        }

        // เพิ่มไปด้านบนสุด
        const firstItem = notifList.querySelector('.list-group-item, a.list-group-item');
        if (firstItem) {
            notifList.insertBefore(notifEl, firstItem);
        } else {
            notifList.appendChild(notifEl);
        }
    });

    // 🔔 อัพเดท badge count และเล่นเสียง
    if (newNotifs.length > 0) {
        // ลบเวลาที่เคยเปิดดู เพราะมี notification ใหม่
        localStorage.removeItem('notif_last_view_time');
        
        const badge = document.getElementById('notifBadge');
        if (badge) {
            const currentCount = parseInt(badge.textContent) || 0;
            updateNotificationBadge(currentCount + newNotifs.length);
        }
        
        const sound = new Audio('/VALPROJECT/sounds/notification.mp3');
        sound.play().catch(e => console.log('Sound error:', e));
    }
}

// เริ่ม polling ทุก 5 วินาที
setInterval(checkNewNotifications, 5000);
</script>

<!-- CHAT SOCKET (Port 5000) -->
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<script>
// ✅ เชื่อมต่อกับ Chat Server (Port 5000)
const chatSocket = io('http://<?= $_SERVER['HTTP_HOST'] ?>:5000', {
    transports: ['websocket', 'polling'],
    reconnection: true,
    reconnectionDelay: 1000,
    reconnectionAttempts: 5
});

const chatBadge = document.getElementById('chatBadge');
const chatIcon = document.getElementById('chatIcon');
const chatBtn = document.getElementById('chatBtn');

chatSocket.on('connect', () => {
    console.log('✅ Connected to Chat Server (Port 5000)');
    // ระบุตัวเองกับ chat server
    chatSocket.emit('identify', { userId: userId });
});

chatSocket.on('disconnect', () => {
    console.log('❌ Disconnected from Chat Server');
});

chatSocket.on('connect_error', (error) => {
    console.error('❌ Chat Socket Connection Error:', error);
});

// ✅ รับข้อความแชทใหม่
chatSocket.on('chat message', (msg) => {
    console.log('💬 New chat message received:', msg);
    
    // ตรวจสอบว่าข้อความนี้ส่งถึงเราหรือไม่
    const isForMe = (
        // Private message ที่ส่งถึงเรา (ไม่ใช่ที่เราส่งเอง)
        (msg.type === 'private' && msg.toId === userId && msg.fromId !== userId) ||
        // Team message (ไม่ใช่ที่เราส่งเอง)
        (msg.type === 'team' && msg.fromId !== userId)
    );
    
    // ถ้าไม่ได้อยู่หน้าแชท และข้อความส่งถึงเรา
    if (!window.location.pathname.includes('/chat/index.php') && isForMe) {
        // ดึงจำนวน badge ปัจจุบัน
        let currentCount = parseInt(chatBadge.textContent) || 0;
        let newCount = currentCount + 1;
        
        // อัพเดท badge
        chatBadge.textContent = newCount > 99 ? '99+' : newCount;
        chatBadge.classList.remove('hidden');
        chatBadge.classList.add('new');
        
        // เล่น animation
        chatIcon.classList.add('shake');
        setTimeout(() => {
            chatIcon.classList.remove('shake');
            chatBadge.classList.remove('new');
        }, 500);
        
        // เล่นเสียงแจ้งเตือน
        const sound = new Audio('/VALPROJECT/sounds/notification.mp3');
        sound.play().catch(e => console.log('Sound error:', e));
    }
});

// ✅ เมื่อคลิกเข้าแชท ให้ซ่อน badge และบันทึกเวลา
if (chatBtn) {
    chatBtn.addEventListener('click', (e) => {
        // ถ้าคลิกไปยังหน้าแชท
        setTimeout(() => {
            chatBadge.classList.add('hidden');
            chatBadge.textContent = '0';
            // บันทึกเวลาที่เปิดดูแชท
            localStorage.setItem('chat_last_view_time', new Date().toISOString());
        }, 300);
    });
}

// ✅ เมื่อโหลดหน้าเว็บ ตรวจสอบว่าเคยเปิดดูแชทไปแล้วหรือยัง
document.addEventListener('DOMContentLoaded', function() {
    // ถ้าอยู่หน้าแชท ให้ซ่อน badge
    if (window.location.pathname.includes('/chat/index.php')) {
        chatBadge.classList.add('hidden');
        chatBadge.textContent = '0';
        localStorage.setItem('chat_last_view_time', new Date().toISOString());
    } else {
        // ถ้าไม่ได้อยู่หน้าแชท ตรวจสอบว่าเคยเปิดดูไปแล้วหรือยัง
        const lastViewTime = localStorage.getItem('chat_last_view_time');
        if (lastViewTime) {
            // ซ่อน badge ถ้าเคยเปิดดูไปแล้ว
            chatBadge.classList.add('hidden');
            chatBadge.textContent = '0';
        }
    }
});
</script>