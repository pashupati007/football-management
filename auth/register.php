<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

if (is_logged_in()) {
    header('Location: ' . app_url('/index.php'));
    exit;
}

$errors = [];
$name = '';
$email = '';
$role = 'public';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'public');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'A valid email is required.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!in_array($role, ['public', 'player', 'coach', 'admin'], true)) {
        $errors[] = 'Please select a valid role.';
    }

    if ($errors === []) {
        $pdo = db();
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute(['email' => $email]);

        if ($checkStmt->fetch() !== false) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if ($errors === []) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $insertStmt = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :password_hash, :role, NOW())'
            );
            $insertStmt->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
            ]);

            if ($role === 'player') {
                $playerStmt = $pdo->prepare(
                    'INSERT INTO players (name, email, position, approval_status, created_at)
                     VALUES (:name, :email, :position, :approval_status, NOW())'
                );
                $playerStmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'position' => 'Unknown',
                    'approval_status' => 'pending',
                ]);
            }

            if ($role === 'coach') {
                $coachStmt = $pdo->prepare(
                    'INSERT INTO coaches (name, email, experience_years, created_at)
                     VALUES (:name, :email, :experience_years, NOW())'
                );
                $coachStmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'experience_years' => 0,
                ]);
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Could not complete registration. Please try again.';
        }

        if ($errors !== []) {
            // Keep form state visible if transaction fails.
        } else {
            set_flash('success', 'Registration successful. Please login.');
            header('Location: ' . app_url('/auth/login.php'));
            exit;
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <h1 class="h3 mb-3">Create Account</h1>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= e($name) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="public" <?= $role === 'public' ? 'selected' : '' ?>>Public</option>
                    <option value="player" <?= $role === 'player' ? 'selected' : '' ?>>Player</option>
                    <option value="coach" <?= $role === 'coach' ? 'selected' : '' ?>>Coach</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
