# VALORANT ESPORTS TRAINING AND STRATEGY PLATFORM

A comprehensive web-based platform designed for VALORANT esports teams and players to manage player profiles, find teammates, organize scrims, analyze team performance, and coordinate strategies.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![License](https://img.shields.io/badge/license-ISC-green.svg)

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Integration](#api-integration)
- [System Architecture](#system-architecture)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

### Core Functionality

- **Player Profiles** - Create and manage player profiles with stats and achievements
- **Team Management** - Create teams, manage members, and handle invitations
- **Career Matching** - LFP (Looking for Players) and LFT (Looking for Team) system
- **Leaderboard** - Track player rankings and VALORANT Premier statistics
- **Scrim Management** - Organize and manage scrimmage matches
- **Strategy Planning** - Create and share game lineups and strategies per map
- **Team Analytics** - Analyze team and player performance metrics
- **Real-time Communication** - Team chat with WebSocket support
- **Notifications** - Real-time notifications system (Socket.IO)
- **Admin Dashboard** - User and team management, ban system
- **Email Support** - PHPMailer integration for notifications and password resets

### Authentication & Security

- User registration and login system
- Password reset and recovery
- Admin authentication and role management
- User ban system

## 🛠️ Tech Stack

### Backend
- **PHP** (7.4+) - Server-side logic and API
- **Node.js** - Real-time features (chat, notifications)
- **Express.js** (v5.1.0) - Node.js framework
- **Apache** - Web server with .htaccess routing

### Database
- **MySQL** - Primary database (valorant_esports)
- **In-memory Cache** - API response caching

### Frontend
- **HTML/CSS/JavaScript** - Client-side interface
- **Socket.IO** - Real-time communication

### Libraries & Tools
- **mysql2** (v3.14.5) - MySQL client
- **cors** (v2.8.5) - CORS middleware
- **dotenv** (v17.2.2) - Environment variables
- **PHPMailer** (v6.10.0) - Email functionality

## 📁 Project Structure

```
VALPROJECT/
├── admin_dashboard/      → Admin panel for user/team management
├── auth/                 → Authentication (login, signup, password reset)
├── career/               → LFP/LFT job board
├── chat/                 → Real-time team chat
├── css/                  → Stylesheets
├── database/             → Database schemas and utilities
├── download/             → Asset downloads (ranks, maps, agents)
├── home/                 → Landing page and Riot ID search
├── img/                  → Images and media assets
├── leaderboard/          → Player rankings and Premier stats
├── notifications/        → Real-time notifications (Node.js)
├── profile/              → User profile management
├── public/               → Public assets
├── scrim/                → Scrim match management
├── sounds/               → Audio files
├── strategy/             → Game lineups and strategy planning
├── team/                 → Team creation and management
├── team_analytics/       → Team performance analytics
├── tests/                → Test files
├── uploads/              → User-uploaded files
├── utils/                → Utilities (db.php, navbar, apikey)
├── cache/                → API caching system
├── PHPMailer-6.10.0/     → Email library
├── index.php             → Main entry point
├── .env                  → Environment variables (DO NOT commit)
├── .htaccess             → URL routing configuration
├── package.json          → Node.js dependencies
├── valorant_esports.sql  → Database schema
└── README.md             → This file
```

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- Node.js 14+ and npm
- MySQL 5.7 or higher
- Apache web server with mod_rewrite enabled
- Composer (optional, for PHP dependencies)

### Step 1: Clone the Repository

```bash
git clone https://github.com/zhepS-z/valorant-esports-training-platform.git
cd valorant-esports-training-platform
```

### Step 2: Install Node.js Dependencies

```bash
npm install
```

### Step 3: Set Up Environment Variables

Create a `.env` file in the project root:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=valorant_esports
DB_PORT=3306

# Server Configuration
PORT=3000
NODE_ENV=development

# Riot API
RIOT_API_KEY=your_riot_api_key_here

# Email Configuration
EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USER=your_email@gmail.com
EMAIL_PASSWORD=your_app_password

# Application
APP_URL=http://localhost
APP_NAME=VALORANT Esports Platform
```

### Step 4: Set Up Database

1. Create a MySQL database named `valorant_esports`
2. Import the schema:

```bash
mysql -u root -p valorant_esports < valorant_esports.sql
```

3. Verify tables are created:
   - users
   - teams
   - lfp_posts (Looking for Players)
   - lft_posts (Looking for Teams)
   - lineups
   - messages
   - ban_history
   - user_notifications

### Step 5: Configure Apache

Ensure `.htaccess` is enabled and the following routes are configured:
- `/profile` → profile/
- `/career` → career/
- `/leaderboard` → leaderboard/
- `/team` → team/
- `/scrim` → scrim/
- `/strategy` → strategy/
- `/admin` → admin_dashboard/

### Step 6: Start the Application

#### PHP Application (Apache)
```bash
# On XAMPP/Windows
# Place project in htdocs/ and start Apache

# Or use PHP built-in server
php -S localhost:8000
```

#### Node.js Services

```bash
# Start real-time services (notifications & chat)
npm run dev

# Or for production
npm run start
```

Visit `http://localhost:8000` in your browser.

## ⚙️ Configuration

### Database Configuration

Update database credentials in `utils/db.php`:

```php
<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'valorant_esports';
```

### Riot API Integration

1. Get your API key from [Riot Developer Portal](https://developer.riotgames.com/)
2. Add to `.env` file
3. Configure caching in `cache/api_cache.php`

### Email Configuration

Configure PHPMailer settings in authentication modules for password resets and notifications.

## 📖 Usage

### For Players

1. **Create Profile**: Sign up and complete your player profile
2. **Find Teams**: Browse LFP posts or create an LFT post
3. **View Rankings**: Check player leaderboards
4. **Join Team**: Apply to teams or accept team invitations
5. **View Strategies**: Browse team lineups and strategies

### For Team Leaders

1. **Create Team**: Register and create a team in the team management section
2. **Recruit Members**: Post LFP positions and manage applications
3. **Plan Strategies**: Create lineups and share strategies per map
4. **Organize Scrims**: Schedule and manage scrim matches
5. **Analytics**: View team and player analytics

### For Admins

1. **Access Dashboard**: Navigate to `/admin` with admin credentials
2. **Manage Users**: View and manage user accounts
3. **Manage Teams**: Monitor team activities
4. **Ban System**: Ban problematic users and teams
5. **System Monitoring**: Track system health and user activities

## 🔗 API Integration

### Riot API

The platform integrates with Riot's official API for:
- Player statistics
- VALORANT Premier rankings
- Match histories
- Rank updates

### Endpoints

Key API endpoints (Node.js/Express):

```
GET    /api/players/:id          → Get player stats
GET    /api/teams/:id            → Get team info
POST   /api/scrims               → Create scrim
GET    /api/leaderboard          → Get rankings
POST   /api/chat/message         → Send message
```

## 🏗️ System Architecture

### Real-time Architecture

```
┌─────────────────┐
│   Browser       │
│   (Client)      │ ◄──WebSocket────► Node.js + Socket.IO
└─────────────────┘                   (notifications/ & chat/)
        │                                    │
        └────────────────────────────────────┼──────┐
                                             │      │
                        ┌────────────────────┘      │
                        │                           │
                        ▼                           ▼
                   ┌─────────────┐          ┌─────────────┐
                   │    PHP      │          │    MySQL    │
                   │  (Apache)   │◄────────►│ valorant_   │
                   │             │          │ esports     │
                   └─────────────┘          └─────────────┘
```

### Module Relationships

| Module | Purpose | Integrations |
|--------|---------|--------------|
| **auth** | Authentication | profile, team, admin |
| **profile** | User profiles | auth, team |
| **career** | Job board | team |
| **leaderboard** | Rankings | cache, Riot API |
| **team** | Team management | career, profile, scrim |
| **scrim** | Scrim matches | team |
| **strategy** | Game planning | profile |
| **team_analytics** | Performance metrics | leaderboard, team |
| **chat** | Real-time messaging | team (Node.js) |
| **notifications** | Real-time alerts | All modules (Socket.IO) |
| **admin_dashboard** | System admin | auth, all modules |

## 🔒 Security Considerations

- Keep `.env` file private (add to `.gitignore`)
- Use prepared statements for all database queries
- Validate and sanitize all user inputs
- Implement rate limiting for API endpoints
- Enable HTTPS in production
- Regular security audits recommended

## 🐛 Troubleshooting

### Database Connection Issues

```php
// Check database.php for connection errors
// Verify MySQL is running
// Confirm credentials in .env file
```

### Real-time Features Not Working

```bash
# Ensure Node.js processes are running
npm run dev

# Check Socket.IO connection in browser console
# Verify port 3000 is not blocked
```

### File Upload Issues

- Check write permissions on `uploads/` folder
- Verify max upload size in PHP configuration
- Check file type restrictions

## 📝 Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| DB_HOST | MySQL host | Yes |
| DB_USER | MySQL username | Yes |
| DB_PASSWORD | MySQL password | Yes |
| DB_NAME | Database name | Yes |
| RIOT_API_KEY | Riot API key | Yes |
| EMAIL_USER | Email account for sending | Yes |
| EMAIL_PASSWORD | Email password/app token | Yes |
| PORT | Node.js server port | No (default: 3000) |
| APP_URL | Application URL | No |

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the ISC License - see the LICENSE file for details.

## 👨‍💻 Author

- GitHub: [@zhepS-z](https://github.com/zhepS-z)

## 📞 Support

For issues, questions, or suggestions, please open an issue on the GitHub repository.

---

**Note**: This is a VALORANT esports platform fan project. VALORANT is a trademark of Riot Games, Inc.
