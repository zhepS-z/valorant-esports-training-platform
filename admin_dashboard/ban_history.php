<?php
define('ACCESS', true);
include '../utils/db.php';
include 'admin_auth.php';
header('Content-Type: text/html; charset=utf-8');

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id <= 0) {
    echo '<div class="text-muted">Invalid user</div>';
    exit;
}

$sql = "SELECT bh.*, a.first_name AS admin_first, a.last_name AS admin_last
        FROM ban_history bh
        LEFT JOIN users a ON a.user_id = bh.banned_by
        WHERE bh.user_id = $user_id
        ORDER BY bh.created_at DESC
        LIMIT 200";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    echo '<div class="text-muted">No ban history.</div>';
    exit;
}

echo '<div class="table-responsive"><table class="table table-sm">';
echo '<thead><tr><th>When</th><th>From</th><th>Until</th><th>By</th><th>Reason</th></tr></thead><tbody>';
while ($row = $result->fetch_assoc()) {
    $when = htmlspecialchars($row['created_at']);
    $from = htmlspecialchars($row['ban_from']);
    $until = $row['ban_until'] === '9999-12-31 23:59:59' ? 'Permanent' : htmlspecialchars($row['ban_until']);
    $by = $row['admin_first'] ? htmlspecialchars($row['admin_first'].' '.$row['admin_last']) : ($row['banned_by'] ? 'Admin #'.intval($row['banned_by']) : 'System');
    $reason = $row['reason'] ? htmlspecialchars($row['reason']) : '-';
    echo "<tr><td>{$when}</td><td>{$from}</td><td>{$until}</td><td>{$by}</td><td>{$reason}</td></tr>";
}
echo '</tbody></table></div>';