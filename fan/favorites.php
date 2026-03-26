<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_role(['public']);

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teamId = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
    $mode = $_POST['mode'] ?? 'add';

    if ($teamId !== false && $teamId !== null && $teamId > 0) {
        if ($mode === 'remove') {
            $stmt = $pdo->prepare('DELETE FROM favorite_teams WHERE user_id = :user_id AND team_id = :team_id');
            $stmt->execute(['user_id' => $user['id'], 'team_id' => $teamId]);
            set_flash('success', 'Favorite removed.');
        } else {
            $stmt = $pdo->prepare('INSERT IGNORE INTO favorite_teams (user_id, team_id, created_at) VALUES (:user_id, :team_id, NOW())');
            $stmt->execute(['user_id' => $user['id'], 'team_id' => $teamId]);
            set_flash('success', 'Favorite added.');
        }
    }

    header('Location: ' . app_url('/fan/favorites.php'));
    exit;
}

$teams = $pdo->query('SELECT id, name FROM teams WHERE approval_status = "approved" ORDER BY name ASC')->fetchAll();

$favStmt = $pdo->prepare(
    'SELECT t.id, t.name
     FROM favorite_teams f
     INNER JOIN teams t ON t.id = f.team_id
     WHERE f.user_id = :user_id
     ORDER BY t.name ASC'
);
$favStmt->execute(['user_id' => $user['id']]);
$favorites = $favStmt->fetchAll();
$favoriteIds = array_map(static fn(array $row): int => (int) $row['id'], $favorites);

$pageTitle = 'Favorite Teams';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Follow Favorite Teams</h1>

<div class="table-responsive">
    <table class="table table-striped align-middle js-paginated-table">
        <thead><tr><th>Team</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach ($teams as $team): ?>
                <tr>
                    <td><?= e($team['name']) ?></td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="team_id" value="<?= e((string) $team['id']) ?>">
                            <?php if (in_array((int) $team['id'], $favoriteIds, true)): ?>
                                <input type="hidden" name="mode" value="remove">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Unfollow</button>
                            <?php else: ?>
                                <input type="hidden" name="mode" value="add">
                                <button class="btn btn-sm btn-outline-primary" type="submit">Follow</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
