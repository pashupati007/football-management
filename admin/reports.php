<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_admin();

$pdo = db();

$totals = [
    'users' => (int) ($pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'] ?? 0),
    'players' => (int) ($pdo->query('SELECT COUNT(*) AS c FROM players')->fetch()['c'] ?? 0),
    'coaches' => (int) ($pdo->query('SELECT COUNT(*) AS c FROM coaches')->fetch()['c'] ?? 0),
    'teams' => (int) ($pdo->query('SELECT COUNT(*) AS c FROM teams')->fetch()['c'] ?? 0),
    'matches' => (int) ($pdo->query('SELECT COUNT(*) AS c FROM matches')->fetch()['c'] ?? 0),
];

$topScorers = $pdo->query('SELECT name, goals, assists FROM players ORDER BY goals DESC, assists DESC LIMIT 10')->fetchAll();
$recentMatches = $pdo->query('SELECT match_date, status, home_score, away_score FROM matches ORDER BY match_date DESC LIMIT 10')->fetchAll();

$pageTitle = 'Admin Reports';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Admin: Reports & Stats</h1>

<div class="row g-3 mb-4">
    <?php foreach ($totals as $label => $count): ?>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small"><?= e(ucfirst($label)) ?></div>
                    <div class="h4 mb-0"><?= e((string) $count) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<h2 class="h5">Top Scorers</h2>
<div class="table-responsive mb-4">
    <table class="table table-striped align-middle js-paginated-table">
        <thead><tr><th>Name</th><th>⚽ Goals</th><th>👥 Assists</th></tr></thead>
        <tbody>
            <?php foreach ($topScorers as $p): ?>
                <tr><td><?= e($p['name']) ?></td><td><?= e((string) $p['goals']) ?></td><td><?= e((string) $p['assists']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2 class="h5">Recent Matches</h2>
<div class="table-responsive">
    <table class="table table-striped align-middle js-paginated-table">
        <thead><tr><th>Date</th><th>Status</th><th>Result</th></tr></thead>
        <tbody>
            <?php foreach ($recentMatches as $m): ?>
                <tr>
                    <td><?= e(format_datetime($m['match_date'])) ?></td>
                    <td><?= e($m['status']) ?></td>
                    <td><?= $m['status'] === 'completed' ? e((string) $m['home_score'] . ' - ' . (string) $m['away_score']) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
