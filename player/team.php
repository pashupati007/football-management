<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_player();

$pdo = db();
$user = current_user();

$stmt = $pdo->prepare(
    'SELECT p.name AS player_name, t.id AS team_id, t.name AS team_name, t.home_ground, c.name AS coach_name
     FROM players p
     LEFT JOIN teams t ON t.id = p.team_id
     LEFT JOIN coaches c ON c.id = p.coach_id
     WHERE p.email = :email
     LIMIT 1'
);
$stmt->execute(['email' => $user['email']]);
$player = $stmt->fetch();

$teammates = [];
if ($player !== false && $player['team_id'] !== null) {
    $mateStmt = $pdo->prepare('SELECT name, position, jersey_number FROM players WHERE team_id = :team_id ORDER BY name ASC');
    $mateStmt->execute(['team_id' => $player['team_id']]);
    $teammates = $mateStmt->fetchAll();
}

$pageTitle = 'My Team';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">My Team</h1>

<?php if ($player === false || $player['team_id'] === null): ?>
    <div class="alert alert-warning">You are not assigned to a team yet.</div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-1"><strong>Team:</strong> <?= e($player['team_name']) ?></p>
            <p class="mb-1"><strong>Home Ground:</strong> <?= e($player['home_ground'] ?? '-') ?></p>
            <p class="mb-0"><strong>Coach:</strong> <?= e($player['coach_name'] ?? 'Unassigned') ?></p>
        </div>
    </div>

    <h2 class="h5">Teammates</h2>
    <div class="table-responsive">
        <table class="table table-striped align-middle js-paginated-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Jersey #</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teammates as $mate): ?>
                    <tr>
                        <td><?= e($mate['name']) ?></td>
                        <td><?= e($mate['position']) ?></td>
                        <td><?= e($mate['jersey_number'] === null ? '-' : (string) $mate['jersey_number']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
