<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_player();

$pdo = db();
$user = current_user();

$teamStmt = $pdo->prepare('SELECT team_id FROM players WHERE email = :email LIMIT 1');
$teamStmt->execute(['email' => $user['email']]);
$row = $teamStmt->fetch();

$matches = [];
if ($row !== false && $row['team_id'] !== null) {
    $matchStmt = $pdo->prepare(
        'SELECT m.match_date, m.status, m.home_score, m.away_score,
                ht.name AS home_team, at.name AS away_team, c.name AS competition_name
         FROM matches m
         INNER JOIN teams ht ON ht.id = m.home_team_id
         INNER JOIN teams at ON at.id = m.away_team_id
         LEFT JOIN competitions c ON c.id = m.competition_id
         WHERE m.home_team_id = :team_id OR m.away_team_id = :team_id
         ORDER BY m.match_date ASC'
    );
    $matchStmt->execute(['team_id' => $row['team_id']]);
    $matches = $matchStmt->fetchAll();
}

$pageTitle = 'Match Schedule';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">My Match Schedule</h1>

<?php if ($matches === []): ?>
    <div class="alert alert-info">No scheduled matches for your team yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle js-paginated-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Competition</th>
                    <th>Match</th>
                    <th>Status</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $match): ?>
                    <tr>
                        <td><?= e(format_datetime($match['match_date'])) ?></td>
                        <td><?= e($match['competition_name'] ?? '-') ?></td>
                        <td><?= e($match['home_team'] . ' vs ' . $match['away_team']) ?></td>
                        <td><?= e($match['status']) ?></td>
                        <td>
                            <?= $match['status'] === 'completed' ? e((string) $match['home_score'] . ' - ' . (string) $match['away_score']) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
