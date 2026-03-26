<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_coach_or_admin();

$pdo = db();
$role = current_role();
$coachId = $role === 'coach' ? current_coach_id($pdo) : null;

if ($role === 'coach') {
    $stmt = $pdo->prepare(
        'SELECT t.id, t.name,
                COUNT(p.id) AS squad_size,
                COALESCE(SUM(p.goals), 0) AS total_goals,
                COALESCE(SUM(p.assists), 0) AS total_assists,
                COALESCE(SUM(p.matches_played), 0) AS total_matches
         FROM teams t
         LEFT JOIN players p ON p.team_id = t.id
         WHERE t.manager_coach_id = :coach_id
         GROUP BY t.id, t.name
         ORDER BY t.name ASC'
    );
    $stmt->execute(['coach_id' => $coachId ?? 0]);
} else {
    $stmt = $pdo->query(
        'SELECT t.id, t.name,
                COUNT(p.id) AS squad_size,
                COALESCE(SUM(p.goals), 0) AS total_goals,
                COALESCE(SUM(p.assists), 0) AS total_assists,
                COALESCE(SUM(p.matches_played), 0) AS total_matches
         FROM teams t
         LEFT JOIN players p ON p.team_id = t.id
         GROUP BY t.id, t.name
         ORDER BY t.name ASC'
    );
}
$teamStats = $stmt->fetchAll();

$pageTitle = 'Team Stats';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Team Stats</h1>

<div class="table-responsive">
    <table class="table table-striped align-middle js-paginated-table">
        <thead>
            <tr>
                <th>Team</th>
                <th>Squad</th>
                <th>⚔️ Matches</th>
                <th>⚽ Goals</th>
                <th>👥 Assists</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teamStats as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e((string) $row['squad_size']) ?></td>
                    <td><?= e((string) $row['total_matches']) ?></td>
                    <td><?= e((string) $row['total_goals']) ?></td>
                    <td><?= e((string) $row['total_assists']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
