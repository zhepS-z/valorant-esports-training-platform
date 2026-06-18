<?php
session_start();
define('ACCESS', true);
require_once '../utils/agent.php';
require_once '../utils/game_assets.php';
include '../utils/db.php';

$mapDir = "../img/maps";
$mapButtonDir = "../img/maps_button";
$agentDir = "../img/agents";

// โหลดจาก DB เท่านั้น
$dbMaps = get_maps_from_db($conn);
$dbAgents = get_agents_from_db($conn);

// สร้าง maps array จาก database
$maps = [];
foreach ($dbMaps as $m) {
    $btnFile = $m['button_image_filename'] ?? $m['image_filename']; // ใช้ button_image_filename ถ้ามี
    $maps[] = [
        'name' => $m['name'],
        'filename' => $m['image_filename'],
        'image_path' => $mapDir . '/' . $m['image_filename'],
        'button_path' => $mapButtonDir . '/' . $btnFile
    ];
}

// หา default map (Ascent)
$defaultMapData = $maps[0] ?? null;
foreach ($maps as $m) {
    if (strtolower($m['filename']) === 'ascent.png') {
        $defaultMapData = $m;
        break;
    }
}
if (!$defaultMapData) $defaultMapData = $maps[0];

// สร้าง agents array จาก database
$agents = [];
foreach ($dbAgents as $a) {
    $img = $a['image_url'];
    if (strpos($img, 'http') !== 0) {
        $img = ($img[0] === '/' || strpos($img, 'img/') === 0) ? '../' . ltrim($img, '/') : '../' . $img;
    }
    $agents[] = [
        'name' => $a['name'],
        'image' => $img
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strategy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include '../utils/link.php'; ?>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: linear-gradient(135deg, #01111c 0%, #01111c 50%, #022d4f 100%);
        color: #e0e0e0;
        height: 100vh;
        width: 100vw;
        overflow: hidden;
    }

    .container-main {
        display: grid;
        grid-template-columns: 280px 1fr 280px;
        height: 90vh;
        gap: 12px;
        padding: 12px;
        padding-left: 100px;
        padding-right: 50px;
    }

    .sidebar {
        display: flex;
        flex-direction: column;
        background: rgba(20, 25, 50, 0.4);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(100, 150, 255, 0.1);
        border-radius: 16px;
        padding: 16px;
        gap: 16px;
        overflow-y: auto;
    }

    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(100, 150, 255, 0.3);
        border-radius: 3px;
    }

    .section-title {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #6495ff;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-title i {
        font-size: 14px;
    }

    .map-dropdown {
        position: relative;
    }

    .map-btn {
        width: 100%;
        height: 72px;
        border: 1px solid rgba(100, 150, 255, 0.2);
        background: rgba(30, 35, 70, 0.6);
        border-radius: 12px;
        cursor: pointer;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        padding: 0;
    }

    .map-btn:hover {
        border-color: rgba(100, 150, 255, 0.5);
        background: rgba(30, 35, 70, 1);
        box-shadow: 0 8px 24px rgba(100, 150, 255, 0.15);
    }

    .map-btn img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .map-btn:hover img {
        transform: scale(1.05);
    }

    .map-list {
        position: absolute;
        top: 85px;
        left: 0;
        right: 0;
        background: rgba(15, 20, 45, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(100, 150, 255, 0.2);
        border-radius: 12px;
        max-height: 240px;
        overflow-y: auto;
        z-index: 100;
        display: none;
        flex-direction: column;
    }

    .map-list.active {
        display: flex;
    }

    .map-list::-webkit-scrollbar {
        width: 6px;
    }

    .map-list::-webkit-scrollbar-thumb {
        background: rgba(100, 150, 255, 0.3);
        border-radius: 3px;
    }

    .map-item {
        height: 60px;
        border-bottom: 1px solid rgba(100, 150, 255, 0.1);
        cursor: pointer;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .map-item:last-child {
        border-bottom: none;
    }

    .map-item:hover {
        background: rgba(100, 150, 255, 0.1);
    }

    .map-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .tools-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .tool-btn {
        aspect-ratio: 1;
        background: linear-gradient(135deg, rgba(100, 150, 255, 0.1) 0%, rgba(100, 150, 255, 0.05) 100%);
        border: 1px solid rgba(100, 150, 255, 0.2);
        border-radius: 12px;
        color: #6495ff;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tool-btn:hover {
        background: linear-gradient(135deg, rgba(100, 150, 255, 0.2) 0%, rgba(100, 150, 255, 0.1) 100%);
        border-color: rgba(100, 150, 255, 0.5);
        box-shadow: 0 8px 24px rgba(100, 150, 255, 0.2);
        transform: translateY(-2px);
    }

    .tool-btn.active {
        border-color: rgba(100, 150, 255, 0.8);
        background: linear-gradient(135deg, rgba(100, 150, 255, 0.3) 0%, rgba(100, 150, 255, 0.2) 100%);
        box-shadow: 0 0 20px rgba(100, 150, 255, 0.4);
    }

    .tool-btn:active {
        transform: translateY(0);
    }

    .center-area {
        display: flex;
        flex-direction: column;
        background: rgba(20, 25, 50, 0.4);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(100, 150, 255, 0.1);
        border-radius: 16px;
        padding: 16px;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .map-canvas {
        position: relative;
        width: 100%;
        height: 100%;
        background: rgba(10, 15, 40, 0.5);
        border-radius: 12px;
        border: 2px dashed rgba(100, 150, 255, 0.2);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .map-canvas.dragover {
        border-color: rgba(100, 150, 255, 0.5);
        background: rgba(100, 150, 255, 0.05);
    }

    #mainMapImg {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
    }

    #drawingCanvas {
        position: absolute;
        top: 0;
        left: 0;
        background: transparent;
        cursor: crosshair;
        z-index: 10;
    }

    /* Custom cursor สำหรับ draw/erase */
    .custom-cursor {
        position: absolute;
        border: 2px solid rgba(100, 150, 255, 0.8);
        border-radius: 50%;
        pointer-events: none;
        z-index: 100;
        display: none;
        transform: translate(-50%, -50%);
        transition: width 0.1s ease, height 0.1s ease;
    }

    .custom-cursor.erase-mode {
        border-color: rgba(255, 100, 100, 0.8);
        background: rgba(255, 100, 100, 0.1);
    }

    .custom-cursor.draw-mode {
        border-color: rgba(100, 150, 255, 0.8);
        background: rgba(100, 150, 255, 0.1);
    }

    .map-placeholder {
        text-align: center;
        color: rgba(200, 200, 200, 0.5);
        z-index: 5;
    }

    .map-placeholder i {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.3;
    }

    .agent-on-map {
        position: absolute;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        cursor: grab;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 20;
        object-fit : cover;
    }

    .agent-on-map:hover {
        transform: scale(1.1);
    }

    .agent-on-map:active {
        cursor: grabbing;
    }

    .agent-on-map img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .agent-on-map.ally {
        box-shadow: 0 0 16px rgba(255, 100, 100, 0.6), inset 0 0 8px rgba(255, 100, 100, 0.3);
    }

    .agent-on-map.enemy {
        box-shadow: 0 0 16px rgba(100, 200, 255, 0.6), inset 0 0 8px rgba(100, 200, 255, 0.3);
    }

    .tool-options {
        display: none;
        gap: 8px;
        margin-top: 8px;
        padding: 8px;
        background: rgba(30, 35, 70, 0.4);
        border-radius: 8px;
        border: 1px solid rgba(100, 150, 255, 0.1);
    }

    .tool-options.active {
        display: flex;
        flex-direction: column;
    }

    .color-group {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .color-btn {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: 2px solid rgba(100, 150, 255, 0.3);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .color-btn:hover,
    .color-btn.selected {
        border-color: rgba(100, 150, 255, 0.8);
        transform: scale(1.1);
        box-shadow: 0 0 8px rgba(100, 150, 255, 0.5);
    }

    /* สำหรับสีดำให้มี border ที่เห็นชัดเจน */
    .color-btn[style*="#000000"] {
        border-color: rgba(200, 200, 200, 0.5);
    }

    .color-btn[style*="#000000"]:hover,
    .color-btn[style*="#000000"].selected {
        border-color: rgba(100, 150, 255, 0.8);
    }

    .slider-control {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .slider-label {
        font-size: 10px;
        text-transform: uppercase;
        color: #6495ff;
        font-weight: 600;
    }

    .slider-control input {
        width: 100%;
        cursor: pointer;
    }

    .text-input-control {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .text-input {
        width: 100%;
        padding: 6px;
        background: rgba(30, 35, 70, 0.6);
        border: 1px solid rgba(100, 150, 255, 0.2);
        border-radius: 6px;
        color: #e0e0e0;
        font-size: 11px;
        outline: none;
    }

    .text-input:focus {
        border-color: rgba(100, 150, 255, 0.5);
        box-shadow: 0 0 8px rgba(100, 150, 255, 0.1);
    }

    .agent-select {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .team-toggle {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(30, 35, 70, 0.6);
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid rgba(100, 150, 255, 0.2);
    }

    .toggle-switch {
        position: relative;
        width: 44px;
        height: 24px;
        background: rgba(100, 150, 255, 0.2);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        padding: 0;
    }

    .toggle-switch.ally {
        background: rgba(255, 100, 100, 0.3);
    }

    .toggle-switch.enemy {
        background: rgba(100, 200, 255, 0.3);
    }

    .toggle-switch::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 20px;
        height: 20px;
        background: rgba(255, 100, 100, 0.8);
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .toggle-switch.enemy::after {
        left: 22px;
        background: rgba(100, 200, 255, 0.8);
    }

    .team-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .agent-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }

    .agent-grid::-webkit-scrollbar {
        width: 6px;
    }

    .agent-grid::-webkit-scrollbar-thumb {
        background: rgba(100, 150, 255, 0.3);
        border-radius: 3px;
    }

    .agent-card {
        aspect-ratio: 1;
        background: rgba(30, 35, 70, 0.6);
        border: 1px solid rgba(100, 150, 255, 0.2);
        border-radius: 12px;
        cursor: grab;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .agent-card:hover {
        border-color: rgba(100, 150, 255, 0.5);
        background: rgba(30, 35, 70, 1);
        box-shadow: 0 8px 24px rgba(100, 150, 255, 0.15);
        transform: translateY(-4px);
    }

    .agent-card:active {
        cursor: grabbing;
    }

    .agent-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .agent-card.selected {
        border-color: rgba(100, 150, 255, 0.8);
        box-shadow: 0 0 16px rgba(100, 150, 255, 0.3);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .agent-on-map {
        animation: fadeIn 0.3s ease;
    }

    .ability-on-map {
        position: absolute;
        cursor: move;
        transition: all 0.2s ease;
        z-index: 15;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ability-on-map:hover {
        transform: scale(1.1) translate(-50%, -50%);
        z-index: 25;
    }

    .ability-on-map i {
        pointer-events: none;
    }

    .ability-smoke {
        background: radial-gradient(circle, rgba(150, 150, 150, 0.6), rgba(80, 80, 80, 0.3));
        border: 2px solid rgba(200, 200, 200, 0.4);
        border-radius: 50%;
    }

    .ability-molly {
        background: radial-gradient(circle, rgba(255, 100, 0, 0.7), rgba(255, 0, 0, 0.3));
        border: 2px solid rgba(255, 150, 0, 0.6);
        box-shadow: 0 0 20px rgba(255, 100, 0, 0.5);
        border-radius: 50%;
    }

    .ability-spike {
        background: radial-gradient(circle, rgba(255, 200, 0, 0.8), rgba(200, 150, 0, 0.4));
        border: 3px solid rgba(255, 220, 0, 0.8);
        box-shadow: 0 0 25px rgba(255, 200, 0, 0.6);
        border-radius: 50%;
    }

    .ability-pin {
        background: none;
        border: none;
    }

    /* Ability items ใน sidebar */
    .ability-items {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .ability-item {
        aspect-ratio: 1;
        background: rgba(30, 35, 70, 0.6);
        border: 1px solid rgba(100, 150, 255, 0.2);
        border-radius: 12px;
        cursor: grab;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .ability-item:hover {
        border-color: rgba(100, 150, 255, 0.5);
        background: rgba(30, 35, 70, 1);
        box-shadow: 0 8px 24px rgba(100, 150, 255, 0.15);
        transform: translateY(-4px);
    }

    .ability-item:active {
        cursor: grabbing;
    }

    .ability-item.spike {
        color: #ffd700;
    }

    .ability-item.smoke {
        color: #aaa;
    }

    .ability-item.molly {
        color: #ff6600;
    }

    .ability-item.pin {
        color: #ff4444;
    }
    </style>
</head>

<body>
    <div class="container-main">
        <!-- LEFT SIDEBAR -->
        <div class="sidebar">
            <div class="section-title">
                <i class="fas fa-map"></i> Map
            </div>

            <div class="map-dropdown">
                <button class="map-btn" id="mapBtn">
                    <img id="selectedMapImg" src="<?= $defaultMapData ? $defaultMapData['button_path'] : ($mapButtonDir . '/' . ($defaultMap ?? 'ascent.png')) ?>" alt="Map">
                </button>
                <div class="map-list" id="mapList">
                    <?php if (!empty($dbMaps)): ?>
                    <?php foreach ($maps as $m): ?>
                    <div class="map-item" onclick="selectMapDropdown('<?= htmlspecialchars($m['image_path']) ?>', '<?= htmlspecialchars(pathinfo($m['filename'], PATHINFO_FILENAME)) ?>')">
                        <img src="<?= htmlspecialchars($m['button_path']) ?>" alt="<?= htmlspecialchars($m['name']) ?>">
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <?php foreach ($maps as $map): ?>
                    <div class="map-item" onclick="selectMapDropdown('<?= $mapDir . '/' . $map ?>', '<?= pathinfo($map, PATHINFO_FILENAME) ?>')">
                        <img src="<?= $mapButtonDir . '/' . $map ?>" alt="<?= pathinfo($map, PATHINFO_FILENAME) ?>">
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-title" style="margin-top: 12px;">
                <i class="fas fa-tools"></i> Tools
            </div>

            <div class="tools-grid">
                <button class="tool-btn" id="drawBtn" title="Draw" onclick="activateTool('draw')">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="tool-btn" id="eraseBtn" title="Erase" onclick="activateTool('erase')">
                    <i class="fas fa-eraser"></i>
                </button>
                <button class="tool-btn" id="clearBtn" title="Clear" onclick="clearMap()">
                    <i class="fas fa-trash"></i>
                </button>
                <button class="tool-btn" id="textBtn" title="Text" onclick="activateTool('text')">
                    <i class="fas fa-font"></i>
                </button>
            </div>

            <div class="section-title" style="margin-top: 12px;">
                <i class="fas fa-flask"></i> Abilities
            </div>

            <div class="ability-items">
                <div class="ability-item spike" draggable="true" ondragstart="startAbilityDrag(event, 'spike')" title="Spike">
                    <img src="../img/strategy_icon/spike.png" alt="Spike" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="ability-item smoke" draggable="true" ondragstart="startAbilityDrag(event, 'smoke')" title="Smoke">
                    <img src="../img/strategy_icon/smoke.png" alt="Smoke" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="ability-item molly" draggable="true" ondragstart="startAbilityDrag(event, 'molly')" title="Molly">
                    <img src="../img/strategy_icon/molly.png" alt="Molly" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="ability-item pin" draggable="true" ondragstart="startAbilityDrag(event, 'pin')" title="Pin">
                    <i class="fas fa-map-pin"></i>
                </div>
            </div>

            <!-- Draw/Erase Options -->
            <div class="tool-options" id="drawOptions">
                <div class="slider-label">Color</div>
                <div class="color-group">
                    <div class="color-btn selected" style="background: #000000;" onclick="setColor('#000000')"></div>
                    <div class="color-btn" style="background: #64c8ff;" onclick="setColor('#64c8ff')"></div>
                    <div class="color-btn" style="background: #64ff64;" onclick="setColor('#64ff64')"></div>
                    <div class="color-btn" style="background: #ffff64;" onclick="setColor('#ffff64')"></div>
                    <div class="color-btn" style="background: #ff64ff;" onclick="setColor('#ff64ff')"></div>
                    <div class="color-btn" style="background: #ffffff;" onclick="setColor('#ffffff')"></div>
                    <div class="color-btn" style="background: #a30000ff;" onclick="setColor('#a30000ff')"></div>
                </div>
                <div class="slider-control">
                    <label class="slider-label">Brush Size: <span id="sizeValue">3</span></label>
                    <input type="range" id="brushSize" min="1" max="50" value="3" oninput="updateBrushSize(this.value)">
                </div>
            </div>

            <!-- Text Options -->
            <div class="tool-options" id="textOptions">
                <div class="text-input-control">
                    <label class="slider-label">Text</label>
                    <input type="text" class="text-input" id="textInput" placeholder="Enter text...">
                </div>
                <div class="slider-control">
                    <label class="slider-label">Size: <span id="fontSizeValue">24</span></label>
                    <input type="range" id="fontSize" min="12" max="60" value="24" oninput="updateFontSize(this.value)">
                </div>
            </div>
        </div>

        <!-- CENTER AREA -->
        <div class="center-area">
            <div class="map-canvas" id="mapCanvas" ondragover="handleDragOver(event)" ondrop="handleDrop(event)"
                ondragleave="handleDragLeave(event)">
                <canvas id="drawingCanvas"></canvas>
                <div class="custom-cursor" id="customCursor"></div>
                <div class="map-placeholder">
                    <i class="fas fa-image"></i>
                    <p>Select a map to start planning</p>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDEBAR -->
        <div class="sidebar">
            <div class="section-title">
                <i class="fas fa-users"></i> Team
            </div>

            <div class="team-toggle">
                <button class="toggle-switch ally" id="teamSwitch" onclick="toggleTeam()"></button>
                <span class="team-label" id="teamLabel">Ally</span>
            </div>

            <div class="section-title" style="margin-top: 12px;">
                <i class="fas fa-user-secret"></i> Agents
            </div>

            <div class="agent-grid" id="agentGrid">
                <?php foreach ($agents as $a): ?>
                <div class="agent-card" draggable="true" ondragstart="startDrag(event, '<?= htmlspecialchars($a['image']) ?>')">
                    <img src="<?= htmlspecialchars($a['image']) ?>" alt="<?= htmlspecialchars($a['name']) ?>" onerror="this.src='https://via.placeholder.com/150?text=?'">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="../strategy/team_toggle.js"></script>
    <script>
    let currentTeam = 'ally';
    let selectedMap = "<?= $defaultMapData ? $defaultMapData['image_path'] : ($mapDir . '/' . ($defaultMap ?? 'ascent.png')) ?>";
    let currentTool = null;
    let isDrawing = false;
    let currentColor = '#000000';
    let brushSize = 3;
    let fontSize = 24;
    let canvas, ctx;
    let abilities = [];
    let customCursor = null;

    document.addEventListener("DOMContentLoaded", () => {
        canvas = document.getElementById('drawingCanvas');
        const container = document.getElementById('mapCanvas');
        canvas.width = container.clientWidth;
        canvas.height = container.clientHeight;
        ctx = canvas.getContext('2d');

        const mapCanvas = document.getElementById('mapCanvas');
        mapCanvas.innerHTML =
            `<canvas id="drawingCanvas"></canvas><div class="custom-cursor" id="customCursor"></div><img id="mainMapImg" src="${selectedMap}" alt="Map">`;
        canvas = document.getElementById('drawingCanvas');
        customCursor = document.getElementById('customCursor');
        canvas.width = container.clientWidth;
        canvas.height = container.clientHeight;
        ctx = canvas.getContext('2d');

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);
        
        // Track mouse movement สำหรับ custom cursor
        canvas.addEventListener('mousemove', updateCursorPosition);
        canvas.addEventListener('mouseenter', showCustomCursor);
        canvas.addEventListener('mouseleave', hideCustomCursor);
    });

    document.getElementById('mapBtn').addEventListener('click', () => {
        document.getElementById('mapList').classList.toggle('active');
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.map-dropdown')) {
            document.getElementById('mapList').classList.remove('active');
        }
    });

    function toggleTeam() {
        currentTeam = currentTeam === 'ally' ? 'enemy' : 'ally';
        const teamSwitch = document.getElementById('teamSwitch');
        teamSwitch.classList.toggle('ally');
        teamSwitch.classList.toggle('enemy');
        document.getElementById('teamLabel').textContent = currentTeam.charAt(0).toUpperCase() + currentTeam.slice(1);
    }

    function activateTool(tool) {
        document.querySelectorAll('.tool-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tool-options').forEach(opt => opt.classList.remove('active'));

        if (currentTool === tool) {
            currentTool = null;
            canvas.style.cursor = 'default';
            hideCustomCursor();
        } else {
            currentTool = tool;
            document.getElementById(tool + 'Btn').classList.add('active');

            if (tool === 'draw' || tool === 'erase') {
                document.getElementById('drawOptions').classList.add('active');
                canvas.style.cursor = 'none'; // ซ่อน cursor เดิม
                updateCustomCursor();
                showCustomCursor();
            } else if (tool === 'text') {
                document.getElementById('textOptions').classList.add('active');
                canvas.style.cursor = 'text';
                hideCustomCursor();
            }
        }
    }

    function setColor(color) {
        currentColor = color;
        document.querySelectorAll('.color-btn').forEach(btn => btn.classList.remove('selected'));
        event.target.classList.add('selected');
    }

    function updateBrushSize(value) {
        brushSize = value;
        document.getElementById('sizeValue').textContent = value;
        updateCustomCursor();
    }

    function updateFontSize(value) {
        fontSize = value;
        document.getElementById('fontSizeValue').textContent = value;
    }

    function startDrawing(e) {
        if (!currentTool) return;
        if (currentTool === 'text') return;
        
        isDrawing = true;
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        ctx.beginPath();
        ctx.moveTo(x, y);
    }

    function draw(e) {
        if (!isDrawing || !currentTool || currentTool === 'text') return;

        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        ctx.lineWidth = currentTool === 'erase' ? brushSize * 3 : brushSize;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        if (currentTool === 'erase') {
            ctx.clearRect(x - brushSize, y - brushSize, brushSize * 2, brushSize * 2);
        } else {
            ctx.strokeStyle = currentColor;
            ctx.lineTo(x, y);
            ctx.stroke();
        }
    }

    function stopDrawing() {
        if (currentTool === 'draw') {
            ctx.closePath();
        }
        isDrawing = false;
    }

    // Text tool ใช้ double click แทน single click เพื่อไม่ชนกับ ability placement
    canvas.addEventListener('dblclick', function(e) {
        if (currentTool === 'text') {
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const text = document.getElementById('textInput').value;

            if (text) {
                ctx.font = `${fontSize}px Arial`;
                ctx.fillStyle = currentColor;
                ctx.fillText(text, x, y);
            }
        }
    });

    // Functions สำหรับ custom cursor
    function updateCustomCursor() {
        if (!customCursor) return;
        
        if (currentTool === 'draw' || currentTool === 'erase') {
            const size = currentTool === 'erase' ? brushSize * 3 : brushSize;
            customCursor.style.width = (size * 2) + 'px';
            customCursor.style.height = (size * 2) + 'px';
            
            // เปลี่ยนสีตามโหมด
            customCursor.className = 'custom-cursor ' + (currentTool === 'erase' ? 'erase-mode' : 'draw-mode');
        }
    }

    function updateCursorPosition(e) {
        if (!customCursor || (currentTool !== 'draw' && currentTool !== 'erase')) return;
        
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        customCursor.style.left = x + 'px';
        customCursor.style.top = y + 'px';
    }

    function showCustomCursor() {
        if (!customCursor) return;
        if (currentTool === 'draw' || currentTool === 'erase') {
            customCursor.style.display = 'block';
        }
    }

    function hideCustomCursor() {
        if (!customCursor) return;
        customCursor.style.display = 'none';
    }

    function handleDragOver(e) {
        e.preventDefault();
        document.getElementById('mapCanvas').classList.add('dragover');
    }

    function handleDragLeave(e) {
        document.getElementById('mapCanvas').classList.remove('dragover');
    }

    function handleDrop(e) {
        e.preventDefault();
        document.getElementById('mapCanvas').classList.remove('dragover');

        const mapCanvas = document.getElementById('mapCanvas');
        const rect = mapCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        // ตรวจสอบการลาก ability
        const abilityType = e.dataTransfer.getData('abilityType');
        if (abilityType) {
            placeAbilityFromDrag(abilityType, x, y);
            return;
        }

        // ตรวจสอบการลาก agent
        const agentSrc = e.dataTransfer.getData('agentSrc');
        if (!agentSrc) return;

        let existingAgent = Array.from(mapCanvas.children).find(child => child.className && child.className.includes(
            'agent-on-map') && child.dataset.agentSrc === agentSrc && child.dataset.team === currentTeam);

        if (existingAgent) {
            existingAgent.style.left = x + 'px';
            existingAgent.style.top = y + 'px';
        } else {
            const agent = document.createElement('div');
            agent.className = `agent-on-map ${currentTeam}`;
            agent.innerHTML = `<img src="${agentSrc}" alt="Agent">`;
            agent.style.left = x + 'px';
            agent.style.top = y + 'px';
            agent.style.transform = 'translate(-50%, -50%)';
            agent.draggable = true;
            agent.dataset.agentSrc = agentSrc;
            agent.dataset.team = currentTeam;

            agent.addEventListener('dragstart', (e) => {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('agentSrc', agentSrc);
            });

            agent.addEventListener('dragend', (e) => {
                const mapCanvas = document.getElementById('mapCanvas');
                const rect = mapCanvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                if (x > 0 && y > 0 && x < rect.width && y < rect.height) {
                    agent.style.left = x + 'px';
                    agent.style.top = y + 'px';
                }
            });

            mapCanvas.appendChild(agent);
        }
    }

    function startDrag(e, agentImg) {
        e.dataTransfer.setData('agentSrc', agentImg);
        e.dataTransfer.effectAllowed = 'copy';
    }

    function startAbilityDrag(e, abilityType) {
        e.dataTransfer.setData('abilityType', abilityType);
        e.dataTransfer.effectAllowed = 'copy';
    }

    function placeAbilityFromDrag(abilityType, x, y) {
        const mapCanvas = document.getElementById('mapCanvas');

        const ability = document.createElement('div');
        ability.className = `ability-on-map ability-${abilityType}`;
        ability.style.left = x + 'px';
        ability.style.top = y + 'px';
        ability.style.transform = 'translate(-50%, -50%)';
        ability.dataset.abilityType = abilityType;

        // สร้าง icon ตามประเภท (ขนาดคงที่ 48px เหมือน agent)
        if (abilityType === 'spike') {
            ability.innerHTML = '<img src="../img/strategy_icon/spike.png" alt="Spike" style="width: 50%; height: 50%; object-fit: contain;">';
        } else if (abilityType === 'pin') {
            ability.innerHTML = '<i class="fas fa-map-pin" style="font-size: 36px; color: #ff4444;"></i>';
        } else if (abilityType === 'smoke') {
            ability.innerHTML = '<img src="../img/strategy_icon/smoke.png" alt="Smoke" style="width: 100%; height: 100%; object-fit: contain;">';
        } else if (abilityType === 'molly') {
            ability.innerHTML = '<img src="../img/strategy_icon/molly.png" alt="Molly" style="width: 100%; height: 100%; object-fit: contain;">';
        }

        // เพิ่มความสามารถในการลากย้าย
        ability.draggable = true;
        ability.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('abilityId', ability.dataset.abilityId);
        });

        ability.addEventListener('dragend', (e) => {
            const rect = mapCanvas.getBoundingClientRect();
            const newX = e.clientX - rect.left;
            const newY = e.clientY - rect.top;
            if (newX > 0 && newY > 0 && newX < rect.width && newY < rect.height) {
                ability.style.left = newX + 'px';
                ability.style.top = newY + 'px';
            }
        });

        // Right-click เพื่อลบ
        ability.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            if (confirm('Delete this ability?')) {
                ability.remove();
                abilities = abilities.filter(a => a !== ability);
            }
        });

        ability.dataset.abilityId = 'ability_' + Date.now() + '_' + Math.random();
        abilities.push(ability);
        mapCanvas.appendChild(ability);
    }

    function selectMapDropdown(mapSrc, mapName) {
        selectedMap = mapSrc;
        const mapCanvas = document.getElementById('mapCanvas');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        document.getElementById('selectedMapImg').src = "<?= $mapButtonDir ?>/" + mapName + ".png";

        let mainImg = mapCanvas.querySelector('#mainMapImg');
        if (mainImg) {
            mainImg.src = mapSrc;
        } else {
            const img = document.createElement('img');
            img.id = 'mainMapImg';
            img.src = mapSrc;
            img.alt = 'Map';
            mapCanvas.appendChild(img);
        }
        document.getElementById('mapList').classList.remove('active');
    }

    function clearMap() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // ลบ abilities ทั้งหมดด้วย
        abilities.forEach(ability => ability.remove());
        abilities = [];
    }

    window.addEventListener('resize', () => {
        const container = document.getElementById('mapCanvas');
        canvas.width = container.clientWidth;
        canvas.height = container.clientHeight;
    });
    </script>
</body>

</html>