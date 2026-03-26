<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_role(['admin']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'create';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $experienceYears = filter_input(INPUT_POST, 'experience_years', FILTER_VALIDATE_INT);

    try {
        if ($mode === 'delete' && $id) {
            $stmt = $pdo->prepare('DELETE FROM coaches WHERE id = :id');
            $stmt->execute(['id' => $id]);
            set_flash('success', 'Coach deleted.');
        } elseif ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            if ($mode === 'update' && $id) {
                $stmt = $pdo->prepare('UPDATE coaches SET name = :name, email = :email, experience_years = :experience_years WHERE id = :id');
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'experience_years' => max(0, (int) $experienceYears),
                    'id' => $id,
                ]);
                set_flash('success', 'Coach updated.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO coaches (name, email, experience_years, created_at) VALUES (:name, :email, :experience_years, NOW())');
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'experience_years' => max(0, (int) $experienceYears),
                ]);
                set_flash('success', 'Coach added.');
            }
        } else {
            set_flash('error', 'Provide valid coach details.');
        }
    } catch (PDOException $e) {
        set_flash('error', 'Could not save coach. Email may already exist.');
    }

    header('Location: ' . app_url('/admin/coaches.php'));
    exit;
}

$coaches = $pdo->query('SELECT id, name, email, experience_years FROM coaches ORDER BY name ASC')->fetchAll();

$pageTitle = 'Admin Coaches';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Admin: Manage Coaches</h1>

<div class="card mb-4"><div class="card-body">
    <form method="post" class="row g-3">
        <input type="hidden" name="mode" value="create">
        <div class="col-md-4"><input class="form-control" name="name" placeholder="Name" required></div>
        <div class="col-md-4"><input class="form-control" name="email" placeholder="Email" type="email" required></div>
        <div class="col-md-2"><input class="form-control" name="experience_years" placeholder="Experience" type="number" min="0" value="0"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Add</button></div>
    </form>
</div></div>

<?php foreach ($coaches as $coach): ?>
    <form method="post" class="row g-2 align-items-center mb-2 border rounded p-2">
        <input type="hidden" name="id" value="<?= e((string) $coach['id']) ?>">
        <input type="hidden" name="mode" value="update">
        <div class="col-md-4"><input class="form-control" name="name" value="<?= e($coach['name']) ?>" required></div>
        <div class="col-md-4"><input class="form-control" name="email" type="email" value="<?= e($coach['email']) ?>" required></div>
        <div class="col-md-2"><input class="form-control" name="experience_years" type="number" min="0" value="<?= e((string) $coach['experience_years']) ?>"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100" type="submit">Save</button></div>
        <div class="col-md-1">
            <button formaction="<?= e(app_url('/admin/coaches.php')) ?>" formmethod="post" name="mode" value="delete" class="btn btn-outline-danger w-100" onclick="return confirm('Delete coach?');">Delete</button>
        </div>
    </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
