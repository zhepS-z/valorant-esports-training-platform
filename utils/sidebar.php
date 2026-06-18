<?php
// Do not call session_start() here if the main file already called it
$current_page = basename($_SERVER['PHP_SELF']);
require_once $_SERVER['DOCUMENT_ROOT'] . '/VALPROJECT/utils/db.php';  // Use absolute path

$profileImg = '';
$defaultAvatar = '/valproject/img/person.png'; // default avatar

if (!empty($_SESSION['user_id'])) {
    $userId = intval($_SESSION['user_id']);
    $sql = "SELECT profile_img FROM users WHERE user_id = $userId LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $profileImg = $row['profile_img'];
        // Remove ../ if present
        $profileImg = preg_replace('#^(\.\./)+#', '', $profileImg);
        // Check actual file (real path on server)
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/valproject/' . $profileImg;
        if ($profileImg && file_exists($filePath)) {
            $profileImg = '/valproject/' . $profileImg;
        } else {
            $profileImg = $defaultAvatar;
        }
    } else {
        $profileImg = $defaultAvatar;
    }
} else {
    $profileImg = $defaultAvatar;
}
?>


<div id="sidebar" class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark">
    <img src="/valproject/img/LOGO/logo.png" alt="Logo" height="38" id="sidebar-toggle" class="sidebar-logo-img"
        style="cursor:pointer;">
    <br><br>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto text-center">
        <li class="nav-item">
            <a href="/valproject/"
                class="nav-link <?= $current_page == 'index.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                title="Tracker">
                <i class="fa fa-home fa-lg"></i>
                <span class="sidebar-label ms-2">Tracker</span>
            </a>
        </li>
        <li>
            <a href="/valproject/team/pages/LFT.php"
                class="nav-link <?= $current_page == 'LFT.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                title="Find Teams">
                <i class="fa fa-users fa-lg"></i>
                <span class="sidebar-label ms-2">Find Teams</span>
            </a>
        </li>
        <li>
            <a href="/valproject/team/pages/LFP.php"
                class="nav-link <?= $current_page == 'LFP.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                title="Find Players">
                <i class="fa fa-user-plus fa-lg"></i>
                <span class="sidebar-label ms-2">Find Players</span>
            </a>
        </li>
        <li>
            <a href="/valproject/scrim/scrim.php"
                class="nav-link <?= $current_page == 'scrim.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                title="Scrims">
                <i class="fas fa-crosshairs fa-lg"></i>
                <span class="sidebar-label ms-2">Scrims</span>
            </a>
        </li>
        <!-- Leaderboards dropdown -->
        <li class="nav-item">
            <a href="#sidebar-leaderboard-collapse"
                class="nav-link d-flex align-items-center <?= in_array($current_page, ['leaderboard.php','leaderboardpremier.php']) ? 'active bg-danger text-white' : 'text-white' ?>"
                data-bs-toggle="collapse" role="button" aria-expanded="false"
                aria-controls="sidebar-leaderboard-collapse">
                <i class="fa fa-trophy fa-lg"></i>
                <span class="sidebar-label ms-2">Leaderboards</span>

            </a>
            <div class="collapse" id="sidebar-leaderboard-collapse">
                <ul class="list-unstyled ps-0 mb-0">
                    <li>
                        <a class="nav-link py-2 d-flex align-items-center <?= $current_page == 'leaderboard.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                            href="/valproject/leaderboard/leaderboard.php">
                            <img src="/valproject/img/radiant_logo.png" alt="Leaderboard Icon"
                                style="height: 22px; width: 22px; margin-right: 8px;">
                            <span class="sidebar-label">Ranked</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link py-2 d-flex align-items-center <?= $current_page == 'leaderboardpremier.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                            href="/valproject/leaderboard/leaderboardpremier.php">
                            <img src="/valproject/img/premier_logo.png" alt="Premier Icon"
                                style="height: 22px; width: 22px; margin-right: 8px;">
                            <span class="sidebar-label">Premier</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li>


