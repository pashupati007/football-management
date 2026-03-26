<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

$pdo = db();
$role = current_role();

if ($role === 'public') {
    $stmt = $pdo->query(
        'SELECT c.id, c.name, c.email, c.experience_years, COUNT(p.id) AS player_count
         FROM coaches c
         LEFT JOIN players p ON p.coach_id = c.id AND p.approval_status = "approved"
         GROUP BY c.id, c.name, c.email, c.experience_years
         HAVING player_count > 0
         ORDER BY c.name ASC'
    );
} else {
    $stmt = $pdo->query(
        'SELECT c.id, c.name, c.email, c.experience_years, COUNT(p.id) AS player_count
         FROM coaches c
         LEFT JOIN players p ON p.coach_id = c.id
         GROUP BY c.id, c.name, c.email, c.experience_years
         ORDER BY c.name ASC'
    );
}

$coaches = $stmt->fetchAll();

$pageTitle = 'Coaches';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Coaches</h1>

<?php if ($coaches === []): ?>
    <div class="alert alert-info">No coaches added yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle js-paginated-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Experience (Years)</th>
                <th>Players Assigned</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($coaches as $coach): ?>
                <tr>
                    <td><?= e($coach['name']) ?></td>
                    <td><?= e($coach['email']) ?></td>
                    <td><?= e((string) $coach['experience_years']) ?></td>
                    <td><?= e((string) $coach['player_count']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
