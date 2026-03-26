<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'create';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? 'league');
    $season = trim($_POST['season'] ?? '');

    if (!in_array($type, ['league', 'tournament'], true)) {
        set_flash('error', 'Invalid competition type.');
        header('Location: ' . app_url('/admin/competitions.php'));
        exit;
    }

    try {
        if ($mode === 'delete' && $id) {
            $stmt = $pdo->prepare('DELETE FROM competitions WHERE id = :id');
            $stmt->execute(['id' => $id]);
            set_flash('success', 'Competition deleted.');
        } elseif ($name !== '') {
            if ($mode === 'update' && $id) {
                $stmt = $pdo->prepare('UPDATE competitions SET name = :name, type = :type, season = :season WHERE id = :id');
                $stmt->execute(['name' => $name, 'type' => $type, 'season' => $season === '' ? null : $season, 'id' => $id]);
                set_flash('success', 'Competition updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO competitions (name, type, season, created_at) VALUES (:name, :type, :season, NOW())');
                $stmt->execute(['name' => $name, 'type' => $type, 'season' => $season === '' ? null : $season]);
                set_flash('success', 'Competition created.');
            }
        }
    } catch (PDOException $e) {
        set_flash('error', 'Could not save competition.');
    }

    header('Location: ' . app_url('/admin/competitions.php'));
    exit;
}

$items = $pdo->query('SELECT id, name, type, season FROM competitions ORDER BY id DESC')->fetchAll();

$pageTitle = 'Admin Competitions';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Admin: Leagues / Tournaments</h1>

<form method="post" class="row g-3 mb-4">
    <input type="hidden" name="mode" value="create">
    <div class="col-md-4"><input class="form-control" name="name" placeholder="Competition Name" required></div>
    <div class="col-md-3"><select class="form-select" name="type"><option value="league">League</option><option value="tournament">Tournament</option></select></div>
    <div class="col-md-3"><input class="form-control" name="season" placeholder="Season (optional)"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Create</button></div>
</form>

<?php foreach ($items as $c): ?>
    <form method="post" class="row g-2 align-items-center mb-2 border rounded p-2">
        <input type="hidden" name="id" value="<?= e((string) $c['id']) ?>">
        <input type="hidden" name="mode" value="update">
        <div class="col-md-4"><input class="form-control" name="name" value="<?= e($c['name']) ?>" required></div>
        <div class="col-md-3">
            <select class="form-select" name="type">
                <option value="league" <?= $c['type'] === 'league' ? 'selected' : '' ?>>League</option>
                <option value="tournament" <?= $c['type'] === 'tournament' ? 'selected' : '' ?>>Tournament</option>
            </select>
        </div>
        <div class="col-md-3"><input class="form-control" name="season" value="<?= e((string) ($c['season'] ?? '')) ?>"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100" type="submit">Save</button></div>
        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="submit" name="mode" value="delete" onclick="return confirm('Delete competition?');">Del</button></div>
    </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
