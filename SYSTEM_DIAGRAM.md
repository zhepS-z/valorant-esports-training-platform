# VALORANT ESPORTS TRACKER — ไดอะแกรมระบบโดยรวม

## 1. โครงสร้างระบบภาพรวม

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              👤 ผู้ใช้ (User / Admin)                            │
└─────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           🏠 จุดเข้าใช้งานหลัก (Entry Points)                     │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  ┌─────────────────────┐  │
│  │   home/     │  │   auth/     │  │  admin_dashboard │  │  .htaccess routes   │  │
│  │  index.php  │  │ login,      │  │  admin_auth.php  │  │  /profile, /career, │  │
│  │  หน้าแรก    │  │ signup,     │  │  หน้าจัดการระบบ  │  │  /leaderboard       │  │
│  │  ค้นหา Riot │  │ forgot pwd  │  │                  │  │                     │  │
│  └─────────────┘  └─────────────┘  └─────────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         ⚙️ โมดูลหลัก (Core Modules)                              │
├──────────────┬──────────────┬──────────────┬──────────────┬──────────────────────┤
│   profile/   │   career/    │  leaderboard/│    team/     │      scrim/          │
│   โปรไฟล์    │   อาชีพ      │   อันดับ     │    ทีม       │    การแข่งขัน        │
│   ผู้เล่น    │   LFP / LFT  │   ผู้เล่น    │   สร้างทีม   │    scrim, api        │
│              │              │   Premier    │   LFP/LFT    │                      │
├──────────────┼──────────────┼──────────────┼──────────────┼──────────────────────┤
│  strategy/   │ team_analytics│   chat/     │ notifications│   download/          │
│   Lineups    │   วิเคราะห์   │  แชททีม     │  แจ้งเตือน   │   Assets             │
│   แผนเกม     │   ทีม-ผู้เล่น │  (Node.js)  │  (Socket.IO) │   Rank, Map, Agent   │
└──────────────┴──────────────┴──────────────┴──────────────┴──────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                       🛠️ ชั้นสนับสนุน (Support & Assets)                         │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐ ┌────────────────────┐  │
│  │  utils/  │ │  cache/  │ │ PHPMailer│ │ css/, img/   │ │  sounds/, uploads/  │  │
│  │  db.php  │ │  API     │ │  อีเมล   │ │  สไตล์/รูป   │ │  เสียง/ไฟล์อัพโหลด  │  │
│  │  navbar  │ │  cache   │ │          │ │              │ │                    │  │
│  │  apikey  │ │          │ │          │ │              │ │                    │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────┘ └────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────────┘
                                        │
                                        ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         🗄️ ฐานข้อมูล MySQL                                      │
│                        valorant_esports                                          │
│  users | teams | lfp_posts | lft_posts | lineups | messages | ban_history | ...  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. โฟลว์การทำงานหลัก (User Flow)

```
                    ┌──────────────┐
                    │   เริ่มต้น   │
                    └──────┬───────┘
                           │
              ┌────────────┼────────────┐
              ▼            ▼            ▼
        ┌──────────┐ ┌──────────┐ ┌──────────────┐
        │ Guest    │ │ สมัคร    │ │ Admin เข้า   │
        │ ค้นหา    │ │ Login    │ │ admin_dashboard│
        │ Riot ID  │ │          │ │               │
        └────┬─────┘ └────┬─────┘ └───────┬──────┘
             │            │               │
             ▼            ▼               ▼
        leaderboard   auth/           จัดการผู้ใช้
        player        profile         จัดการทีม
        (ไม่ต้องล็อกอิน)  ไปที่ home     แบนผู้ใช้
```

---

## 3. โมดูลและความสัมพันธ์

| โมดูล | หน้าที่ | ใช้ร่วมกับ |
|-------|---------|-----------|
| **auth** | เข้าสู่ระบบ, สมัครสมาชิก, ลืมรหัส | profile, team, admin |
| **profile** | ดู/แก้ไขโปรไฟล์ผู้ใช้ | auth, team |
| **career** | LFP/LFT หาทีม-หาผู้เล่น | team |
| **leaderboard** | อันดับผู้เล่น, Premier (Riot API) | cache, download (ranks) |
| **team** | สร้างทีม, โพสต์ LFP/LFT, เชิญสมาชิก | career, profile |
| **scrim** | จัดการ Scrim, API | team |
| **strategy** | Lineups แผนเกม, Agent, Map | profile |
| **team_analytics** | วิเคราะห์ทีม/ผู้เล่น | leaderboard, team |
| **chat** | แชททีม (Node.js + Socket.IO) | team |
| **notifications** | แจ้งเตือนแบบ Real-time (Socket.IO) | ทุกโมดูล |
| **admin_dashboard** | จัดการผู้ใช้, ทีม, แบน | auth |

