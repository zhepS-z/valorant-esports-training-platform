<div class="mode-filter">
    <?php
    $modes = [
        'all' => 'All Modes',
        'competitive' => 'Competitive',
        'unrated' => 'Unrated',
        'deathmatch' => 'Deathmatch',
        'spike rush' => 'Spike Rush',
        'escalation' => 'Escalation',
        'team deathmatch' => 'Team Deathmatch',
        'replication' => 'Replication',
        'swiftplay' => 'Swiftplay',
        'premier' => 'Premier'
    ];

    foreach ($modes as $mode_key => $mode_label): ?>
        <a href="?riot_id=<?= urlencode($_GET['riot_id']) ?>&region=<?= $_GET['region'] ?>&match_count=<?= $match_count ?>&mode=<?= urlencode($mode_key) ?>"
            class="btn btn-dark <?= $selected_mode === $mode_key ? 'active' : '' ?>" onclick="showSpinner(this)">
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            <span class="btn-text"><?= $mode_label ?></span>
        </a>
    <?php endforeach; ?>
</div>