<?php
/**
 * Load Agent and Map from database
 * Used instead of hardcoding in strategy, lineups
 */

function get_agents_from_db($conn = null) {
    if (!$conn) {
        global $conn;
    }
    if (!$conn) return [];

    $agents = [];
    $res = @$conn->query("SELECT id, name, role, image_url FROM valorant_agents WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $agents[] = $row;
        }
    }
    return $agents;
}

function get_maps_from_db($conn = null) {
    if (!$conn) {
        global $conn;
    }
    if (!$conn) return [];

    $maps = [];
    $res = @$conn->query("SELECT id, name, image_filename, button_image_filename FROM valorant_maps WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $maps[] = $row;
        }
    }
    return $maps;
}

/**
 * Get all Agents grouped by role (for lineups dropdown)
 */
function get_agents_grouped_by_role($conn = null) {
    $agents = get_agents_from_db($conn);
    $grouped = [
        'Controller' => [],
        'Sentinel' => [],
        'Initiator' => [],
        'Duelist' => []
    ];
    foreach ($agents as $a) {
        $role = $a['role'] ?? 'Duelist';
        if (isset($grouped[$role])) {
            $grouped[$role][] = $a;
        }
    }
    return $grouped;
}
