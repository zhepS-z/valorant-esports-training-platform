<?php 
session_start();
define('ACCESS', true);
require_once '../../utils/apikey.php';
require_once '../../auth/auth_check.php';
include '../../utils/db.php';

// Check if user is a team manager
$manager_id = $_SESSION['user_id'];
$teamQuery = $conn->query("SELECT team_id FROM teams WHERE manager_id = $manager_id");
if ($teamQuery->num_rows === 0) {
    header("Location: ../../error/403.php");
    exit();
}
$team_id = $teamQuery->fetch_assoc()['team_id'];

// Get join requests for this team
$requestsQuery = $conn->query("
    SELECT r.request_id, r.status, r.created_at, 
           u.user_id, u.first_name, u.last_name, u.profile_img, u.valorant_rank, u.primary_role
    FROM team_join_requests r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.team_id = $team_id
    ORDER BY r.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Join Requests</title>
    <link href="../css/LFT_LFP.css" rel="stylesheet">
    <?php include '../../utils/link.php'; ?>
    <style>
        .request-card {
            background: linear-gradient(135deg, #01182a 0%, #0f1923 100%);
            border-radius: 8px;
            border-left: 4px solid var(--valorant-red);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .request-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 70, 85, 0.1);
        }
        
        .request-status {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-pending {
            color: #FFC107;
        }
        
        .status-accepted {
            color: #28A745;
        }
        
        .status-declined {
            color: #DC3545;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid var(--valorant-red);
            object-fit: cover;
        }
        
        .action-btn {
            min-width: 100px;
        }
        
        .request-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .role-badge {
            background-color: #2a3b4c;
            color: var(--valorant-light);
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .empty-state {
            background-color: #01182a;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: var(--valorant-red);
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-valorant"><i class="fas fa-user-plus me-2"></i>Team Join Requests</h2>
            <a href="team_management.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to Team
            </a>
        </div>
        
        <?php if ($requestsQuery->num_rows > 0): ?>
            <div class="row">
                <?php while($request = $requestsQuery->fetch_assoc()): ?>
                    <div class="col-md-6 mb-4">
                        <div class="request-card p-4">
                            <div class="d-flex align-items-start mb-3">
                                <?php
                                  if (empty($request['profile_img'])) {
                                    $avatar_req_copy = null;  // Use icon instead
                                  } else if (preg_match('#^https?://#i', $request['profile_img'])) {
                                    $avatar_req_copy = $request['profile_img'];
                                  } else {
                                    $p_req_copy = str_replace('\\', '/', $request['profile_img']);
                                    $p_req_copy = str_replace('team/', '', $p_req_copy);
                                    $p_req_copy = ltrim($p_req_copy, '/');
                                    $avatar_req_copy = '/VALPROJECT/img/' . $p_req_copy;
                                  }
                                ?>
                                <img src="<?= htmlspecialchars($avatar_req_copy) ?>" 
                                     class="user-avatar me-3" 
                                     alt="<?= htmlspecialchars($request['first_name']) ?>">
                                     
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></h5>
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge bg-dark me-2"><?= htmlspecialchars($request['valorant_rank']) ?></span>
                                                <span class="role-badge"><?= htmlspecialchars($request['primary_role']) ?></span>
                                            </div>
                                        </div>
                                        <span class="request-status status-<?= $request['status'] ?>">
                                            <?= $request['status'] ?>
                                        </span>
                                    </div>
                                    
                                    <p class="request-date mb-3">
                                        <i class="far fa-clock me-1"></i>
                                        Requested on <?= date('M j, Y g:i A', strtotime($request['created_at'])) ?>
                                    </p>
                                    
                                    <?php if ($request['status'] == 'pending'): ?>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-success action-btn accept-request" 
                                                    data-request-id="<?= $request['request_id'] ?>">
                                                <i class="fas fa-check me-1"></i> Accept
                                            </button>
                                            <button class="btn btn-danger action-btn decline-request" 
                                                    data-request-id="<?= $request['request_id'] ?>">
                                                <i class="fas fa-times me-1"></i> Decline
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3 class="text-valorant mb-3">No Join Requests Yet</h3>
                <p class="text-muted">You don't have any pending team join requests at the moment.</p>
                <a href="find_teams.php" class="btn btn-custom mt-3">
                    <i class="fas fa-search me-2"></i>Find Players to Invite
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle accept request
            document.querySelectorAll('.accept-request').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestId = this.dataset.requestId;
                    if (confirm('Are you sure you want to accept this join request?')) {
                        processRequest(requestId, 'accepted');
                    }
                });
            });
            
            // Handle decline request
            document.querySelectorAll('.decline-request').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestId = this.dataset.requestId;
                    if (confirm('Are you sure you want to decline this join request?')) {
                        processRequest(requestId, 'declined');
                    }
                });
            });
            
            function processRequest(requestId, action) {
                fetch('../api/process_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&action=${action}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Request ${action} successfully!`);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request.');
                });
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>