<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_role(['player']);

$pdo = db();
$user = current_user();

$selfStmt = $pdo->prepare(
    'SELECT p.name, p.position, p.jersey_number, p.matches_played, p.goals, p.assists, t.name AS team_name
     FROM players p
     LEFT JOIN teams t ON t.id = p.team_id
     WHERE p.email = :email
     LIMIT 1'
);
$selfStmt->execute(['email' => $user['email']]);
$myStats = $selfStmt->fetch();

$topStmt = $pdo->query(
    'SELECT name, position, matches_played, goals, assists
     FROM players
     ORDER BY goals DESC, assists DESC, matches_played DESC
     LIMIT 10'
);
$topPlayers = $topStmt->fetchAll();

$pageTitle = 'Player Stats';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Player Stats</h1>

<?php if ($myStats === false): ?>
    <div class="alert alert-warning">No player profile is linked to your account email yet.</div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5">My Stats</h2>
            <p class="mb-1"><strong>Name:</strong> <?= e($myStats['name']) ?></p>
            <p class="mb-1"><strong>Position:</strong> <?= e($myStats['position']) ?></p>
            <p class="mb-1"><strong>Jersey:</strong> <?= e($myStats['jersey_number'] === null ? '-' : (string) $myStats['jersey_number']) ?></p>
            <p class="mb-1"><strong>Team:</strong> <?= e($myStats['team_name'] ?? 'Unassigned') ?></p>
            <p class="mb-0"><strong>⚔️ Matches:</strong> <?= e((string) $myStats['matches_played']) ?>,
                <strong>⚽ Goals:</strong> <?= e((string) $myStats['goals']) ?>,
                <strong>👥 Assists:</strong> <?= e((string) $myStats['assists']) ?></p>
        </div>
    </div>
<?php endif; ?>

<h2 class="h5">Top Players</h2>
<div class="table-responsive">
    <table class="table table-striped align-middle js-paginated-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Position</th>
            <th>⚔️ Matches</th>
            <th>⚽ Goals</th>
            <th>👥 Assists</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($topPlayers as $player): ?>
            <tr>
                <td><?= e($player['name']) ?></td>
                <td><?= e($player['position']) ?></td>
                <td><?= e((string) $player['matches_played']) ?></td>
                <td><?= e((string) $player['goals']) ?></td>
                <td><?= e((string) $player['assists']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