---

## 4. สถาปัตยกรรมแบบ Real-time

```
┌─────────────┐                    ┌─────────────────────┐
│   Browser   │ ◄──WebSocket────► │  notifications/     │
│   (Client)  │                    │  server.js          │
└─────────────┘                    │  (Node.js+Socket.IO)│
       │                           └──────────┬──────────┘
       │                                      │
       │                           ┌──────────▼──────────┐
       │                           │  MySQL              │
       │                           │  user_notifications │
       │                           └─────────────────────┘
       │
       │  ◄──WebSocket────►  chat/  (แชททีม - ถ้ามี server แยก)
       │
       ▼
┌─────────────────────────────────────────────────────────┐
│  PHP (Apache/XAMPP) - หน้าเว็บหลัก, API, Auth, DB       │
└─────────────────────────────────────────────────────────┘
```

---

## 5. โครงสร้างโฟลเดอร์สรุป

```
VALPROJECT/
├── home/              → หน้าแรก, ค้นหา Riot ID
├── auth/              → Login, Signup, Forgot/Reset Password
├── admin_dashboard/   → จัดการผู้ใช้, ทีม, แบน
├── profile/           → โปรไฟล์ผู้เล่น
├── career/            → LFP/LFT (อาชีพ)
├── leaderboard/       → อันดับ, Premier, รายละเอียดแมตช์
├── team/              → ทีม, สร้างทีม, LFP/LFT, เชิญสมาชิก
├── scrim/             → การแข่งขัน Scrim
├── strategy/          → Lineups แผนเกม
├── team_analytics/    → วิเคราะห์ทีม/ผู้เล่น
├── chat/              → แชท (Node.js)
├── notifications/     → แจ้งเตือน Real-time (Node.js)
├── utils/             → db, navbar, apikey, template
├── download/          → ไฟล์ Rank, Map, Agent
├── cache/             → Cache ผล Riot API
├── css/, img/, sounds/→ สไตล์, รูป, เสียง
├── uploads/           → ไฟล์ที่ผู้ใช้อัปโหลด
└── PHPMailer-6.10.0/  → ส่งอีเมล
```

---

## 6. Mermaid Diagram (ใช้แสดงใน Markdown Viewer / GitHub)

```mermaid
flowchart TB
    subgraph User["👤 ผู้ใช้"]
        Guest[Guest]
        Member[สมาชิก]
        Admin[Admin]
    end

    subgraph Entry["จุดเข้าใช้งาน"]
        Home[home - หน้าแรก]
        Auth[auth - ล็อกอิน/สมัคร]
        AdminDash[admin_dashboard]
    end

    subgraph Core["โมดูลหลัก"]
        Profile[profile]
        Career[career - LFP/LFT]
        Leaderboard[leaderboard]
        Team[team]
        Scrim[scrim]
        Strategy[strategy - Lineups]
        Analytics[team_analytics]
    end

    subgraph Realtime["Real-time"]
        Chat[chat - Node.js]
        Notif[notifications - Socket.IO]
    end

    subgraph Support["สนับสนุน"]
        Utils[utils - db, navbar]
        Cache[cache]
        Download[download]
        Mail[PHPMailer]
    end

    subgraph DB[(MySQL valorant_esports)]

    end

    Guest --> Home
    Member --> Auth
    Admin --> AdminDash
    Auth --> Profile
    Auth --> Team
    Home --> Leaderboard
    Profile --> Team
    Profile --> Strategy
    Team --> Career
    Team --> Scrim
    Team --> Chat
    Team --> Analytics
    Leaderboard --> Analytics
    Core --> Utils
    Utils --> DB
    Notif --> DB
    Chat --> DB
```

---

*สร้างเมื่อ: 2025-02-05 | VALORANT ESPORTS TRACKER*
