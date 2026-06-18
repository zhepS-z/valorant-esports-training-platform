<?php 
session_start();
define('ACCESS', true);
require_once '../utils/apikey.php';
require_once '../auth/auth_check.php';
include '../utils/db.php';
require_once '../utils/game_assets.php';

$agentsGrouped = get_agents_grouped_by_role($conn);
$mapsList = get_maps_from_db($conn);

// Fallback when database tables are empty
if (empty($mapsList)) {
    $mapsList = [
        ['name' => 'Ascent'], ['name' => 'Bind'], ['name' => 'Haven'], ['name' => 'Split'],
        ['name' => 'Icebox'], ['name' => 'Breeze'], ['name' => 'Fracture'], ['name' => 'Pearl'],
        ['name' => 'Lotus'], ['name' => 'Sunset'], ['name' => 'Abyss']
    ];
}
if (empty($agentsGrouped) || (empty($agentsGrouped['Controller']) && empty($agentsGrouped['Duelist']))) {
    $agentsGrouped = [
        'Controller' => [['name' => 'Brimstone'], ['name' => 'Viper'], ['name' => 'Omen'], ['name' => 'Astra'], ['name' => 'Harbor'], ['name' => 'Clove']],
        'Sentinel' => [['name' => 'Killjoy'], ['name' => 'Cypher'], ['name' => 'Sage'], ['name' => 'Chamber'], ['name' => 'Deadlock']],
        'Initiator' => [['name' => 'Sova'], ['name' => 'Breach'], ['name' => 'Skye'], ['name' => 'KAY/O'], ['name' => 'Fade'], ['name' => 'Gekko']],
        'Duelist' => [['name' => 'Jett'], ['name' => 'Phoenix'], ['name' => 'Reyna'], ['name' => 'Raze'], ['name' => 'Yoru'], ['name' => 'Neon'], ['name' => 'Iso']]
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = array();
    
    try {
        // Get form data
        $youtubeUrl = $_POST['youtubeUrl'];
        $map = $_POST['map'];
        $agent = $_POST['agent'];
        $skill = $_POST['skill'];
        $description = $_POST['description'];
        $userId = $_SESSION['user_id'];

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO lineups (user_id, youtube_url, map, agent, skill, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $userId, $youtubeUrl, $map, $agent, $skill, $description);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Line up uploaded successfully!';
        } else {
            throw new Exception("Error executing query");
        }

    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = 'Error uploading line up: ' . $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fetch existing lineups
$lineups = array();
$query = "SELECT l.*, u.first_name, u.last_name 
          FROM lineups l 
          JOIN users u ON l.user_id = u.user_id 
          ORDER BY l.created_at DESC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lineups[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valorant Line Up Hub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            color: #fff;
        }


        .container-lineup {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo h1 {
            font-size: 1.8em;
            background: linear-gradient(135deg, #ff4655 0%, #bd3944 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .upload-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #ff4655 0%, #bd3944 100%);
            border: none;
            border-radius: 25px;
            color: #fff;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 70, 85, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 70, 85, 0.6);
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            color: #fff;
            font-size: 0.95em;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab-btn:hover, .tab-btn.active {
            background: rgba(255, 70, 85, 0.2);
            border-color: #ff4655;
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
        }

        .video-card {
            background: #1a1a1a;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
        }

        .video-card:hover {
            transform: translateY(-5px);
        }

        .thumbnail {
            width: 100%;
            height: 190px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .thumbnail::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="30" fill="rgba(255,255,255,0.1)"/><polygon points="45,35 45,65 65,50" fill="rgba(255,255,255,0.3)"/></svg>') center/30% no-repeat;
        }

        .duration {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.8);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .video-info {
            padding: 12px;
        }

        .video-title {
            font-size: 1em;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .video-meta {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .map-badge {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db;
            border: 1px solid #3498db;
        }

        .agent-badge {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }

        .skill-badge {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
            border: 1px solid #f39c12;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9)!important;
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #1a1a1a;
            border: 2px solid rgba(255, 70, 85, 0.3);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: modalSlideIn 0.3s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .modal-header h3 {
            font-size: 1.8em;
            color: #ff4655;
        }

        .close-modal {
            background: none;
            border: none;
            color: #fff;
            font-size: 2em;
            cursor: pointer;
            transition: all 0.3s;
            line-height: 1;
            padding: 0;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-modal:hover {
            background: rgba(255, 70, 85, 0.2);
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ff4655;
            font-size: 1em;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #000000ff;
            font-size: 1em;
            transition: all 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #ff4655;
            background: rgba(255, 255, 255, 0.08);
        }

        select option {
            background: #1a1a1a;
            color: #fff;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .modal-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .cancel-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .cancel-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .submit-btn {
            background: linear-gradient(135deg, #ff4655 0%, #bd3944 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(255, 70, 85, 0.4);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 70, 85, 0.6);
        }

        .filters-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .filters-title {
            font-size: 0.95em;
            font-weight: 600;
            color: #ff4655;
            margin-bottom: 15px;
        }

        .search-box {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            margin-bottom: 15px;
            font-size: 0.95em;
        }

        .search-box::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .search-box:focus {
            outline: none;
            border-color: #ff4655;
            background: rgba(255, 255, 255, 0.08);
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .filter-select {
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 0.9em;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: #ff4655;
        }

        .filter-select option {
            background: #1a1a1a;
            color: #fff;
        }

        .clear-filters-btn {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s;
        }

        .clear-filters-btn:hover {
            background: rgba(255, 70, 85, 0.2);
            border-color: #ff4655;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .video-grid {
                grid-template-columns: 1fr;
            }

            header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .modal-content {
                padding: 25px;
            }
        }
    </style>
    <?php include '../utils/link.php'; ?>
</head>
<body>
    <div class="container-lineup">
        <header>
            <div class="logo">
                <h1><i class="fas fa-crosshairs"></i> VALORANT LINE UP HUB</h1>
            </div>
            <button class="upload-btn" onclick="openUploadModal()">
                <i class="fas fa-upload"></i> Upload Line Up
            </button>
        </header>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-title"><i class="fas fa-filter"></i> Search & Filter</div>
            
            <input type="text" class="search-box" id="searchInput" placeholder="Search for Agent, Map, Skill, Details...">
            
            <div class="filter-row">
                <select class="filter-select" id="mapFilter">
                    <option value=""><i class="fas fa-map"></i> All Maps</option>
                    <?php foreach ($mapsList as $m): ?>
                    <option value="<?= htmlspecialchars($m['name']) ?>"><?= htmlspecialchars($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select class="filter-select" id="agentFilter">
                    <option value=""><i class="fas fa-user"></i> All Agents</option>
                    <?php foreach ($agentsGrouped as $role => $agents): ?>
                    <?php if (!empty($agents)): ?>
                    <optgroup label="<?= htmlspecialchars($role) ?>">
                        <?php foreach ($agents as $a): ?>
                        <option value="<?= htmlspecialchars($a['name']) ?>"><?= htmlspecialchars($a['name']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>

                <select class="filter-select" id="skillFilter">
                    <option value=""><i class="fas fa-bolt"></i> All Skills</option>
                    <option value="Q - Ability 1">Q - Ability 1</option>
                    <option value="E - Ability 2">E - Ability 2</option>
                    <option value="C - Signature">C - Signature Ability</option>
                    <option value="X - Ultimate">X - Ultimate</option>
                </select>

                <button class="clear-filters-btn" onclick="clearFilters()"><i class="fas fa-redo"></i> Clear Filters</button>
            </div>
        </div>

        <div class="video-grid" id="videoGrid">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-film"></i></div>
                <h3>No Line Ups Yet</h3>
                <p style="margin-top: 10px;">Click the Upload button to post your first line up!</p>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-upload"></i> Upload Line Up</h3>
                <button class="close-modal" onclick="closeUploadModal()"><i class="fas fa-times"></i></button>
            </div>
            
            <form id="uploadForm">
                <div class="form-group">
                    <label><i class="fas fa-link"></i> YouTube Link</label>
                    <input type="url" id="youtubeUrl" placeholder="https://youtube.com/watch?v=..." required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map"></i> Map</label>
                    <select id="mapSelect" required>
                        <option value="">Select Map</option>
                        <?php foreach ($mapsList as $m): ?>
                        <option value="<?= htmlspecialchars($m['name']) ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Agent</label>
                    <select id="agentSelect" required>
                        <option value="">Select Agent</option>
                        <?php foreach ($agentsGrouped as $role => $agents): ?>
                        <?php if (!empty($agents)): ?>
                        <optgroup label="<?= htmlspecialchars($role) ?>">
                            <?php foreach ($agents as $a): ?>
                            <option value="<?= htmlspecialchars($a['name']) ?>"><?= htmlspecialchars($a['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-bolt"></i> Skill</label>
                    <select id="skillSelect" required>
                        <option value="">Select Skill</option>
                        <option value="Q - Ability 1">Q - Ability 1</option>
                        <option value="E - Ability 2">E - Ability 2</option>
                        <option value="C - Signature">C - Signature Ability</option>
                        <option value="X - Ultimate">X - Ultimate</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Description</label>
                    <textarea id="description" placeholder="Describe this line up..." required></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="modal-btn cancel-btn" onclick="closeUploadModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="modal-btn submit-btn">
                        <i class="fas fa-check"></i> Post
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // All lineups data
        const allLineups = <?php echo json_encode($lineups); ?>;

        // Create video card
        function createVideoCard(video) {
            const match = video.youtube_url.match(/[?&]v=([^&#]+)/);
            const videoId = match ? match[1] : null;
            const thumbnail = videoId 
                ? `https://img.youtube.com/vi/${videoId}/hqdefault.jpg` 
                : null;  // Use icon instead

            return `
                <div class="video-card" onclick="window.open('${video.youtube_url}', '_blank')">
                    <div class="thumbnail">
                        <img src="${thumbnail}" alt="${video.agent} lineup thumbnail" style="width:100%;height:100%;object-fit:cover;">
                        <div class="duration">Line Up</div>
                    </div>
                    <div class="video-info">
                        <div class="video-title">${video.agent} Line Up - ${video.map}</div>
                        <div style="color: #aaa; font-size: 0.9em; margin: 8px 0;">
                            ${video.description.substring(0, 80)}${video.description.length > 80 ? '...' : ''}
                        </div>
                        <div class="video-meta">
                            <span class="badge map-badge"><i class="fas fa-map"></i> ${video.map}</span>
                            <span class="badge agent-badge"><i class="fas fa-user"></i> ${video.agent}</span>
                            <span class="badge skill-badge"><i class="fas fa-bolt"></i> ${video.skill}</span>
                        </div>
                        <div style="color: #666; font-size: 0.8em; margin-top: 8px;">
                            Posted by ${video.first_name} ${video.last_name}
                        </div>
                    </div>
                </div>
            `;
        }

        // Display lineups
        function renderVideos(videos = allLineups) {
            const grid = document.getElementById('videoGrid');
            
            if (videos.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-film"></i></div>
                        <h3>No Line Ups Found</h3>
                        <p style="margin-top: 10px;">Try adjusting the filters or post a new line up.</p>
                    </div>
                `;
            } else {
                grid.innerHTML = videos.map(createVideoCard).join('');
            }
        }

        // Filter function
        function applyFilters() {
            const searchQuery = document.getElementById('searchInput').value.toLowerCase();
            const mapFilter = document.getElementById('mapFilter').value;
            const agentFilter = document.getElementById('agentFilter').value;
            const skillFilter = document.getElementById('skillFilter').value;

            const filtered = allLineups.filter(video => {
                const matchSearch = searchQuery === '' || 
                    video.agent.toLowerCase().includes(searchQuery) ||
                    video.map.toLowerCase().includes(searchQuery) ||
                    video.skill.toLowerCase().includes(searchQuery) ||
                    video.description.toLowerCase().includes(searchQuery) ||
                    `${video.first_name} ${video.last_name}`.toLowerCase().includes(searchQuery);

                const matchMap = mapFilter === '' || video.map === mapFilter;
                const matchAgent = agentFilter === '' || video.agent === agentFilter;
                const matchSkill = skillFilter === '' || video.skill === skillFilter;

                return matchSearch && matchMap && matchAgent && matchSkill;
            });

            renderVideos(filtered);
        }

        // Clear filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('mapFilter').value = '';
            document.getElementById('agentFilter').value = '';
            document.getElementById('skillFilter').value = '';
            renderVideos(allLineups);
        }

        // Event listeners for filters
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('mapFilter').addEventListener('change', applyFilters);
        document.getElementById('agentFilter').addEventListener('change', applyFilters);
        document.getElementById('skillFilter').addEventListener('change', applyFilters);

        // Modal functions
        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
            document.getElementById('uploadForm').reset();
        }

        document.getElementById('uploadModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUploadModal();
            }
        });

        // Submit form
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('youtubeUrl', document.getElementById('youtubeUrl').value);
            formData.append('map', document.getElementById('mapSelect').value);
            formData.append('agent', document.getElementById('agentSelect').value);
            formData.append('skill', document.getElementById('skillSelect').value);
            formData.append('description', document.getElementById('description').value);

            fetch('lineups.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Line up uploaded successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error uploading line up: ' + error);
            });

            closeUploadModal();
        });

        // Load data on page load
        renderVideos();
    </script>
</body>
</html>