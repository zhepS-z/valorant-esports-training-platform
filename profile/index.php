<?php 
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
require_once '../auth/auth_check.php';
include '../utils/db.php'; // ใช้ connection จาก db.php


?>
<!-- index.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valorant Tracker</title>
    <style>
      
        

    </style>
    <?php include '../utils/link.php'; ?>
</head>

<body>
    <br>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8 col-xl-6">
                <div class="tracker-card">
                    <h2 class="mb-4 text-center">Valorant Tracker</h2>
                    <form action="../leaderboard/leaderboardplayer.php" method="get">
                        <div class="mb-3">
                            <label class="form-label">Riot ID (Username#Tag):</label>
                            <input type="text" name="riot_id" class="form-control " placeholder="Riot ID (e.g. TenZ#NA1)" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Region:</label>
                            <select name="region" class="form-select" required>
                                <option value="na">North America (NA)</option>
                                <option value="eu">Europe (EU)</option>
                                <option value="ap">Asia Pacific (AP)</option>
                                <option value="kr">Korea (KR)</option>
                                <option value="latam">Latin America (LATAM)</option>
                                <option value="br">Brazil (BR)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">ค้นหา</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>