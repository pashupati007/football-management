<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'create';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'public');

    if (!in_array($role, ['public', 'player', 'coach', 'admin'], true)) {
        set_flash('error', 'Invalid role.');
        header('Location: ' . app_url('/admin/users.php'));
        exit;
    }

    try {
        if ($mode === 'delete' && $id !== false && $id !== null) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);
            set_flash('success', 'User deleted.');
        } elseif ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            if ($mode === 'update' && $id !== false && $id !== null) {
                $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id');
                $stmt->execute(['name' => $name, 'email' => $email, 'role' => $role, 'id' => $id]);
                set_flash('success', 'User updated.');
            } else {
                $password = $_POST['password'] ?? '';
                if (strlen($password) < 8) {
                    set_flash('error', 'Password must be at least 8 characters.');
                    header('Location: ' . app_url('/admin/users.php'));
                    exit;
                }
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :password_hash, :role, NOW())');
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                ]);
                set_flash('success', 'User created.');
            }
        } else {
            set_flash('error', 'Provide valid user details.');
        }
    } catch (PDOException $e) {
        set_flash('error', 'Unable to save user. Email may already exist.');
    }

    header('Location: ' . app_url('/admin/users.php'));
    exit;
}

$users = $pdo->query('SELECT id, name, email, role FROM users ORDER BY id DESC')->fetchAll();

$pageTitle = 'Admin Users';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Admin: Manage Users</h1>

<div class="card mb-4"><div class="card-body">
    <form method="post" class="row g-3">
        <input type="hidden" name="mode" value="create">
        <div class="col-md-3"><input class="form-control" name="name" placeholder="Name" required></div>
        <div class="col-md-3"><input class="form-control" name="email" placeholder="Email" type="email" required></div>
        <div class="col-md-2">
            <select class="form-select" name="role" required>
                <option value="public">Public</option>
                <option value="player">Player</option>
                <option value="coach">Coach</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="col-md-2"><input class="form-control" name="password" type="password" placeholder="Password" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Create</button></div>
    </form>
</div></div>

<?php foreach ($users as $u): ?>
    <form method="post" class="row g-2 align-items-center mb-2 border rounded p-2">
        <input type="hidden" name="id" value="<?= e((string) $u['id']) ?>">
        <input type="hidden" name="mode" value="update">
        <div class="col-md-3"><input class="form-control" name="name" value="<?= e($u['name']) ?>" required></div>
        <div class="col-md-3"><input class="form-control" name="email" type="email" value="<?= e($u['email']) ?>" required></div>
        <div class="col-md-2">
            <select class="form-select" name="role" required>
                <?php foreach (['public', 'player', 'coach', 'admin'] as $r): ?>
                    <option value="<?= e($r) ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= e(ucfirst($r)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Save</button></div>
        <div class="col-md-2"><button class="btn btn-outline-danger w-100" type="submit" name="mode" value="delete" onclick="return confirm('Delete user?');">Delete</button></div>
    </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
