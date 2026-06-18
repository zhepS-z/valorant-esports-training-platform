<?php 
session_start(); // เริ่มต้นเซสชัน
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valorant Tracker</title>
    <style>
        /* Modern CSS Reset */
        * {
        }

        body-index {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('../img/bg/index_bg.png') no-repeat center center fixed !important;
            background-size: cover !important;
            min-height: 100vh !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            padding: 20px !important;
            color: #fff !important;
        }

        .container-index {
            width: 100% !important;
            max-width: 500px !important;
            animation: fadeIn 0.8s ease-out !important;
        }

        .tracker-card {
            background: rgba(22, 22, 26, 0.85) !important;
            backdrop-filter: blur(10px) !important;
            border-radius: 16px !important;
            padding: 2.5rem !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .logo {
            text-align: center !important;
            margin-bottom: 2rem !important;
        }

        .logo h1 {
            font-size: 2.5rem !important;
            font-weight: 700 !important;
            background: linear-gradient(90deg, #ff4655, #ff8e8e) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            margin-bottom: 0.5rem !important;
            letter-spacing: 1px !important;
        }

        .logo p {
            color: rgba(255, 255, 255, 0.7) !important;
            font-size: 0.9rem !important;
            letter-spacing: 1.5px !important;
        }

        .form-index-group {
            margin-bottom: 1.5rem !important;
            color : #fff !important;
        }

        .form-index-label {
            display: block !important;
            margin-bottom: 0.5rem !important;
            font-weight: 500 !important;
            color: rgba(255, 255, 255, 0.9) !important;
            background: rgba(0, 0, 0, 0.3) !important;
        }

        .form-index-control, .form-index-select {
            width: 100% !important;
            padding: 12px 16px !important;
            background: rgba(255, 255, 255, 0.08) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 8px !important;
            color: #4b4b4bff !important;
            font-size: 1rem !important;
            transition: all 0.3s ease !important;
        }

        .form-index-control:focus, .form-index-select:focus {
            outline: none !important;
            border-color: #ff4655 !important;
            box-shadow: 0 0 0 2px rgba(255, 70, 85, 0.2) !important;
            background: rgba(255, 255, 255, 0.12) !important;
        }

        .form-index-control::placeholder {
            color: rgba(255, 255, 255, 0.4) !important;
        }

        .btn-index-search {
            width: 100% !important;
            padding: 14px !important;
            background: linear-gradient(135deg, #ff4655, #ff5e6c) !important;
            border: none !important;
            border-radius: 8px !important;
            color: white !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            margin-top: 0.5rem !important;
            letter-spacing: 0.5px !important;
        }

        .btn-index-search:hover {
            background: linear-gradient(135deg, #e63e4d, #ff4655) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(255, 70, 85, 0.4) !important;
        }

        .btn-index-search:active {
            transform: translateY(0) !important;
        }

        .features {
            display: flex !important;
            justify-content: space-between !important;
            margin-top: 2rem !important;
            padding-top: 1.5rem !important;
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .feature {
            text-align: center !important;
            flex: 1 !important;
            padding: 0 10px !important;
        }

        .feature-icon {
            font-size: 1.5rem !important;
            margin-bottom: 0.5rem !important;
            color: #ff4655 !important;
        }

        .feature-text {
            font-size: 0.8rem !important;
            color: rgba(255, 255, 255, 0.7) !important;
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0 !important;
                transform: translateY(20px) !important;
            }
            to {
                opacity: 1 !important;
                transform: translateY(0) !important;
            }
        }

        /* Responsive */
        @media (max-width: 576px) {
            .tracker-card {
                padding: 2rem 1.5rem !important;
            }
            
            .logo h1 {
                font-size: 2rem !important;
            }
            
            .features {
                flex-direction: column !important;
                gap: 1rem !important;
            }
        }
    </style>
    <?php include '../utils/link.php'; ?>
</head>

<body-index>
    <div class="container-index">
        <div class="tracker-card">
            <div class="logo">
                <h1>VALORANT TRACKER</h1>
                <p>TRACK YOUR STATS & PERFORMANCE</p>
            </div>
            
            <form action="../leaderboard/leaderboardplayer.php" method="get">
                <div class="form-index-group">
                    <label class="form-index-label">Riot ID (Username#Tag)</label>
                    <input type="text" name="riot_id" class="form-index-control" placeholder="e.g. TenZ#NA1" required>
                </div>
                
                <div class="form-index-group">
                    <label class="form-index-label">Region</label>
                    <select name="region" class="form-index-select" required>
                        <option value="na">North America (NA)</option>
                        <option value="eu">Europe (EU)</option>
                        <option value="ap">Asia Pacific (AP)</option>
                        <option value="kr">Korea (KR)</option>
                        <option value="latam">Latin America (LATAM)</option>
                        <option value="br">Brazil (BR)</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-index-search">SEARCH PLAYER</button>
            </form>
            
            <div class="features">
                <div class="feature">
                    <div class="feature-icon">📊</div>
                    <div class="feature-text">Detailed Stats</div>
                </div>
                <div class="feature">
                    <div class="feature-icon">🏆</div>
                    <div class="feature-text">Rank Tracking</div>
                </div>
                <div class="feature">
                    <div class="feature-icon">⚡</div>
                    <div class="feature-text">Perform-indexance Analysis</div>
                </div>
            </div>
        </div>
    </div>
</body-index>

</html>