<li class="nav-item">
    <a href="#sidebar-tactics-collapse"
        class="nav-link d-flex align-items-center <?= in_array($current_page, ['strategy.php','lineups.php']) ? 'active bg-danger text-white' : 'text-white' ?>"
        data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebar-tactics-collapse">
        <i class="fa fa-lightbulb fa-lg"></i>
        <span class="sidebar-label ms-2">Tactics</span>
    </a>
    <div class="collapse" id="sidebar-tactics-collapse">
        <ul class="list-unstyled ps-0 mb-0">
            <li>
                <a class="nav-link py-2 d-flex align-items-center <?= $current_page == 'strategy.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                    href="/valproject/strategy/strategy.php">
                    <i class="fa-solid fa-puzzle-piece me-2"></i>
                    <span class="sidebar-label">Strategy</span>
                </a>
            </li>
            <li>
                <a class="nav-link py-2 d-flex align-items-center <?= $current_page == 'lineups.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                    href="/valproject/strategy/lineups.php">
                    <i class="fa-solid fa-bullseye me-2"></i>
                    <span class="sidebar-label">Lineups</span>
                </a>
            </li>
        </ul>
    </div>
</li>

        </li>
        <?php if (!empty($_SESSION['riot_id'])): ?>
        <li>
            <a href="/valproject/career/career.php"
                class="nav-link <?= $current_page == 'career.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                title="Career">
                <i class="fa fa-id-card fa-lg"></i>
                <span class="sidebar-label ms-2">Career</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (!empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            
            
            
        <li class="nav-item">
            <a href="#sidebar-admin-collapse"
                class="nav-link d-flex align-items-center <?= in_array($current_page, ['user_table.php','team_table.php','agent_table.php','map_table.php']) ? 'active bg-danger text-white' : 'text-white' ?>"
                data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebar-admin-collapse">
                <i class="fa-solid fa-user-tie"></i>
                <span class="sidebar-label ms-2">Admin Menu</span>
            </a>
            <div class="collapse" id="sidebar-admin-collapse">
                <ul class="list-unstyled ps-0 mb-0">
                    <li>
                        <a class="nav-link py-2 d-flex align-items-center <?= $current_page == 'user_table.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                            href="/valproject/admin_dashboard/user_table.php">
                            <i class="fa fa-user-shield me-2"></i>
                            <span class="sidebar-label">Users</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link py-2 d-flex align-items-center <?= $current_page == 'agent_table.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                            href="/valproject/admin_dashboard/agent_table.php">
                            <i class="fa fa-user-secret me-2"></i>
                            <span class="sidebar-label">Agents</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link py-2 d-flex align-items-center <?= $current_page == 'map_table.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                            href="/valproject/admin_dashboard/map_table.php">
                            <i class="fa fa-map me-2"></i>
                            <span class="sidebar-label">Maps</span>
                        </a>
                    </li>
                                        <li>
                        <a class="nav-link py-2 d-flex align-items-center <?= $current_page == 'pending_user.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                            href="/valproject/admin_dashboard/pending_user.php">
                            <i class="fa-solid fa-user-clock me-2"></i>
                            <span class="sidebar-label">Pending Users</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link py-2 d-flex align-items-center <?= $current_page == 'team_table.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                            href="/valproject/admin_dashboard/team_table.php">
                            <i class="fa fa-users me-2"></i>
                            <span class="sidebar-label">Teams</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link py-2 d-flex align-items-center <?= $current_page == 'team_member.php' ? 'active bg-danger text-white' : 'text-white' ?>"
                            href="/valproject/admin_dashboard/team_member.php">
                            <i class="fa fa-user-friends me-2"></i>
                            <span class="sidebar-label">Team Members</span>
                        </a>
                    </li>

                </ul>
            </div>
        </li>
        <?php endif; ?>
    </ul>
    <hr>
    <!-- Profile accordion (replaces old dropdown) -->
    <li class="nav-item" style="list-style:none;">
        <a href="#sidebar-profile-collapse"
            class="d-flex align-items-center justify-content-center p-2 text-white text-decoration-none"
            data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebar-profile-collapse"
            id="profileDropdown">
            <img src="<?= htmlspecialchars($profileImg) ?>"
                alt="" width="32" height="32" class="rounded-circle me-2">
            <span
                class="sidebar-label"><strong><?= !empty($_SESSION['riot_id']) ? htmlspecialchars($_SESSION['riot_id']) : 'Guest' ?></strong></span>
        </a>
        <div class="collapse" id="sidebar-profile-collapse">
            <ul class="list-unstyled ps-0 mb-0">
                <?php
                // determine if current user is a team manager / team owner
                $canManageTeam = false;
                if (!empty($_SESSION['user_id'])) {
                    $uid = intval($_SESSION['user_id']);
                    // check global role
                    $uRes = $conn->query("SELECT role FROM users WHERE user_id = $uid LIMIT 1");
                    if ($uRes && $uRow = $uRes->fetch_assoc()) {
                        if (isset($uRow['role']) && $uRow['role'] === 'manager') {
                            $canManageTeam = true;
                        }
                    }
                    // check team_members role_in_team = 'Manager'
                    if (!$canManageTeam) {
                        $mRes = $conn->query("SELECT 1 FROM team_members WHERE user_id = $uid AND role_in_team = 'Manager' LIMIT 1");
                        if ($mRes && $mRes->fetch_assoc()) {
                            $canManageTeam = true;
                        }
                    }
                    // check teams.manager_id
                    if (!$canManageTeam) {
                        $tRes = $conn->query("SELECT 1 FROM teams WHERE manager_id = $uid LIMIT 1");
                        if ($tRes && $tRes->fetch_assoc()) {
                            $canManageTeam = true;
                        }
                    }
                }
                ?>
                <?php if (!empty($_SESSION['user_id'])): ?>
                <li><a class="nav-link py-2" href="/valproject/profile/profile.php"><i class="fa fa-user me-2"></i><span
                            class="sidebar-label">Profile</span></a></li>
                <li><a class="nav-link py-2" href="/valproject/team/pages/team_index.php"><i
                            class="fa fa-users me-2"></i><span class="sidebar-label">My Team</span></a></li>

                <?php if ($canManageTeam): ?>
                <?php endif; ?>

                <!-- Add Request Status button -->
                <li><a class="nav-link py-2" href="/valproject/team/api/request_status.php"><i
                            class="fa fa-clock me-2"></i><span class="sidebar-label">Request Status</span></a></li>
                <!-- ...existing code... -->
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="nav-link py-2" href="/valproject/auth/logout.php"><i
                            class="fa fa-sign-out-alt me-2"></i><span class="sidebar-label">Logout</span></a></li>
                <?php else: ?>
                <li><a class="nav-link py-2" href="/valproject/auth/login.php"><i
                            class="fa fa-sign-in-alt me-2"></i><span class="sidebar-label">Login</span></a></li>
                <li><a class="nav-link py-2" href="/valproject/auth/signup.php"><i
                            class="fa fa-user-plus me-2"></i><span class="sidebar-label">Register</span></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </li>
</div>

<script>
// Sidebar toggle logic (updated — only toggle a page wrapper instead of forcing body margin)
function setSidebarState(open) {
    var sidebar = document.getElementById('sidebar');
    var pageWrapper = document.getElementById('page-wrapper'); // new wrapper around page content
    if (!pageWrapper) {
        // fallback: if wrapper not present, don't change body margins; only toggle sidebar visual state
        if (open) {
            sidebar.classList.remove('closed');
        } else {
            sidebar.classList.add('closed');
        }
        return;
    }

    if (open) {
        sidebar.classList.remove('closed');
        pageWrapper.classList.add('with-sidebar');   // define styles in CSS
        pageWrapper.classList.remove('no-sidebar');
    } else {
        sidebar.classList.add('closed');
        pageWrapper.classList.add('no-sidebar');     // define styles in CSS
        pageWrapper.classList.remove('with-sidebar');
    }
}

// Initial state: closed (but applied to wrapper)
setSidebarState(false);

document.getElementById('sidebar-toggle').onclick = function() {
    var sidebar = document.getElementById('sidebar');
    var isClosed = sidebar.classList.contains('closed');
    setSidebarState(isClosed);
};

// Responsive: auto adjust (keeps closed by default)
function checkSidebarOnResize() {
    if (window.innerWidth <= 900) {
        setSidebarState(false);
    } else {
        setSidebarState(false);
    }
}
window.addEventListener('resize', checkSidebarOnResize);
document.addEventListener('DOMContentLoaded', checkSidebarOnResize);
</script>