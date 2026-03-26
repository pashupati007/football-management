<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

$pdo = db();
$stmt = $pdo->query(
    'SELECT m.id, m.match_date, m.status, m.home_score, m.away_score,
            ht.name AS home_team, at.name AS away_team, c.name AS competition_name
     FROM matches m
     INNER JOIN teams ht ON ht.id = m.home_team_id
     INNER JOIN teams at ON at.id = m.away_team_id
     LEFT JOIN competitions c ON c.id = m.competition_id
     ORDER BY m.match_date ASC, m.id DESC'
);
$matches = $stmt->fetchAll();

$pageTitle = 'Matches';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Matches</h1>

<?php if ($matches === []): ?>
    <div class="alert alert-info">No matches scheduled yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle js-paginated-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Competition</th>
                    <th>Fixture</th>
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
                        <td><?= $match['status'] === 'completed' ? e((string) $match['home_score'] . ' - ' . (string) $match['away_score']) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
