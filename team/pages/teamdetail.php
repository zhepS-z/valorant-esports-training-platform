<?php 
session_start(); // เริ่มต้นเซสชัน
define('ACCESS', true);
require_once '../../utils/apikey.php';  // โหลด API Key
require_once '../../auth/auth_check.php';
include '../../utils/db.php'; // ใช้ connection จาก db.php
require_once '../../utils/agent.php'; // 
require_once '../../utils/game_assets.php'; // โหลด map และ agent จาก DB

// ดึง maps จาก database
$dbMaps = get_maps_from_db($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>teamdetail</title>
    <link href="../css/teamdetail.css" rel="stylesheet">
    <style>


    </style>
    <?php include '../../utils/link.php'; ?>

</head>

<body>
    <!-- Dashboard Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-crosshairs me-2"></i>Valorant Performance Dashboard</h1>
                    <p class="mb-0">Track your team's training progress and statistics</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="teamDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-users me-1"></i> Team Phantom
                        </button>

                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Date Range Selector -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="valorant-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h5 class="card-title mb-0"><i class="far fa-calendar-alt me-2"></i>Date Range</h5>
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex">
                                    <input type="date" class="form-control me-2" value="2023-05-01">
                                    <span class="align-self-center">to</span>
                                    <input type="date" class="form-control ms-2 me-3" value="2023-05-15">
                                    <button class="btn btn-sm btn-outline-danger">Apply</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Player Stats Row -->
        <div class="row">
            <div class="col-md-12">
                <div class="valorant-card">
                    <div class="valorant-card-header">
                        <i class="fas fa-user me-2"></i>Player Performance Overview
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Player Selector -->
                            <div class="col-md-3">
                                <div class="list-group">
                                    <?php foreach (['jett', 'sova', 'cypher', 'brimstone', 'breach'] as $agent): ?>
                                        <a href="#" class="list-group-item list-group-item-action <?= $agent === 'jett' ? 'active' : '' ?>">
                                            <div class="d-flex align-items-center">
                                                <img src="<?= get_agent_image_url($agent) ?>" alt="<?= ucfirst($agent) ?>" class="player-avatar me-3">
                                                <div>
                                                    <h6 class="mb-0"><?= ucfirst($agent) ?></h6>
                                                    <small>
                                                        <?= $agent === 'jett' ? 'Duelist' : ($agent === 'sova' ? 'Initiator' : ($agent === 'cypher' ? 'Sentinel' : ($agent === 'brimstone' ? 'Controller' : 'Initiator'))) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Player Stats -->
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-label">Headshot %</div>
                                            <div class="stat-value">42%</div>
                                            <div class="progress progress-thin">
                                                <div class="progress-bar progress-bar-valorant" role="progressbar"
                                                    style="width: 42%" aria-valuenow="42" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-muted">+3% from last week</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-label">Win Rate</div>
                                            <div class="stat-value">65%</div>
                                            <div class="progress progress-thin">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: 65%" aria-valuenow="65" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-muted">Last 20 matches</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-label">Average Combat Score</div>
                                            <div class="stat-value">248</div>
                                            <div class="progress progress-thin">
                                                <div class="progress-bar progress-bar-valorant" role="progressbar"
                                                    style="width: 62%" aria-valuenow="62" aria-valuemin="0"
                                                    aria-valuemax="400"></div>
                                            </div>
                                            <small class="text-muted">Team avg: 210</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-label">K/D Ratio</div>
                                            <div class="stat-value">1.32</div>
                                            <div class="progress progress-thin">
                                                <div class="progress-bar progress-bar-valorant" role="progressbar"
                                                    style="width: 66%" aria-valuenow="66" aria-valuemin="0"
                                                    aria-valuemax="2"></div>
                                            </div>
                                            <small class="text-muted">Kills: 142 | Deaths: 108</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-label">First Blood %</div>
                                            <div class="stat-value">38%</div>
                                            <div class="progress progress-thin">
                                                <div class="progress-bar progress-bar-valorant" role="progressbar"
                                                    style="width: 38%" aria-valuenow="38" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-muted">28 first bloods</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <div class="stat-label">Clutch Success</div>
                                            <div class="stat-value">45%</div>
                                            <div class="progress progress-thin">
                                                <div class="progress-bar progress-bar-valorant" role="progressbar"
                                                    style="width: 45%" aria-valuenow="45" aria-valuemin="0"
                                                    aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-muted">9/20 attempts</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Performance Row -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="valorant-card">
                    <div class="valorant-card-header">
                        <i class="fas fa-users me-2"></i>Team Performance
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-pills mb-3" id="team-performance-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="overview-tab" data-bs-toggle="pill"
                                    data-bs-target="#overview" type="button" role="tab" aria-controls="overview"
                                    aria-selected="true">Overview</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="rounds-tab" data-bs-toggle="pill" data-bs-target="#rounds"
                                    type="button" role="tab" aria-controls="rounds" aria-selected="false">Round
                                    Analysis</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="maps-tab" data-bs-toggle="pill" data-bs-target="#maps"
                                    type="button" role="tab" aria-controls="maps" aria-selected="false">Map
                                    Performance</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="team-performance-tabContent">
                            <div class="tab-pane fade show active" id="overview" role="tabpanel"
                                aria-labelledby="overview-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="chart-container">
                                            <!-- Win/Loss Chart -->
                                            <canvas id="winLossChart"></canvas>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="chart-container">
                                            <!-- KDA Comparison Chart -->
                                            <canvas id="kdaComparisonChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <h5>Recent Matches</h5>
                                        <div class="recent-match win-match">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong>Win 13-9</strong> | Haven
                                                </div>
                                                <div>
                                                    <small class="text-muted">May 14, 2023</small>
                                                </div>
                                            </div>
                                            <div class="team-composition mt-2">
                                                <?php foreach (['jett', 'sova', 'sage', 'astra', 'cypher'] as $agent): ?>
                                                    <img src="<?= get_agent_image_url($agent) ?>" alt="<?= ucfirst($agent) ?>" class="agent-icon">
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="recent-match loss-match">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong>Loss 10-13</strong> | Bind
                                                </div>
                                                <div>
                                                    <small class="text-muted">May 12, 2023</small>
                                                </div>
                                            </div>
                                            <div class="team-composition mt-2">
                                                <?php foreach (['raze', 'killjoy', 'skye', 'viper', 'gekko'] as $agent): ?>
                                                    <img src="<?= get_agent_image_url($agent) ?>" alt="<?= ucfirst($agent) ?>" class="agent-icon">
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="rounds" role="tabpanel" aria-labelledby="rounds-tab">
                                <div class="chart-container">
                                    <!-- Round Win Analysis Chart -->
                                    <canvas id="roundWinAnalysisChart"></canvas>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <h5>Attack Win %</h5>
                                        <div class="progress mb-3">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 52%"
                                                aria-valuenow="52" aria-valuemin="0" aria-valuemax="100">52%</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Defense Win %</h5>
                                        <div class="progress mb-3">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 48%"
                                                aria-valuenow="48" aria-valuemin="0" aria-valuemax="100">48%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="maps" role="tabpanel" aria-labelledby="maps-tab">
                                <div class="row">
                                    <?php 
                                    // ดึง 3 map แรก จาก database สำหรับแสดง
                                    $mapCount = 0;
                                    foreach ($dbMaps as $map):
                                        if ($mapCount >= 3) break;
                                        $btnFile = $map['button_image_filename'] ?? $map['image_filename']; // ใช้ button_image_filename ถ้ามี
                                        $mapButtonPath = '../../img/maps_button/' . $btnFile;
                                        $winRates = [67, 45, 58]; // Demo data
                                        $trends = ['+12%', '-5%', '+3%'];
                                        $trendClasses = ['text-success', 'text-danger', 'text-success'];
                                        $mapCount++;
                                    ?>
                                    <div class="col-md-4">
                                        <div class="valorant-card">
                                            <img src="<?= htmlspecialchars($mapButtonPath) ?>" alt="<?= htmlspecialchars($map['name']) ?>" class="map-image" onerror="this.outerHTML='<div style=\"width:100%;height:200px;display:flex;align-items:center;justify-content:center;background:#6c757d;\"><i class=\"fas fa-map\" style=\"font-size:48px;color:white;\"></i></div>'" >
                                            <div class="card-body">
                                                <h5><?= htmlspecialchars($map['name']) ?></h5>
                                                <div class="d-flex justify-content-between">
                                                    <span>Win Rate: <strong><?= $winRates[$mapCount - 1] ?>%</strong></span>
                                                    <span class="<?= $trendClasses[$mapCount - 1] ?>"><?= $trends[$mapCount - 1] ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="valorant-card">
                    <div class="valorant-card-header">
                        <i class="fas fa-trophy me-2"></i>Team Rankings
                    </div>
                    <div class="card-body">
                        <div class="stat-card">
                            <div class="stat-label">Current Rank</div>
                            <div class="stat-value text-center">Immortal 3</div>
                            <div class="text-center mb-3">
                                <img src="../../img/rank/immortal3.png" alt="Immortal 3" class="img-fluid"
                                    style="max-width: 80px;">
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar progress-bar-valorant" role="progressbar" style="width: 65%"
                                    aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">65% to Radiant</div>
                            </div>
                        </div>
                        <hr>
                        <h5>Player Rankings</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Player</th>
                                        <th>Rank</th>
                                        <th>RR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>JettMain</td>
                                        <td>Diamond 3</td>
                                        <td>78</td>
                                    </tr>
                                    <tr>
                                        <td>SovaPro</td>
                                        <td>Immortal 3</td>
                                        <td>45</td>
                                    </tr>
                                    <tr>
                                        <td>CypherCam</td>
                                        <td>Diamond 1</td>
                                        <td>92</td>
                                    </tr>
                                    <tr>
                                        <td>BrimStone</td>
                                        <td>Platinum 3</td>
                                        <td>88</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script>
    // You would implement actual chart initialization here
    // This is just a placeholder for the demo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard loaded - charts would be initialized here');
    });

    // Win/Loss Chart
    const winLossCtx = document.getElementById('winLossChart').getContext('2d');
    new Chart(winLossCtx, {
        type: 'pie',
        data: {
            labels: ['Wins', 'Losses'],
            datasets: [{
                data: [65, 35], // ตัวอย่างข้อมูล: 65% ชนะ, 35% แพ้
                backgroundColor: ['#4CAF50', '#F44336'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Win/Loss Ratio'
                }
            }
        }
    });

    // KDA Comparison Chart
    const kdaCtx = document.getElementById('kdaComparisonChart').getContext('2d');
    new Chart(kdaCtx, {
        type: 'bar',
        data: {
            labels: ['Kills', 'Deaths', 'Assists'],
            datasets: [{
                label: 'Player KDA',
                data: [20, 10, 15], // ตัวอย่างข้อมูล: 20 Kills, 10 Deaths, 15 Assists
                backgroundColor: ['#2196F3', '#F44336', '#FFC107'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false,
                },
                title: {
                    display: true,
                    text: 'KDA Comparison'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Round Win Analysis Chart
    const roundWinCtx = document.getElementById('roundWinAnalysisChart').getContext('2d');
    new Chart(roundWinCtx, {
        type: 'line',
        data: {
            labels: ['Round 1', 'Round 2', 'Round 3', 'Round 4', 'Round 5', 'Round 6', 'Round 7', 'Round 8', 'Round 9', 'Round 10'], // ตัวอย่างรอบ
            datasets: [{
                label: 'Win Rate (%)',
                data: [60, 70, 50, 80, 90, 40, 70, 60, 85, 75], // ตัวอย่างข้อมูล Win Rate
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Round Win Analysis'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Win Rate (%)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Rounds'
                    }
                }
            }
        }
    });
    </script>
</body>

</html>