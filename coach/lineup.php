<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_coach_or_admin();

$pdo = db();
$role = current_role();
$coachId = $role === 'coach' ? current_coach_id($pdo) : null;

if ($role === 'coach') {
    $teamStmt = $pdo->prepare('SELECT id, name FROM teams WHERE manager_coach_id = :coach_id AND approval_status = "approved" ORDER BY name ASC');
    $teamStmt->execute(['coach_id' => $coachId ?? 0]);
} else {
    $teamStmt = $pdo->query('SELECT id, name FROM teams WHERE approval_status = "approved" ORDER BY name ASC');
}
$teams = $teamStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teamId = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
    $formation = trim($_POST['formation'] ?? '4-4-2');
    $notes = trim($_POST['notes'] ?? '');
    $players = $_POST['players'] ?? [];

    if ($teamId === false || $teamId === null || $teamId < 1 || $formation === '') {
        set_flash('error', 'Invalid lineup details.');
        header('Location: ' . app_url('/coach/lineup.php'));
        exit;
    }

    $creatorCoachId = $coachId;
    if ($role === 'admin') {
        $coachLookup = $pdo->prepare('SELECT manager_coach_id FROM teams WHERE id = :id LIMIT 1');
        $coachLookup->execute(['id' => $teamId]);
        $row = $coachLookup->fetch();
        $creatorCoachId = $row !== false && $row['manager_coach_id'] !== null ? (int) $row['manager_coach_id'] : 1;
    }

    if ($creatorCoachId === null) {
        set_flash('error', 'No coach account linked for lineup creation.');
        header('Location: ' . app_url('/coach/lineup.php'));
        exit;
    }

    $pdo->beginTransaction();
    try {
        $insertLineup = $pdo->prepare('INSERT INTO lineups (team_id, formation, notes, created_by_coach_id, created_at) VALUES (:team_id, :formation, :notes, :created_by_coach_id, NOW())');
        $insertLineup->execute([
            'team_id' => $teamId,
            'formation' => $formation,
            'notes' => $notes === '' ? null : $notes,
            'created_by_coach_id' => $creatorCoachId,
        ]);

        $lineupId = (int) $pdo->lastInsertId();
        $insertPlayer = $pdo->prepare('INSERT INTO lineup_players (lineup_id, player_id, position_slot) VALUES (:lineup_id, :player_id, :position_slot)');
        foreach ($players as $playerId => $slot) {
            $pid = (int) $playerId;
            $slotText = trim((string) $slot);
            if ($pid > 0 && $slotText !== '') {
                $insertPlayer->execute([
                    'lineup_id' => $lineupId,
                    'player_id' => $pid,
                    'position_slot' => $slotText,
                ]);
            }
        }

        $pdo->commit();
        set_flash('success', 'Lineup saved.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        set_flash('error', 'Could not save lineup.');
    }

    header('Location: ' . app_url('/coach/lineup.php'));
    exit;
}

$selectedTeamId = isset($_GET['team_id']) ? (int) $_GET['team_id'] : (isset($teams[0]['id']) ? (int) $teams[0]['id'] : 0);
$players = [];
if ($selectedTeamId > 0) {
    $playersStmt = $pdo->prepare('SELECT id, name FROM players WHERE team_id = :team_id ORDER BY name ASC');
    $playersStmt->execute(['team_id' => $selectedTeamId]);
    $players = $playersStmt->fetchAll();
}

$recent = $pdo->query('SELECT l.id, l.formation, l.created_at, t.name AS team_name FROM lineups l INNER JOIN teams t ON t.id = l.team_id ORDER BY l.id DESC LIMIT 10')->fetchAll();

$pageTitle = 'Lineup & Formation';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Set Lineup / Formation</h1>

<?php if ($teams === []): ?>
    <div class="alert alert-warning">No managed approved teams found.</div>
<?php else: ?>
    <form method="post" class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="team_id">Team</label>
                <select class="form-select" id="team_id" name="team_id" required>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= e((string) $team['id']) ?>" <?= (int) $team['id'] === $selectedTeamId ? 'selected' : '' ?>><?= e($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="formation">Formation</label>
                <input class="form-control" id="formation" name="formation" value="4-4-2" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="notes">Notes</label>
                <input class="form-control" id="notes" name="notes" placeholder="Optional notes">
            </div>
        </div>

        <hr>
        <h2 class="h6">Players in lineup (optional position slot)</h2>
        <div class="row g-2">
            <?php foreach ($players as $player): ?>
                <div class="col-md-6">
                    <label class="form-label"><?= e($player['name']) ?></label>
                    <input class="form-control" name="players[<?= e((string) $player['id']) ?>]" placeholder="e.g. LW, ST, CB">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary" type="submit">Save Lineup</button>
        </div>
    </form>
<?php endif; ?>

<h2 class="h5">Recent Lineups</h2>
<div class="table-responsive">
    <table class="table table-striped align-middle js-paginated-table">
        <thead>
            <tr><th>ID</th><th>Team</th><th>Formation</th><th>Created</th></tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $row): ?>
                <tr>
                    <td><?= e((string) $row['id']) ?></td>
                    <td><?= e($row['team_name']) ?></td>
                    <td><?= e($row['formation']) ?></td>
                    <td><?= e(format_datetime($row['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
