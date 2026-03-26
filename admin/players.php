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
    $position = trim($_POST['position'] ?? '');
    $matches = filter_input(INPUT_POST, 'matches_played', FILTER_VALIDATE_INT);
    $goals = filter_input(INPUT_POST, 'goals', FILTER_VALIDATE_INT);
    $assists = filter_input(INPUT_POST, 'assists', FILTER_VALIDATE_INT);

    try {
        if ($mode === 'delete' && $id) {
            $stmt = $pdo->prepare('DELETE FROM players WHERE id = :id');
            $stmt->execute(['id' => $id]);
            set_flash('success', 'Player deleted.');
        } elseif ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false && $position !== '') {
            if ($mode === 'update' && $id) {
                $stmt = $pdo->prepare(
                    'UPDATE players
                     SET name = :name, email = :email, position = :position,
                         matches_played = :matches_played, goals = :goals, assists = :assists
                     WHERE id = :id'
                );
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'position' => $position,
                    'matches_played' => max(0, (int) $matches),
                    'goals' => max(0, (int) $goals),
                    'assists' => max(0, (int) $assists),
                    'id' => $id,
                ]);
                set_flash('success', 'Player updated.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO players (name, email, position, matches_played, goals, assists, created_at)
                     VALUES (:name, :email, :position, :matches_played, :goals, :assists, NOW())'
                );
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'position' => $position,
                    'matches_played' => max(0, (int) $matches),
                    'goals' => max(0, (int) $goals),
                    'assists' => max(0, (int) $assists),
                ]);
                set_flash('success', 'Player added.');
            }
        } else {
            set_flash('error', 'Provide valid player details.');
        }
    } catch (PDOException $e) {
        set_flash('error', 'Could not save player. Email/jersey may already exist.');
    }

    header('Location: ' . app_url('/admin/players.php'));
    exit;
}

$players = $pdo->query('SELECT id, name, email, position, matches_played, goals, assists FROM players ORDER BY name ASC')->fetchAll();

$pageTitle = 'Admin Players';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Admin: Manage Players</h1>

<div class="card mb-4"><div class="card-body">
    <form method="post" class="row g-3">
        <input type="hidden" name="mode" value="create">
        <div class="col-md-3"><input class="form-control" name="name" placeholder="Name" required></div>
        <div class="col-md-3"><input class="form-control" name="email" placeholder="Email" type="email" required></div>
        <div class="col-md-2"><input class="form-control" name="position" placeholder="Position" required></div>
        <div class="col-md-1"><input class="form-control" name="matches_played" type="number" min="0" value="0"></div>
        <div class="col-md-1"><input class="form-control" name="goals" type="number" min="0" value="0"></div>
        <div class="col-md-1"><input class="form-control" name="assists" type="number" min="0" value="0"></div>
        <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">Add</button></div>
    </form>
</div></div>

<?php foreach ($players as $player): ?>
    <form method="post" class="row g-2 align-items-center mb-2 border rounded p-2">
        <input type="hidden" name="id" value="<?= e((string) $player['id']) ?>">
        <input type="hidden" name="mode" value="update">
        <div class="col-md-2"><input class="form-control" name="name" value="<?= e($player['name']) ?>" required></div>
        <div class="col-md-2"><input class="form-control" name="email" type="email" value="<?= e($player['email']) ?>" required></div>
        <div class="col-md-2"><input class="form-control" name="position" value="<?= e($player['position']) ?>" required></div>
        <div class="col-md-1"><input class="form-control" name="matches_played" type="number" min="0" value="<?= e((string) $player['matches_played']) ?>"></div>
        <div class="col-md-1"><input class="form-control" name="goals" type="number" min="0" value="<?= e((string) $player['goals']) ?>"></div>
        <div class="col-md-1"><input class="form-control" name="assists" type="number" min="0" value="<?= e((string) $player['assists']) ?>"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100" type="submit">Save</button></div>
        <div class="col-md-2">
            <button formaction="<?= e(app_url('/admin/players.php')) ?>" formmethod="post" name="mode" value="delete" class="btn btn-outline-danger w-100" onclick="return confirm('Delete player?');">Delete</button>
        </div>
    </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
