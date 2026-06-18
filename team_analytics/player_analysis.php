<?php 
session_start(); // เริ่มต้นเซสชัน
define('ACCESS', true);
require_once '../utils/apikey.php';  // โหลด API Key
require_once '../auth/auth_check.php';
include '../utils/db.php';

// -------- Config -------- //
// Get parameters from URL
$user_id = $_GET['userid'] ?? null;
$riot_id = $_GET['riot_id'] ?? '';
$region = $_GET['region'] ?? 'ap';

// Parse riot_id into name and tag
$name = '';
$tag = '';
if (!empty($riot_id)) {
    if (strpos($riot_id, '#') !== false) {
        list($name, $tag) = explode('#', $riot_id);
    } elseif (strpos($riot_id, ' ') !== false) {
        $parts = preg_split('/\s+/', trim($riot_id));
        $name = $parts[0];
        $tag = $parts[count($parts)-1];
    }
}

// Use fallback values if parameters are empty
if (empty($name) || empty($tag)) {
    // Fetch from database using user_id
    if ($user_id) {
        $stmt = $conn->prepare("SELECT riot_id, region FROM users WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $riot_id = $row['riot_id'];
            $region = $row['region'] ?: 'ap';
            
            // Parse riot_id again
            if (strpos($riot_id, '#') !== false) {
                list($name, $tag) = explode('#', $riot_id);
            } elseif (strpos($riot_id, ' ') !== false) {
                $parts = preg_split('/\s+/', trim($riot_id));
                $name = $parts[0];
                $tag = $parts[count($parts)-1];
            }
        }
        $stmt->close();
    }
    
    // Final fallback values
    $name = $name ?: 'smile';
    $tag = $tag ?: '8387'; 
}

// Clean up the values
$name = trim($name);
$tag = trim($tag);
$region = strtolower(trim($region));

// -------- Fetch API -------- //
$api_url = "https://api.henrikdev.xyz/valorant/v3/matches/$region/$name/$tag";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// เพิ่ม API Key ใน header
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: $api_key"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Add safe division function
function safeDivide($numerator, $denominator, $precision = 2) {
    if ($denominator == 0 || $numerator === null || $denominator === null) {
        return 0;
    }
    return round($numerator / $denominator, $precision);
}

// Enhanced debug information
if (isset($_GET['debug'])) {
    echo "<div style='background: #1a1a1a; color: #00ff00; padding: 20px; margin: 20px; border-radius: 5px; font-family: monospace;'>";
    echo "<h3>🔍 Debug Information</h3>";
    
    // API Request Details
    echo "<h4>📡 API Request</h4>";
    echo "URL: " . htmlspecialchars($api_url) . "<br>";
    echo "HTTP Response Code: " . $httpCode . "<br>";
    
    // Raw Response
    echo "<h4>📥 Raw API Response</h4>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
    
    // Decoded Data
    echo "<h4>🔄 Decoded Data</h4>";
    echo "<pre>";
    print_r(json_decode($response, true));
    echo "</pre>";
    
    // Variables State
    echo "<h4>📊 Variables State</h4>";
    echo "Games Count: " . $gameCount . "<br>";
    echo "Total Kills: " . $totalKills . "<br>";
    echo "Total Deaths: " . $totalDeaths . "<br>";
    echo "Total Assists: " . $totalAssists . "<br>";
    echo "Total Score: " . $totalScore . "<br>";
    
    // Error Information
    echo "<h4>⚠️ Errors</h4>";
    if ($httpCode !== 200) {
        echo "API Error: HTTP Code " . $httpCode . "<br>";
    }
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON Error: " . json_last_error_msg() . "<br>";
    }
    
    echo "</div>";
    exit; // Stop further processing when debugging
}

// Debug: Print API response
if (isset($_GET['debug'])) {
    echo "<pre>";
    print_r($response);
    echo "</pre>";
}

