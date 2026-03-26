<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

$pdo = db();
$teams = $pdo->query('SELECT id, name FROM teams WHERE approval_status = "approved" ORDER BY name ASC')->fetchAll();

$rows = [];
foreach ($teams as $team) {
    $id = (int) $team['id'];
    $playedStmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM matches WHERE status = "completed" AND (home_team_id = :home_id OR away_team_id = :away_id)');
    $playedStmt->execute([
        'home_id' => $id,
        'away_id' => $id,
    ]);
    $played = (int) ($playedStmt->fetch()['cnt'] ?? 0);

    $winStmt = $pdo->prepare(
        'SELECT COUNT(*) AS cnt
         FROM matches
         WHERE status = "completed"
                     AND ((home_team_id = :home_id AND home_score > away_score) OR (away_team_id = :away_id AND away_score > home_score))'
    );
        $winStmt->execute([
                'home_id' => $id,
                'away_id' => $id,
        ]);
    $wins = (int) ($winStmt->fetch()['cnt'] ?? 0);

    $drawStmt = $pdo->prepare(
        'SELECT COUNT(*) AS cnt
         FROM matches
         WHERE status = "completed"
                     AND (home_team_id = :home_id OR away_team_id = :away_id)
           AND home_score = away_score'
    );
        $drawStmt->execute([
                'home_id' => $id,
                'away_id' => $id,
        ]);
    $draws = (int) ($drawStmt->fetch()['cnt'] ?? 0);

    $losses = max(0, $played - $wins - $draws);
    $points = ($wins * 3) + $draws;

    $rows[] = [
        'team' => $team['name'],
        'played' => $played,
        'wins' => $wins,
        'draws' => $draws,
        'losses' => $losses,
        'points' => $points,
    ];
}

usort($rows, static function (array $a, array $b): int {
    return $b['points'] <=> $a['points'] ?: $b['wins'] <=> $a['wins'];
});

$pageTitle = 'Standings';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Standings</h1>

<div class="table-responsive">
    <table class="table table-striped align-middle js-paginated-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Team</th>
                <th>P</th>
                <th>W</th>
                <th>D</th>
                <th>L</th>
                <th>Pts</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $idx => $row): ?>
                <tr>
                    <td><?= e((string) ($idx + 1)) ?></td>
                    <td><?= e($row['team']) ?></td>
                    <td><?= e((string) $row['played']) ?></td>
                    <td><?= e((string) $row['wins']) ?></td>
                    <td><?= e((string) $row['draws']) ?></td>
                    <td><?= e((string) $row['losses']) ?></td>
                    <td><strong><?= e((string) $row['points']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
