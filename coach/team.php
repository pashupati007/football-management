<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_coach_or_admin();

$pdo = db();
$role = current_role();
$coachId = $role === 'coach' ? current_coach_id($pdo) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'create';
    $teamId = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $homeGround = trim($_POST['home_ground'] ?? '');

    if ($mode === 'create') {
        if ($name === '') {
            set_flash('error', 'Team name is required.');
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO teams (name, home_ground, manager_coach_id, approval_status, created_at)
                 VALUES (:name, :home_ground, :manager_coach_id, :approval_status, NOW())'
            );
            $insert->execute([
                'name' => $name,
                'home_ground' => $homeGround === '' ? null : $homeGround,
                'manager_coach_id' => $role === 'coach' ? $coachId : null,
                'approval_status' => $role === 'coach' ? 'pending' : 'approved',
            ]);
            set_flash('success', 'Team created.');
        }
    } elseif ($mode === 'delete' && $teamId !== false && $teamId !== null) {
        if ($role === 'coach') {
            $delete = $pdo->prepare('DELETE FROM teams WHERE id = :id AND manager_coach_id = :coach_id');
            $delete->execute(['id' => $teamId, 'coach_id' => $coachId]);
        } else {
            $delete = $pdo->prepare('DELETE FROM teams WHERE id = :id');
            $delete->execute(['id' => $teamId]);
        }
        set_flash('success', 'Team deleted if permitted.');
    }

    header('Location: ' . app_url('/coach/team.php'));
    exit;
}

if ($role === 'coach') {
    $stmt = $pdo->prepare('SELECT id, name, home_ground, approval_status FROM teams WHERE manager_coach_id = :coach_id ORDER BY name ASC');
    $stmt->execute(['coach_id' => $coachId ?? 0]);
} else {
    $stmt = $pdo->query('SELECT id, name, home_ground, approval_status FROM teams ORDER BY name ASC');
}
$teams = $stmt->fetchAll();

$pageTitle = 'Manage Team';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Manage Team</h1>

<?php if ($role === 'coach' && $coachId === null): ?>
    <div class="alert alert-warning">Your account is not linked to a coach record yet.</div>
<?php else: ?>
    <div class="card mb-4"><div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="mode" value="create">
            <div class="col-md-5"><input class="form-control" name="name" placeholder="Team Name" required></div>
            <div class="col-md-5"><input class="form-control" name="home_ground" placeholder="Home Ground"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Create</button></div>
        </form>
    </div></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped align-middle js-paginated-table">
        <thead>
            <tr>
                <th>Team</th>
                <th>Home Ground</th>
                <th>Approval</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teams as $team): ?>
                <tr>
                    <td><?= e($team['name']) ?></td>
                    <td><?= e((string) ($team['home_ground'] ?? '-')) ?></td>
                    <td><?= e($team['approval_status']) ?></td>
                    <td>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this team?');">
                            <input type="hidden" name="mode" value="delete">
                            <input type="hidden" name="team_id" value="<?= e((string) $team['id']) ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