// Initialize variables with default values
$matches = [];
$gameCount = 0;
$error_message = ''; // เพิ่มการประกาศค่าเริ่มต้น
$totalKills = $totalDeaths = $totalAssists = $totalScore = 0;
$totalHead = $totalBody = $totalLeg = 0;
$totalSpent = $totalLoadout = $totalAvgSpent = 0;
$totalDamageMade = $totalDamageTaken = 0;
$totalC = $totalQ = $totalE = $totalX = 0;
$totalRounds = 0; // เพิ่มตัวแปรเก็บจำนวนรอบทั้งหมด

// Check if API call was successful and debug errors
if ($httpCode !== 200) {
    $error_message = "API Error: HTTP Code $httpCode";
} else if (!$response) {
    $error_message = "API Error: No response received";
} else {
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = "JSON Error: " . json_last_error_msg();
    } else if (!isset($data['data']) || !is_array($data['data'])) {
        $error_message = "API Error: Invalid data structure";
    } else {
        $matches = $data['data'];
        $gameCount = count($matches);

        if ($gameCount === 0) {
            $error_message = "No matches found for this player";
        } else {
            // Process first match for display
            $latestMatch = $matches[0];
            if (isset($latestMatch['players']['all_players'])) {
                foreach ($latestMatch['players']['all_players'] as $p) {
                    if (strtolower($p['name']) === strtolower($name) && strtolower($p['tag']) === strtolower($tag)) {
                        $player = $p;
                        break;
                    }
                }
            }

            // Calculate totals from all matches
            foreach ($matches as $match) {
                if (isset($match['metadata']['rounds_played'])) {
                    $totalRounds += intval($match['metadata']['rounds_played']);
                }
                
                if (isset($match['players']['all_players'])) {
                    foreach ($match['players']['all_players'] as $p) {
                        if (strtolower($p['name']) === strtolower($name) && strtolower($p['tag']) === strtolower($tag)) {
                            // ตรวจสอบและแปลงค่าให้เป็นตัวเลขก่อนบวก
                            $totalKills += intval($p['stats']['kills'] ?? 0);
                            $totalDeaths += intval($p['stats']['deaths'] ?? 0);
                            $totalAssists += intval($p['stats']['assists'] ?? 0);
                            $totalScore += intval($p['stats']['score'] ?? 0);
                            $totalHead += intval($p['stats']['headshots'] ?? 0);
                            $totalBody += intval($p['stats']['bodyshots'] ?? 0);
                            $totalLeg += intval($p['stats']['legshots'] ?? 0);
                            $totalDamageMade += intval($p['damage_made'] ?? 0);
                            $totalDamageTaken += intval($p['damage_received'] ?? 0);
                            
                            // แก้ไขการเก็บข้อมูลเศรษฐกิจ
                            $totalSpent += intval($p['economy']['spent']['overall'] ?? 0);
                            $totalLoadout += intval($p['economy']['loadout_value']['overall'] ?? 0);
                            
                            // หากต้องการเก็บค่าเฉลี่ยด้วย
                            $avgSpentPerRound = floatval($p['economy']['spent']['average'] ?? 0);
                            $avgLoadoutPerRound = floatval($p['economy']['loadout_value']['average'] ?? 0);
                            
                            // Ability casts
                            $ability_casts = $p['ability_casts'] ?? [];
                            $totalC += intval($ability_casts['c_cast'] ?? 0);
                            $totalQ += intval($ability_casts['q_cast'] ?? 0);
                            $totalE += intval($ability_casts['e_cast'] ?? 0);
                            $totalX += intval($ability_casts['x_cast'] ?? 0);
                            break;
                        }
                    }
                }
            }
        }
    }
}

// Safe calculations with null checks and zero handling
$kd = safeDivide($totalKills, $totalDeaths);
$kda = safeDivide(($totalKills + $totalAssists), $totalDeaths);
$totalShots = $totalHead + $totalBody + $totalLeg;

// Add safe calculations for shot percentages
$hsPercent = $totalShots > 0 ? safeDivide($totalHead * 100, $totalShots, 1) : 0;
$bsPercent = $totalShots > 0 ? safeDivide($totalBody * 100, $totalShots, 1) : 0;
$lsPercent = $totalShots > 0 ? safeDivide($totalLeg * 100, $totalShots, 1) : 0;

