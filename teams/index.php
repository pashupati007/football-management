<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

$pdo = db();
$role = current_role();

if ($role === 'public') {
    $stmt = $pdo->query(
        'SELECT t.id, t.name, t.home_ground, COUNT(p.id) AS player_count
         FROM teams t
         LEFT JOIN players p ON p.team_id = t.id
         WHERE t.approval_status = "approved"
         GROUP BY t.id, t.name, t.home_ground
         ORDER BY t.name ASC'
    );
} else {
    $stmt = $pdo->query(
        'SELECT t.id, t.name, t.home_ground, COUNT(p.id) AS player_count
         FROM teams t
         LEFT JOIN players p ON p.team_id = t.id
         GROUP BY t.id, t.name, t.home_ground
         ORDER BY t.name ASC'
    );
}

$teams = $stmt->fetchAll();

$pageTitle = 'Teams';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Teams</h1>

<?php if ($teams === []): ?>
    <div class="alert alert-info">No teams added yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle js-paginated-table">
            <thead>
            <tr>
                <th>Team</th>
                <th>Home Ground</th>
                <th>Players</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($teams as $team): ?>
                <tr>
                    <td><?= e($team['name']) ?></td>
                    <td><?= e($team['home_ground'] ?? '-') ?></td>
                    <td><?= e((string) $team['player_count']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
