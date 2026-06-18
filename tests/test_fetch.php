<?php
// Prevent blocking when including files that protect direct access
if (!defined('ACCESS')) define('ACCESS', true);

require_once __DIR__ . '/../scripts/rank_cache.php';

// Edit riot_id / region as needed from DB
$riot_id = 'zhepS#toey';
$region = 'ap';
$res = fetch_player_rank_cached(152, $riot_id, $region, 1);
var_export($res);
echo PHP_EOL;