<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_role(['admin']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'create';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $homeGround = trim($_POST['home_ground'] ?? '');

    try {
        if ($mode === 'delete' && $id) {
            $stmt = $pdo->prepare('DELETE FROM teams WHERE id = :id');
            $stmt->execute(['id' => $id]);
            set_flash('success', 'Team deleted.');
        } elseif ($name !== '') {
            if ($mode === 'update' && $id) {
                $stmt = $pdo->prepare('UPDATE teams SET name = :name, home_ground = :home_ground WHERE id = :id');
                $stmt->execute([
                    'name' => $name,
                    'home_ground' => $homeGround === '' ? null : $homeGround,
                    'id' => $id,
                ]);
                set_flash('success', 'Team updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO teams (name, home_ground, created_at) VALUES (:name, :home_ground, NOW())');
                $stmt->execute([
                    'name' => $name,
                    'home_ground' => $homeGround === '' ? null : $homeGround,
                ]);
                set_flash('success', 'Team added.');
            }
        } else {
            set_flash('error', 'Team name is required.');
        }
    } catch (PDOException $e) {
        set_flash('error', 'Could not save team. Team name may already exist.');
    }

    header('Location: ' . app_url('/admin/teams.php'));
    exit;
}

$teams = $pdo->query('SELECT id, name, home_ground FROM teams ORDER BY name ASC')->fetchAll();

$pageTitle = 'Admin Teams';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Admin: Manage Teams</h1>

<div class="card mb-4"><div class="card-body">
    <form method="post" class="row g-3">
        <input type="hidden" name="mode" value="create">
        <div class="col-md-5"><input class="form-control" name="name" placeholder="Team Name" required></div>
        <div class="col-md-5"><input class="form-control" name="home_ground" placeholder="Home Ground"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Add</button></div>
    </form>
</div></div>

<?php foreach ($teams as $team): ?>
    <form method="post" class="row g-2 align-items-center mb-2 border rounded p-2">
        <input type="hidden" name="id" value="<?= e((string) $team['id']) ?>">
        <input type="hidden" name="mode" value="update">
        <div class="col-md-5"><input class="form-control" name="name" value="<?= e($team['name']) ?>" required></div>
        <div class="col-md-4"><input class="form-control" name="home_ground" value="<?= e((string) ($team['home_ground'] ?? '')) ?>"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100" type="submit">Save</button></div>
        <div class="col-md-2">
            <button formaction="<?= e(app_url('/admin/teams.php')) ?>" formmethod="post" name="mode" value="delete" class="btn btn-outline-danger w-100" onclick="return confirm('Delete team?');">Delete</button>
        </div>
    </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