// Safe per-game averages
$avgDamagePerGame = $gameCount > 0 ? safeDivide($totalDamageMade, $gameCount) : 0;
$avgSpentPerGame = $gameCount > 0 ? safeDivide($totalSpent, $gameCount) : 0;
$avgLoadoutPerGame = $gameCount > 0 ? safeDivide($totalLoadout, $gameCount) : 0;

// Add a check for spent per round calculation
$spentPerRound = $gameCount > 0 ? number_format(safeDivide($totalSpent, $gameCount)) : 0;

// Add this function at the top of the file, after other function declarations
function getDamageAnalysis($damageDealt, $damageTaken, $avgDamagePerRound) {
    $analysis = [];
    
    // Calculate damage difference
    $damageDiff = $damageDealt - $damageTaken;
    
    // Analysis based on damage difference
    if ($damageDiff >= 2000) {
        $analysis['overall'] = "ดีมาก! สร้างความเสียหายมากกว่าที่ได้รับอย่างชัดเจน แสดงถึงการควบคุมเกมที่ดี";
        $analysis['rating'] = "ดี";
    } elseif ($damageDiff >= 1000) {
        $analysis['overall'] = "ปานกลาง - สร้างความเสียหายมากกว่าที่ได้รับเล็กน้อย ยังมีโอกาสพัฒนา";
        $analysis['rating'] = "ปานกลาง";
    } elseif ($damageDiff >= -1000) {
        $analysis['overall'] = "ปานกลาง - ความเสียหายที่สร้างและได้รับใกล้เคียงกัน ควรปรับปรุงการเล่นเชิงรุก";
        $analysis['rating'] = "ปานกลาง";
    } elseif ($damageDiff >= -2000) {
        $analysis['overall'] = "ต้องปรับปรุง - ได้รับความเสียหายมากกว่าที่สร้าง ควรระวังตำแหน่งการเล่นมากขึ้น";
        $analysis['rating'] = "แย่";
    } else {
        $analysis['overall'] = "ต้องปรับปรุงมาก - ได้รับความเสียหายมากกว่าที่สร้างอย่างชัดเจน ควรฝึกการใช้ cover และ positioning";
        $analysis['rating'] = "แย่มาก";
    }

    // Additional specific advice based on damage metrics
    if ($damageDealt > $damageTaken) {
        $analysis['advice'] = "คำแนะนำ: รักษามาตรฐานการเล่นแบบนี้ไว้ พยายามเพิ่มความแม่นยำในการยิงเพื่อสร้างความเสียหายให้มากขึ้น";
    } else {
        $analysis['advice'] = "คำแนะนำ: ควรปรับปรุงการใช้ cover และ positioning ให้ดีขึ้น พยายามหลบหลีกการโดนยิงและหาจังหวะโจมตีที่ปลอดภัย";
    }

    // ADR (Average Damage per Round) analysis
    if ($avgDamagePerRound > 150) {
        $analysis['adr'] = "ADR สูงมาก แสดงถึงการมีผลกระทบต่อเกมสูง";
    } elseif ($avgDamagePerRound > 100) {
        $analysis['adr'] = "ADR อยู่ในเกณฑ์ดี มีส่วนร่วมกับทีมสม่ำเสมอ";
    } else {
        $analysis['adr'] = "ADR ต่ำ ควรพยายามมีส่วนร่วมในการต่อสู้มากขึ้น";
    }

    return $analysis;
}

