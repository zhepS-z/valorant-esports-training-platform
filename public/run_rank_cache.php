<?php
// เรียกหน้าเพียงเพื่อสาธิต — ไม่มีการเช็ค key/IP
// แจ้งว่าเป็นการเรียกจากเว็บ
if (!defined('ACCESS')) define('ACCESS', true);

// โหลด helper + db
require_once __DIR__ . '/../scripts/rank_cache.php';

echo "Starting rank cache update...\n";

try {
    global $conn;
    $stmt = $conn->prepare("SELECT user_id, riot_id, region FROM users WHERE riot_id IS NOT NULL AND riot_id <> '' AND region IS NOT NULL AND region <> ''");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $uid = (int)$row['user_id'];
        $rid = $row['riot_id'];
        $reg = $row['region'];
        echo "Refreshing player: {$rid} ({$reg})\n";
        fetch_player_rank_cached($uid, $rid, $reg, defined('PLAYER_RANK_TTL') ? PLAYER_RANK_TTL : 21600);
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT team_id FROM teams");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $tid = (int)$row['team_id'];
        echo "Refreshing team: {$tid}\n";
        compute_team_average_rank_cached($tid, defined('TEAM_RANK_TTL') ? TEAM_RANK_TTL : 86400);
    }
    $stmt->close();

    echo "Rank cache update finished.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    http_response_code(500);
    exit(1);
}
exit(0);