<?php
// ป้องกันกรณี $paginated_matches หรือ $match_data ไม่ถูกส่งมา
$paginated_matches = $paginated_matches ?? [];
// ให้ค่า default สำหรับ match_region (มาจากตัวเรียก เช่น leaderboardplayer.php หรือ query string)
$match_region = isset($region) ? $region : (isset($_GET['region']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['region']) : 'ap');

// กรองโหมดตามค่าที่ส่งมาจากฟอร์ม (ถ้ามี)
$filtered_matches = $paginated_matches;
if (isset($_GET['mode']) && !empty($_GET['mode'])) {
    $mode_filter = strtolower($_GET['mode']);
    $filtered_matches = array_filter($paginated_matches, function ($match) use ($mode_filter) {
        $mode = strtolower($match['meta']['mode'] ?? '');
        return $mode === $mode_filter;
    });
    $filtered_matches = array_values($filtered_matches);
}
?>
<div class="container">
    <table class="table match-history-table">
        <thead>
            <tr>
                <th colspan="7" class="text-center">Match History</th>
            </tr>
            <tr>
                <th scope="col">Agent</th>
                <th scope="col">Map</th>
                <th scope="col">Mode</th>
                <th scope="col">Result</th>
                <th scope="col">Score</th>
                <th scope="col">K/D/A</th>
                <th scope="col">Ratio</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $previous_date = '';
            foreach ($filtered_matches as $match):
                $map = $match['meta']['map']['name'] ?? 'Unknown Map';
                $mode = $match['meta']['mode'] ?? 'Unknown Mode';

                // match id (รองรับทั้ง id และ matchid)
                $match_id = $match['meta']['id'] ?? $match['meta']['matchid'] ?? null;

                // เวลาเริ่มเกม และรูปแบบวันที่
                $game_start = $match['meta']['started_at'] ?? '';
                $game_date = $game_start ? date('M j Y', strtotime($game_start)) : 'Unknown Date';

                // คะแนนทีม
                $red_score = $match['teams']['red'] ?? 0;
                $blue_score = $match['teams']['blue'] ?? 0;

                // ข้อมูลผู้เล่นในแมตช์ (อาจเป็น null)
                $player_stats = $match['stats'] ?? [];
                $agent = $player_stats['character']['name'] ?? 'Unknown Agent';
                $agent_id = $player_stats['character']['id'] ?? '';
                $kills = $player_stats['kills'] ?? 0;
                $deaths = $player_stats['deaths'] ?? 0;
                $assists = $player_stats['assists'] ?? 0;
                $player_team = $player_stats['team'] ?? '';

                // ผลลัพธ์ (สำหรับโหมดทั่วไป)
                $result = 'N/A';
                $result_class = 'badge-secondary';
                $mode_lc = strtolower($mode);
                if ($mode_lc === 'deathmatch') {
                    $result = 'Deathmatch';
                    $result_class = 'badge-primary';
                } elseif ($mode_lc === 'custom' || $mode_lc === 'custom mode') {
                    // เพิ่มการรองรับ custom mode
                    $result = 'Custom Mode';
                    $result_class = 'badge-warning';
                } else {
                    if ($player_team === 'Red' || $player_team === 'Blue') {
                        $is_win = ($player_team === 'Red' && $red_score > $blue_score) || ($player_team === 'Blue' && $blue_score > $red_score);
                        $result = $is_win ? 'Victory' : 'Defeat';
                        $result_class = $is_win ? 'badge-success' : 'badge-danger';
                    } else {
                        // ถ้าไม่รู้ทีม ให้ดูจากคะแนนโดยรวม
                        if ($red_score !== $blue_score) {
                            $result = ($red_score > $blue_score) ? 'Red Win' : 'Blue Win';
                            $result_class = 'badge-info';
                        } else {
                            $result = 'Draw';
                            $result_class = 'badge-secondary';
                        }
                    }
                }

                // จัดรูปแบบคะแนนให้ทีมของผู้เล่นขึ้นก่อน
                if ($player_team === 'Red') {
                    $score_display = "{$red_score} - {$blue_score}";
                } elseif ($player_team === 'Blue') {
                    $score_display = "{$blue_score} - {$red_score}";
                } else {
                    $score_display = "{$red_score} - {$blue_score}";
                }

                // KDA ratio
                $kda_ratio = $deaths > 0 ? round(($kills + $assists) / $deaths, 2) : ($kills + $assists);

                // Agent image (fallback)
                $agent_image_url = $agent_id ? "https://media.valorant-api.com/agents/{$agent_id}/displayicon.png" : "../img/default_agent.png";

                // แสดงวันที่ใหม่เมื่อเปลี่ยนวัน (ถ้าต้องการแบ่งกลุ่มตามวัน)
                if ($game_date !== $previous_date):
                    $previous_date = $game_date;
                endif;

                // สร้างลิงก์ไปยัง match_detail (แนบ region)
                // ทำให้แมตช์แบบ deathmatch / custom ไม่คลิกได้ (ไม่เปิดรายละเอียด)
                $clickable = ($match_id !== null && !in_array($mode_lc, ['deathmatch', 'custom', 'custom mode'], true));
                $detail_url = "../leaderboard/match_detail.php?matchid=" . urlencode($match_id) . "&region=" . urlencode($match_region);
            ?>
            <tr <?php if ($clickable): ?> onclick="window.location.href='<?= htmlspecialchars($detail_url) ?>'" style="cursor:pointer;" <?php else: ?> style="background:#23272b;" <?php endif; ?>>
                <td>
                    <img src="<?= htmlspecialchars($agent_image_url) ?>" alt="<?= htmlspecialchars($agent) ?>" style="width:40px;height:40px;">
                </td>
                <td><?= htmlspecialchars($map) ?></td>
                <td><?= htmlspecialchars($mode) ?></td>
                <td><span class="badge <?= htmlspecialchars($result_class) ?>"><?= htmlspecialchars($result) ?></span></td>
                <td><?= htmlspecialchars($score_display) ?></td>
                <td><?= htmlspecialchars("{$kills}/{$deaths}/{$assists}") ?></td>
                <td><?= htmlspecialchars((string)$kda_ratio) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