// Add this analysis function near the top of the file with other functions
function getShootingAnalysis($hsPercent, $bsPercent, $lsPercent) {
    $analysis = [];
    
    // Analyze headshot percentage
    if ($hsPercent >= 30) {
        $analysis['rating'] = "ยอดเยี่ยม";
        $analysis['description'] = "อัตรา Headshot สูงมาก ({$hsPercent}%) แสดงถึงความแม่นยำในการเล็งระดับสูง";
    } elseif ($hsPercent >= 20) {
        $analysis['rating'] = "ดี";
        $analysis['description'] = "อัตรา Headshot ดี ({$hsPercent}%) อยู่ในเกณฑ์ที่น่าพอใจ";
    } elseif ($hsPercent >= 15) {
        $analysis['rating'] = "ปานกลาง";
        $analysis['description'] = "อัตรา Headshot ปานกลาง ({$hsPercent}%) ควรฝึกการเล็งหัวเพิ่มเติม";
    } else {
        $analysis['rating'] = "ต้องปรับปรุง";
        $analysis['description'] = "อัตรา Headshot ต่ำ ({$hsPercent}%) ควรฝึกการเล็งและ crosshair placement";
    }

    // Add advice based on shot distribution
    if ($lsPercent > 15) {
        $analysis['advice'] = "ควรปรับการวาง crosshair ให้สูงขึ้น เนื่องจากมีการยิงโดนขามากเกินไป";
    } elseif ($bsPercent > 70) {
        $analysis['advice'] = "พยายามเล็งให้สูงขึ้นเป็นระดับหัว แทนที่จะเล็งลำตัว";
    } else {
        $analysis['advice'] = "การกระจายการยิงอยู่ในเกณฑ์ดี พยายามรักษามาตรฐานนี้ไว้";
    }

    return $analysis;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Analysis</title>
    <style>
    :root {
        --primary-bg: #01182a;
        --secondary-bg: #022d4f;
        --accent-color: #04406e;
        --text-primary: #ffffff;
        --text-secondary: rgba(255,255,255,0.78);
        --border-color: rgba(255,255,255,0.06);
        --hover-bg: rgba(255,255,255,0.02);
    }


    .stat-card {
        background: var(--primary-bg);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: var(--accent-color);
        box-shadow: 0 10px 30px rgba(2,45,79,0.3);
    }

    .stat-header {
        background: linear-gradient(135deg, var(--secondary-bg) 0%, var(--accent-color) 100%);
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        text-align: center;
        box-shadow: 0 10px 40px rgba(2,45,79,0.4);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--text-primary);
        text-shadow: 0 0 10px rgba(2,45,79,0.5);
    }

    .stat-label {
        font-size: 0.9rem;
        color: rgba(236, 232, 225, 0.7);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .progress {
        height: 25px;
        background: rgba(1,24,42,0.8);
        border-radius: 10px;
    }

    .progress-bar {
        background: linear-gradient(90deg, var(--secondary-bg) 0%, var(--accent-color) 100%);
        font-weight: bold;
    }

    .badge-custom {
        background: var(--secondary-bg);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        color: var(--text-primary);
    }

    /* Update debug information colors */
    .debug-info {
        background: var(--primary-bg);
        color: var(--text-primary);
        padding: 20px;
        margin: 20px;
        border-radius: 5px;
        font-family: monospace;
        border: 1px solid var(--border-color);
    }

    /* KDA Display gradient update */
.kda-display {
    background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 4rem;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

    .icon-box {
        width: 60px;
        height: 60px;
        background: rgba(255, 70, 85, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }

    .icon-box i {
        font-size: 1.8rem;
        color: var(--valorant-red);
    }

    .ability-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .ability-item {
        background: rgba(15, 25, 35, 0.6);
        padding: 1rem;
        border-radius: 10px;
        text-align: center;
        border: 1px solid rgba(255, 70, 85, 0.1);
    }

    .team-badge {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        border-radius: 5px;
        font-weight: bold;
    }

    .team-red {
        background: rgba(255, 70, 85, 0.3);
    }

    .team-blue {
        background: rgba(70, 150, 255, 0.3);
    }

    .win-badge {
        background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 25px;
        font-size: 1.2rem;
        font-weight: bold;
        display: inline-block;
        box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
    }

.summary-box {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-start; /* ให้เนื้อหาเรียงจากบนลงล่าง */
    align-items: center; /* จัดให้อยู่กลางแนวนอน */
}

.summary-icon {
    font-size: 2rem;
    margin-bottom: 0.75rem; /* ระยะห่างระหว่าง icon กับ title */
}

.summary-title {
    font-size: 1rem;
    font-weight: bold;
    margin-bottom: 0.75rem; /* ระยะห่างระหว่าง title กับเนื้อหาด้านล่าง */
}

    </style>
    <?php include '../utils/link.php'; ?>

</head>

<body>
    <div class="container py-5">
        <!-- Header -->
        <div class="stat-header">
            <h1 class="display-4 mb-3"><i class="fas fa-chart-line"></i> VALORANT Match Analysis</h1>
            <div class="row justify-content-center">
                <div class="col-auto">
                    <span class="badge-custom"><i class="fas fa-user"></i> <?= $name ?>#<?= $tag ?></span>
                </div>
                <div class="col-auto">
                    <span class="badge-custom"><i class="fas fa-gamepad"></i> <?= $gameCount ?> Games Analyzed</span>
                </div>
                <div class="col-auto">
                    <span class="badge-custom"><i class="fas fa-globe"></i> <?= strtoupper($region) ?></span>
                </div>
            </div>
        </div>

        <!-- Add debug button in header if needed -->
        <div class="row justify-content-center">
            <!-- ...existing badges... -->
            <?php if (!$error_message): ?>
            <div class="col-auto">
            </div>
            <?php endif; ?>
        </div>

        <!-- Update error message display -->
        <?php if ($error_message): ?>
        <div class="alert alert-danger text-center mb-4">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error_message) ?>
            <br>
            <small>Please check if the player name, tag and region are correct.</small>
        </div>
        <?php endif; ?>

        <!-- KDA Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stat-card text-center">
                    <h3 class="mb-4"><i class="fas fa-crosshairs"></i> ประสิทธิภาพการต่อสู้</h3>
                    <div class="kda-display mb-3"><?= $totalKills ?> / <?= $totalDeaths ?> / <?= $totalAssists ?></div>
                    <p class="text-white mb-0">Total Kills / Deaths / Assists</p>
                    <hr class="my-4" style="border-color: rgba(255, 70, 85, 0.3);">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="stat-value"><?= $kd ?></div>
                            <div class="stat-label">K/D Ratio</div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-value"><?= $kda ?></div>
                            <div class="stat-label">KDA Ratio</div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-value"><?= $totalScore ?></div>
                            <div class="stat-label">Total Score</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Combat Stats -->
        <div class="row">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="icon-box">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h4 class="text-center mb-4">ความแม่นยำการยิง</h4>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-skull text-danger"></i> Headshots</span>
                            <span class="fw-bold"><?= $totalHead ?> (<?= $hsPercent ?>%)</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?= $hsPercent ?>%">
                                <?= $hsPercent ?>%</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-warning"></i> Bodyshots</span>
                            <span class="fw-bold"><?= $totalBody ?> (<?= $bsPercent ?>%)</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $bsPercent ?>%">
                                <?= $bsPercent ?>%</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-shoe-prints text-info"></i> Legshots</span>
                            <span class="fw-bold"><?= $totalLeg ?> (<?= $lsPercent ?>%)</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?= $lsPercent ?>%">
                                <?= $lsPercent ?>%</div>
                        </div>
                    </div>

                    <div class="text-center mt-4 p-3" style="background: rgba(255, 230, 0, 0.35); border-radius: 10px;">
                        <p class="mb-1"><i class="fas fa-exclamation-triangle"></i> <strong>คำแนะนำ</strong></p>
                        <?php
                        // Headshot Analysis
                        if ($hsPercent > 40) {
                            echo "<small>Headshot: เปอร์เซ็นต์สูงมาก รักษาสถิติให้ดีแบบนี้ต่อไป</small><br>";
                        } elseif ($hsPercent > 30) {
                            echo "<small>Headshot: สุดยอด! การเล็งหัวแม่นระดับแรงค์สูง</small><br>";
                        } elseif ($hsPercent > 20) {
                            echo "<small>Headshot: ดีมากแล้ว รักษามาตรฐานนี้ไว้</small><br>";
                        } elseif ($hsPercent > 15) {
                            echo "<small>Headshot: เริ่มดีขึ้นแล้ว ถือว่าเกินมาตรฐานผู้เล่นทั่วไป</small><br>";
                        } else {
                            echo "<small>Headshot: ยังต่ำไปหน่อย ควรฝึกการเล็งหัวให้มากขึ้น</small><br>";
                        }

                        // Bodyshot Analysis
                        if ($bsPercent > 75) {
                            echo "<small>Bodyshot: ยิงเข้าลำตัวเยอะเกินไป ลองปรับเล็งให้อยู่ระดับหัวมากขึ้น</small><br>";
                        } elseif ($bsPercent >= 60) {
                            echo "<small>Bodyshot: ปกติแล้ว ส่วนใหญ่ผู้เล่นจะอยู่ในช่วงนี้</small><br>";
                        } else {
                            echo "<small>Bodyshot: ถือว่าดี อาจเพราะยิงหัวได้เยอะ</small><br>";
                        }

                        // Legshot Analysis
                        if ($lsPercent > 15) {
                            echo "<small>Legshot: ยิงต่ำไปบ่อยเกินไป ปรับ crosshair ให้สูงขึ้นเล็งระดับหัว</small>";
                        } elseif ($lsPercent >= 7) {
                            echo "<small>Legshot: ปกติแล้ว ยังโอเคอยู่</small>";
                        } else {
                            echo "<small>Legshot: ยอดเยี่ยม! เล็งได้มั่นคง ไม่ตกลงไปโดนขาบ่อย</small>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="icon-box">
                        <i class="fas fa-fire"></i>
                    </div>
                    <h4 class="text-center mb-4">สถิติความเสียหาย</h4>

                    <?php
                    $avgDamagePerRound = $gameCount > 0 ? $totalDamageMade / ($gameCount * 13) : 0; // assuming average 13 rounds per game
                    $damageAnalysis = getDamageAnalysis($totalDamageMade, $totalDamageTaken, $avgDamagePerRound);
                    ?>

                    <div class="row text-center mb-4">
                        <div class="col-6">
                            <div class="stat-value" style="font-size: 2rem; color: #ff4655;">
                                <?= number_format($totalDamageMade) ?>
                            </div>
                            <div class="stat-label"><i class="fas fa-arrow-up"></i> Damage ที่สร้าง</div>
                        </div>

                        <div class="col-6">
                            <div class="stat-value" style="font-size: 2rem; color: #4690ff;">
                                <?= number_format($totalDamageTaken) ?>
                            </div>
                            <div class="stat-label"><i class="fas fa-arrow-down"></i> Damage ที่ได้รับ</div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <div class="badge-custom">
                            <i class="fas fa-chart-line"></i> เฉลี่ย <?= number_format($avgDamagePerRound, 1) ?> DMG/รอบ
                        </div>
                    </div>

                    <div class="text-center mt-4 p-3" style="background: rgba(255, 70, 85, 0.1); border-radius: 10px;">
                        <p class="mb-1"><i class="fas fa-exclamation-triangle"></i>
                            <strong>การวิเคราะห์ความเสียหาย</strong></p>
                        <div class="mb-2">
                            <small><i class="fas fa-arrow-up text-success"></i>
                                <?= $damageAnalysis['overall'] ?></small>
                        </div>
                        <div class="mb-2">
                            <small><i class="fas fa-arrow-down text-danger"></i>
                                <?= $damageAnalysis['advice'] ?></small>
                        </div>
                        <div class="mb-2">
                            <small><i class="fas fa-chart-bar text-warning"></i> <?= $damageAnalysis['adr'] ?></small>
                        </div>
                        <div>
                            <small><i class="fas fa-star text-info"></i> ระดับการเล่น:
                                <?= $damageAnalysis['rating'] ?></small>
                        </div>
                    </div>
                </div>
            </div>



        </div>

        <!-- Economy -->
        <div class="row mt-4">
            <div class="col">
                <div class="stat-card">
                    <div class="icon-box">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h4 class="text-center mb-4">การจัดการเศรษฐกิจ</h4>

                    <div class="row text-center mb-4">
                        <div class="col-6">
                            <div class="stat-value" style="font-size: 2rem;"><?= number_format($totalSpent) ?></div>
                            <div class="stat-label">เงินที่ใช้ทั้งหมด</div>
                        </div>
                        <div class="col-6">
                            <div class="stat-value" style="font-size: 2rem;">
                                <?= number_format($totalSpent / $gameCount) ?>
                            </div>
                            <div class="stat-label">เฉลี่ยต่อรอบ</div>
                        </div>
                    </div>

                    <div class="row text-center">
                        <div class="col-6">
                            <div class="stat-value" style="font-size: 2rem;"><?= number_format($totalLoadout) ?></div>
                            <div class="stat-label">มูลค่าอุปกรณ์รวม</div>
                        </div>
                        <div class="col-6">
                            <div class="stat-value" style="font-size: 2rem;">
                                <?= number_format($totalLoadout / $gameCount) ?></div>
                            <div class="stat-label">เฉลี่ยต่อรอบ</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <!-- Performance Summary -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="stat-card">
                    <h4 class="text-center mb-4"><i class="fas fa-chart-bar"></i> สรุปประสิทธิภาพ</h4>
                    <?php $shootingAnalysis = getShootingAnalysis($hsPercent, $bsPercent, $lsPercent); ?>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="p-3 text-center summary-box"
                                style="background: rgba(255, 193, 7, 0.16); border-radius: 10px; border: 1px solid rgba(255, 193, 7, 0.3);">
                                <i class="fas fa-crosshairs summary-icon" style="color: #ffc107;"></i>
                                <h6 class="summary-title">การยิง - <?= $shootingAnalysis['rating'] ?></h6><br>
                                <p class="mb-0 small"><?= $shootingAnalysis['description'] ?></p><br>
                                <p class="mb-0 small text-warning mt-2"><?= $shootingAnalysis['advice'] ?></p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3 text-center summary-box"
                                style="background: rgba(76, 175, 80, 0.1); border-radius: 10px; border: 1px solid rgba(76, 175, 80, 0.3);">
                                <i class="fas fa-heart-broken summary-icon" style="color: #4CAF50;"></i>
                                <h6 class="summary-title">ความเสียหาย</h6>
                                <?php
                                $avgDamagePerGame = $gameCount > 0 ? round($totalDamageMade / $gameCount) : 0;
                                $damageDiff = $totalDamageMade - $totalDamageTaken;
                                
                                if ($damageDiff > 0) {
                                    echo "<p class='mb-0 small text-success'>สร้างความเสียหายมากกว่าที่ได้รับ " . number_format($avgDamagePerGame) . " ต่อเกม</p>";
                                    echo "<div class='mt-2'>";
                                    echo "<br><p class='small text-white mb-1'>คำแนะนำ</p>";
                                    echo "<ul class='text-start small' style='list-style: none; padding-left: 0;'>";
                                    echo "<li><i class='fas fa-check-circle text-success me-1'></i> รักษามาตรฐานการเล่นแบบนี้ไว้</li>";
                                    echo "<li><i class='fas fa-info-circle text-info me-1'></i> พยายามเพิ่มความแม่นยำเพื่อสร้างความเสียหายให้มากขึ้น</li>";
                                    echo "</ul>";
                                    echo "</div>";
                                } else {
                                    echo "<p class='mb-0 small text-warning'>ควรระวังการรับความเสียหาย และหาโอกาสสร้างความเสียหายเพิ่มขึ้น</p>";
                                    echo "<div class='mt-2'>";
                                    echo "<br><p class='small text-white mb-1'>คำแนะนำในการปรับปรุง:</p>";
                                    echo "<ul class='text-start small' style='list-style: none; padding-left: 0;'>";
                                    echo "<li><i class='fas fa-shield-alt text-warning me-1'></i> ใช้ที่กำบังให้มากขึ้น</li>";
                                    echo "<li><i class='fas fa-crosshairs text-warning me-1'></i> ฝึกการหาจังหวะยิงที่ปลอดภัย</li>";
                                    echo "<li><i class='fas fa-map-marker-alt text-warning me-1'></i> ระวังตำแหน่งการยืน</li>";
                                    echo "</ul>";
                                    echo "</div>";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3 text-center summary-box" style="background: rgba(33, 150, 243, 0.1); border-radius: 10px; border: 1px solid rgba(33, 150, 243, 0.3);">
                                <i class="fas fa-coins summary-icon" style="color: #2196F3;"></i>
                                <h6 class="summary-title">เศรษฐกิจ</h6>
                                <?php
                                // คำนวณค่าเฉลี่ยต่อรอบ โดยใช้จำนวนรอบทั้งหมด
                                $avgSpentPerRound = $totalRounds > 0 ? round($totalSpent / $totalRounds) : 0;
                                $avgLoadoutPerRound = $totalRounds > 0 ? round($totalLoadout / $totalRounds) : 0;

                                // วิเคราะห์การใช้จ่ายต่อรอบ (ปรับเกณฑ์ให้เหมาะสมกับการเล่นต่อรอบ)
                                if ($avgSpentPerRound > 4000) {
                                    echo "<p class='mb-0 small text-info'>การใช้จ่ายสูง เฉลี่ย " . number_format($avgSpentPerRound) . " ต่อรอบ</p>";
                                } else if ($avgSpentPerRound > 2000) {
                                    echo "<p class='mb-0 small text-success'>การใช้จ่ายอยู่ในเกณฑ์ดี เฉลี่ย " . number_format($avgSpentPerRound) . " ต่อรอบ</p>";
                                } else {
                                    echo "<p class='mb-0 small text-warning'>การใช้จ่ายค่อนข้างต่ำ เฉลี่ย " . number_format($avgSpentPerRound) . " ต่อรอบ</p>";
                                }

                                // แสดงมูลค่าอุปกรณ์เฉลี่ยต่อรอบด้วย
                                echo "<p class='small text-white mt-2'>มูลค่าอุปกรณ์เฉลี่ย: " . number_format($avgLoadoutPerRound) . " ต่อรอบ</p>";
                                ?>
                                
                                <div class="mt-3">
                                    <p class="small text-white mb-1">คำแนะนำ:</p>
                                    <ul class="text-start small" style="list-style: none; padding-left: 0;">
                                        <?php
                                        // ปรับเงื่อนไขและคำแนะนำให้เหมาะสมกับค่าต่อรอบ
                                        if ($avgSpentPerRound > 4000) {
                                            echo "<li><i class='fas fa-exclamation-circle text-warning me-1'></i> ควรประหยัดในรอบ eco และ force buy</li>";
                                            echo "<li><i class='fas fa-piggy-bank text-info me-1'></i> พิจารณาการ save ในรอบที่เสียเปรียบ</li>";
                                        } else if ($avgSpentPerRound > 2000) {
                                            echo "<li><i class='fas fa-check-circle text-success me-1'></i> การบริหารเงินดี มีการ save และ full buy เหมาะสม</li>";
                                            echo "<li><i class='fas fa-sync text-info me-1'></i> รักษาการควบคุมเศรษฐกิจแบบนี้ต่อไป</li>";
                                        } else {
                                            echo "<li><i class='fas fa-arrow-up text-warning me-1'></i> ควรซื้ออุปกรณ์เพิ่มในรอบ full buy</li>";
                                            echo "<li><i class='fas fa-shield-alt text-info me-1'></i> อย่าลืมซื้อ armor ในรอบสำคัญ</li>";
                                        }
                                        ?>
                                        <li><i class='fas fa-users text-primary me-1'></i> ดูเศรษฐกิจทีมก่อนตัดสินใจซื้อ</li>
                                        <li><i class='fas fa-chart-line text-success me-1'></i> พยายามรักษาเงินให้เหนือ threshold สำหรับรอบถัดไป</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>

</html>