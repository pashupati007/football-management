<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

$pdo = db();
$role = current_role();

if ($role === 'public') {
    $stmt = $pdo->query(
        'SELECT p.id, p.name, p.email, p.position, p.jersey_number, p.matches_played, p.goals, p.assists,
                t.name AS team_name, c.name AS coach_name
         FROM players p
         LEFT JOIN teams t ON t.id = p.team_id
         LEFT JOIN coaches c ON c.id = p.coach_id
         WHERE p.approval_status = "approved"
         ORDER BY p.name ASC'
    );
} else {
    $stmt = $pdo->query(
        'SELECT p.id, p.name, p.email, p.position, p.jersey_number, p.matches_played, p.goals, p.assists,
                t.name AS team_name, c.name AS coach_name
         FROM players p
         LEFT JOIN teams t ON t.id = p.team_id
         LEFT JOIN coaches c ON c.id = p.coach_id
         ORDER BY p.name ASC'
    );
}

$players = $stmt->fetchAll();

$pageTitle = 'Players';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Players</h1>

<?php if ($players === []): ?>
    <div class="alert alert-info">No players added yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle js-paginated-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Position</th>
                <th>Jersey</th>
                <th>Team</th>
                <th>Coach</th>
                <th>Stats</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($players as $player): ?>
                <tr>
                    <td><?= e($player['name']) ?></td>
                    <td><?= e($player['position']) ?></td>
                    <td><?= e($player['jersey_number'] === null ? '-' : (string) $player['jersey_number']) ?></td>
                    <td><?= e($player['team_name'] ?? 'Unassigned') ?></td>
                    <td><?= e($player['coach_name'] ?? 'Unassigned') ?></td>
                    <td><?= e('⚔️ ' . $player['matches_played'] . ', ⚽ ' . $player['goals'] . ', 👥 ' . $player['assists']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
