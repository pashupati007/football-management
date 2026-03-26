<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_coach_or_admin();

$pdo = db();
$role = current_role();
$coachId = $role === 'coach' ? current_coach_id($pdo) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playerId = filter_input(INPUT_POST, 'player_id', FILTER_VALIDATE_INT);
    $availability = trim($_POST['availability'] ?? 'available');

    if ($playerId === false || $playerId === null || $playerId < 1 || !in_array($availability, ['available', 'injured', 'suspended'], true)) {
        set_flash('error', 'Invalid availability update request.');
        header('Location: ' . app_url('/coach/availability.php'));
        exit;
    }

    if ($role === 'coach') {
        $update = $pdo->prepare('UPDATE players SET availability = :availability WHERE id = :id AND coach_id = :coach_id');
        $update->execute(['availability' => $availability, 'id' => $playerId, 'coach_id' => $coachId ?? 0]);
    } else {
        $update = $pdo->prepare('UPDATE players SET availability = :availability WHERE id = :id');
        $update->execute(['availability' => $availability, 'id' => $playerId]);
    }

    set_flash('success', 'Player availability updated.');
    header('Location: ' . app_url('/coach/availability.php'));
    exit;
}

if ($role === 'coach') {
    $stmt = $pdo->prepare('SELECT id, name, position, availability FROM players WHERE coach_id = :coach_id ORDER BY name ASC');
    $stmt->execute(['coach_id' => $coachId ?? 0]);
} else {
    $stmt = $pdo->query('SELECT id, name, position, availability FROM players ORDER BY name ASC');
}
$players = $stmt->fetchAll();

$pageTitle = 'Player Availability';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Update Player Availability</h1>

<?php if ($players === []): ?>
    <div class="alert alert-info">No players available for this coach.</div>
<?php else: ?>
    <?php foreach ($players as $player): ?>
        <form method="post" class="row g-2 align-items-center mb-2 border rounded p-2">
            <input type="hidden" name="player_id" value="<?= e((string) $player['id']) ?>">
            <div class="col-md-4"><strong><?= e($player['name']) ?></strong> <span class="text-muted">(<?= e($player['position']) ?>)</span></div>
            <div class="col-md-4">
                <select name="availability" class="form-select">
                    <option value="available" <?= $player['availability'] === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="injured" <?= $player['availability'] === 'injured' ? 'selected' : '' ?>>Injured</option>
                    <option value="suspended" <?= $player['availability'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Update</button></div>
        </form>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
