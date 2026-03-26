<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entity = $_POST['entity'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = trim($_POST['status'] ?? 'pending');

    if ($id && in_array($status, ['pending', 'approved', 'rejected'], true)) {
        if ($entity === 'player') {
            $stmt = $pdo->prepare('UPDATE players SET approval_status = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
        }
        if ($entity === 'team') {
            $stmt = $pdo->prepare('UPDATE teams SET approval_status = :status WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
        }
        set_flash('success', 'Approval updated.');
    }

    header('Location: ' . app_url('/admin/approvals.php'));
    exit;
}

$players = $pdo->query('SELECT id, name, approval_status FROM players ORDER BY id DESC')->fetchAll();
$teams = $pdo->query('SELECT id, name, approval_status FROM teams ORDER BY id DESC')->fetchAll();

$pageTitle = 'Admin Approvals';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Admin: Approve Teams & Players</h1>

<h2 class="h5">Players</h2>
<?php foreach ($players as $p): ?>
    <form method="post" class="row g-2 align-items-center mb-2 border rounded p-2">
        <input type="hidden" name="entity" value="player">
        <input type="hidden" name="id" value="<?= e((string) $p['id']) ?>">
        <div class="col-md-6"><?= e($p['name']) ?></div>
        <div class="col-md-4">
            <select class="form-select" name="status">
                <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $p['approval_status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Save</button></div>
    </form>
<?php endforeach; ?>

<h2 class="h5 mt-4">Teams</h2>
<?php foreach ($teams as $t): ?>
    <form method="post" class="row g-2 align-items-center mb-2 border rounded p-2">
        <input type="hidden" name="entity" value="team">
        <input type="hidden" name="id" value="<?= e((string) $t['id']) ?>">
        <div class="col-md-6"><?= e($t['name']) ?></div>
        <div class="col-md-4">
            <select class="form-select" name="status">
                <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $t['approval_status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Save</button></div>
    </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
