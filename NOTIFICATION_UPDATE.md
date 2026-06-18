# 📬 Notification System Update - Team Acceptance/Rejection

## ✅ Updates Completed

### 1. **Team Join Request - Acceptance Notification**
**File**: `team/api/process_request.php`

When a team manager **accepts** a user's request to join the team:
- **Notification Type**: `team_request_accepted`
- **Title**: "โครงการได้รับการอนุมัติ! ✅"
- **Message**: "คุณได้รับการอนุมัติเข้า [Team Name] แล้ว!"
- **Meta Data**: `team_id`, `team_name`

```php
triggerNotification(
    $join_user_id,
    'team_request_accepted',
    'โครงการได้รับการอนุมัติ! ✅',
    'คุณได้รับการอนุมัติเข้า ' . $team_name . ' แล้ว!',
    ['team_id' => $team_id, 'team_name' => $team_name]
);
```

---

### 2. **Team Join Request - Rejection Notification**
**File**: `team/api/process_request.php`

When a team manager **rejects** a user's request to join the team:
- **Notification Type**: `team_request_declined`
- **Title**: "การขอเข้าทีมถูกปฏิเสธ ❌"
- **Message**: "ขออภัย การขอของคุณเข้า [Team Name] ถูกปฏิเสธแล้ว"
- **Meta Data**: `team_id`, `team_name`

```php
triggerNotification(
    $join_user_id,
    'team_request_declined',
    'การขอเข้าทีมถูกปฏิเสธ ❌',
    'ขออภัย การขอของคุณเข้า ' . $team_name . ' ถูกปฏิเสธแล้ว',
    ['team_id' => $team_id, 'team_name' => $team_name]
);
```

---

### 3. **LFP Application - Acceptance Notification**
**File**: `team/api/invite_applicant.php`

When a team **accepts** a player's LFP application:
- **Notification Type**: `lfp_application_accepted`
- **Title**: "ยินดีด้วย! สมัครสำเร็จแล้ว! ✅"
- **Message**: "คุณได้รับการสมัครเข้า [Team Name] แล้ว!"
- **Meta Data**: `team_id`, `team_name`

```php
triggerNotification(
    $applicantId,
    'lfp_application_accepted',
    'ยินดีด้วย! สมัครสำเร็จแล้ว! ✅',
    'คุณได้รับการสมัครเข้า ' . $row['team_name'] . ' แล้ว!',
    ['team_id' => $teamId, 'team_name' => $row['team_name']]
);
```

---

### 4. **LFP Application - Rejection Notification**
**File**: `team/api/decline_application.php`

When a team **rejects** a player's LFP application:
- **Notification Type**: `lfp_application_declined`
- **Title**: "การสมัครถูกปฏิเสธ ❌"
- **Message**: "ขออภัย การสมัครของคุณถูกปฏิเสธแล้ว"
- **Meta Data**: `app_id`

```php
triggerNotification(
    $applicant_id,
    'lfp_application_declined',
    'การสมัครถูกปฏิเสธ ❌',
    'ขออภัย การสมัครของคุณถูกปฏิเสธแล้ว',
    ['app_id' => $app_id]
);
```

---

## 📋 Files Modified

| File | Changes |
|------|---------|
| `team/api/process_request.php` | Added notifications for team request acceptance and rejection |
| `team/api/invite_applicant.php` | Added notification for LFP application acceptance |
| `team/api/decline_application.php` | Added notification for LFP application rejection |

---

## 🔧 How It Works

1. **Insert into `notification_helper.php`**: The `triggerNotification()` function is called
   - Sends real-time notification via Socket.io (TCP to Node.js server)
   - Saves to `user_notifications` database table

2. **Real-time Display**: Using Socket.io:
   - If user is connected → instant notification popup
   - If user is offline → stored in database, appears when they check notifications

3. **Notification Display**:
   - Notifications appear in the `check_notifications.php` endpoint
   - Frontend polls `/notifications/check_notifications.php` to fetch all pending notifications
   - Includes team join request notifications, LFP application notifications, and user notifications

---

## ✨ Features

- ✅ **Real-time Notifications**: Uses Socket.io for instant updates
- ✅ **Database Fallback**: Saves to database if user is offline
- ✅ **Metadata Support**: Includes team info and application details
- ✅ **Thai Language Support**: Messages are in Thai language
- ✅ **Team Information**: Shows which team accepted/rejected the request
- ✅ **Four Scenarios Covered**:
  1. Team join request accepted
  2. Team join request rejected
  3. LFP application accepted
  4. LFP application rejected

---

## 🧪 Testing

To test the notifications:

1. **Team Join Request**:
   - Create a team
   - Have another user request to join
   - Accept/Reject the request
   - User should see notification in their notification panel

2. **LFP Application**:
   - Create an LFP post (Looking for Players)
   - Have another user apply
   - Accept/Reject the application
   - User should see notification in their notification panel

---

## 📞 Database Relations

The system uses these tables:
- `team_join_requests`: Stores join request metadata
- `lfp_applications`: Stores LFP application metadata
- `user_notifications`: Stores all user notifications with type, title, body, and meta
- `teams`: Stores team information (team_name used in notifications)

---

**Last Updated**: February 11, 2026
