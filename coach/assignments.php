<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_role(['coach', 'admin']);

$pdo = db();
$user = current_user();

$coachId = null;
if (current_role() === 'coach') {
    $coachStmt = $pdo->prepare('SELECT id FROM coaches WHERE email = :email LIMIT 1');
    $coachStmt->execute(['email' => $user['email']]);
    $coachRow = $coachStmt->fetch();
    if ($coachRow !== false) {
        $coachId = (int) $coachRow['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playerId = filter_input(INPUT_POST, 'player_id', FILTER_VALIDATE_INT);
    $jerseyNumber = filter_input(INPUT_POST, 'jersey_number', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
    $teamId = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
    $assignedCoachId = filter_input(INPUT_POST, 'coach_id', FILTER_VALIDATE_INT);

    if ($playerId === false || $playerId === null || $playerId < 1) {
        set_flash('error', 'Invalid player selected.');
        header('Location: ' . app_url('/coach/assignments.php'));
        exit;
    }

    if ($jerseyNumber === false || $jerseyNumber === null) {
        set_flash('error', 'Jersey number must be between 1 and 99.');
        header('Location: ' . app_url('/coach/assignments.php'));
        exit;
    }

    if ($teamId === false || $teamId === null || $teamId < 1) {
        set_flash('error', 'Invalid team selected.');
        header('Location: ' . app_url('/coach/assignments.php'));
        exit;
    }

    if (current_role() === 'coach') {
        $assignedCoachId = $coachId;
    }

    if ($assignedCoachId === false || $assignedCoachId === null || $assignedCoachId < 1) {
        set_flash('error', 'Invalid coach selected.');
        header('Location: ' . app_url('/coach/assignments.php'));
        exit;
    }

    try {
        $update = $pdo->prepare(
            'UPDATE players
             SET jersey_number = :jersey_number, team_id = :team_id, coach_id = :coach_id
             WHERE id = :id'
        );
        $update->execute([
            'jersey_number' => $jerseyNumber,
            'team_id' => $teamId,
            'coach_id' => $assignedCoachId,
            'id' => $playerId,
        ]);
        set_flash('success', 'Player assignment updated.');
    } catch (PDOException $e) {
        set_flash('error', 'Could not update assignment. Ensure jersey number is unique.');
    }

    header('Location: ' . app_url('/coach/assignments.php'));
    exit;
}

$players = [];
$teams = [];
$coaches = [];

try {
    $playersStmt = $pdo->query(
        'SELECT p.id, p.name, p.jersey_number, t.name AS team_name, c.name AS coach_name
         FROM players p
         LEFT JOIN teams t ON t.id = p.team_id
         LEFT JOIN coaches c ON c.id = p.coach_id
         ORDER BY p.name ASC'
    );
    $players = $playersStmt->fetchAll();
} catch (PDOException $e) {
    // Fallback query avoids joins so assignment can still work if team/coach joins fail.
    $fallbackStmt = $pdo->query('SELECT id, name, jersey_number FROM players ORDER BY name ASC');
    $players = array_map(static function (array $row): array {
        $row['team_name'] = 'Unassigned';
        $row['coach_name'] = 'Unassigned';
        return $row;
    }, $fallbackStmt->fetchAll());
    set_flash('error', 'Could not fetch full player list details. Showing basic list instead.');
}

try {
    $teams = $pdo->query('SELECT id, name FROM teams ORDER BY name ASC')->fetchAll();
} catch (PDOException $e) {
    $teams = [];
}

if (current_role() === 'admin') {
    try {
        $coaches = $pdo->query('SELECT id, name FROM coaches ORDER BY name ASC')->fetchAll();
    } catch (PDOException $e) {
        $coaches = [];
    }
} else {
    $coaches = $coachId === null ? [] : [['id' => $coachId, 'name' => $user['name']]];
}

$pageTitle = 'Coach Assignments';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Assign Jersey Number and Team</h1>

<?php if (current_role() === 'coach' && $coachId === null): ?>
    <div class="alert alert-warning">Your account email is not linked to a coach record yet. Ask admin to add a coach with your email.</div>
<?php endif; ?>

<?php if ($players === []): ?>
    <div class="alert alert-warning">No players found to assign yet. Ask admin to add players first.</div>
<?php endif; ?>

<?php if ($teams === []): ?>
    <div class="alert alert-warning">No teams found to assign players into. Ask admin/coach to create teams first.</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label for="player_id" class="form-label">Player</label>
                <select id="player_id" name="player_id" class="form-select" required <?= $players === [] ? 'disabled' : '' ?>>
                    <?php foreach ($players as $player): ?>
                        <option value="<?= e((string) $player['id']) ?>"><?= e($player['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="jersey_number" class="form-label">Jersey #</label>
                <input type="number" min="1" max="99" id="jersey_number" name="jersey_number" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label for="team_id" class="form-label">Team</label>
                <select id="team_id" name="team_id" class="form-select" required <?= $teams === [] ? 'disabled' : '' ?>>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= e((string) $team['id']) ?>"><?= e($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="coach_id" class="form-label">Coach</label>
                <select id="coach_id" name="coach_id" class="form-select" <?= current_role() === 'coach' ? 'disabled' : '' ?> required>
                    <?php foreach ($coaches as $coach): ?>
                        <option value="<?= e((string) $coach['id']) ?>"><?= e($coach['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (current_role() === 'coach' && $coachId !== null): ?>
                    <input type="hidden" name="coach_id" value="<?= e((string) $coachId) ?>">
                <?php endif; ?>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary" <?= ($players === [] || $teams === [] || $coaches === []) ? 'disabled' : '' ?>>Assign</button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle js-paginated-table">
        <thead>
        <tr>
            <th>Player</th>
            <th>Jersey</th>
            <th>Team</th>
            <th>Coach</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($players as $player): ?>
            <tr>
                <td><?= e($player['name']) ?></td>
                <td><?= e($player['jersey_number'] === null ? '-' : (string) $player['jersey_number']) ?></td>
                <td><?= e($player['team_name'] ?? 'Unassigned') ?></td>
                <td><?= e($player['coach_name'] ?? 'Unassigned') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
