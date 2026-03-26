<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'create';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($mode === 'delete' && $id) {
        $pdo->prepare('DELETE FROM matches WHERE id = :id')->execute(['id' => $id]);
        set_flash('success', 'Match deleted.');
        header('Location: ' . app_url('/admin/matches.php'));
        exit;
    }

    $competitionId = filter_input(INPUT_POST, 'competition_id', FILTER_VALIDATE_INT);
    $homeTeamId = filter_input(INPUT_POST, 'home_team_id', FILTER_VALIDATE_INT);
    $awayTeamId = filter_input(INPUT_POST, 'away_team_id', FILTER_VALIDATE_INT);
    $matchDate = trim($_POST['match_date'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $status = trim($_POST['status'] ?? 'scheduled');
    $homeScore = filter_input(INPUT_POST, 'home_score', FILTER_VALIDATE_INT);
    $awayScore = filter_input(INPUT_POST, 'away_score', FILTER_VALIDATE_INT);

    if (!in_array($status, ['scheduled', 'completed'], true) || !$homeTeamId || !$awayTeamId || $homeTeamId === $awayTeamId || $matchDate === '') {
        set_flash('error', 'Invalid match data.');
        header('Location: ' . app_url('/admin/matches.php'));
        exit;
    }

    $homeScoreVal = $status === 'completed' ? max(0, (int) $homeScore) : null;
    $awayScoreVal = $status === 'completed' ? max(0, (int) $awayScore) : null;

    if ($mode === 'update' && $id) {
        $stmt = $pdo->prepare(
            'UPDATE matches
             SET competition_id = :competition_id, home_team_id = :home_team_id, away_team_id = :away_team_id,
                 match_date = :match_date, venue = :venue, status = :status, home_score = :home_score, away_score = :away_score
             WHERE id = :id'
        );
        $stmt->execute([
            'competition_id' => $competitionId ?: null,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'match_date' => $matchDate,
            'venue' => $venue === '' ? null : $venue,
            'status' => $status,
            'home_score' => $homeScoreVal,
            'away_score' => $awayScoreVal,
            'id' => $id,
        ]);
        set_flash('success', 'Match updated.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO matches (competition_id, home_team_id, away_team_id, match_date, venue, status, home_score, away_score, created_at)
             VALUES (:competition_id, :home_team_id, :away_team_id, :match_date, :venue, :status, :home_score, :away_score, NOW())'
        );
        $stmt->execute([
            'competition_id' => $competitionId ?: null,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'match_date' => $matchDate,
            'venue' => $venue === '' ? null : $venue,
            'status' => $status,
            'home_score' => $homeScoreVal,
            'away_score' => $awayScoreVal,
        ]);
        set_flash('success', 'Match created.');
    }

    header('Location: ' . app_url('/admin/matches.php'));
    exit;
}

$teams = $pdo->query('SELECT id, name FROM teams WHERE approval_status = "approved" ORDER BY name ASC')->fetchAll();
$competitions = $pdo->query('SELECT id, name, type FROM competitions ORDER BY name ASC')->fetchAll();
$matches = $pdo->query(
    'SELECT m.id, m.match_date, m.status, m.home_score, m.away_score, m.home_team_id, m.away_team_id,
            m.competition_id, m.venue, ht.name AS home_team, at.name AS away_team
     FROM matches m
     INNER JOIN teams ht ON ht.id = m.home_team_id
     INNER JOIN teams at ON at.id = m.away_team_id
     ORDER BY m.match_date DESC, m.id DESC'
)->fetchAll();

$pageTitle = 'Admin Matches';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<h1 class="h3 mb-3">Admin: Manage Matches & Results</h1>

<form method="post" class="row g-2 mb-4 border rounded p-3">
    <input type="hidden" name="mode" value="create">
    <div class="col-md-2"><input class="form-control" name="match_date" type="datetime-local" required></div>
    <div class="col-md-2"><input class="form-control" name="venue" placeholder="Venue"></div>
    <div class="col-md-2">
        <select class="form-select" name="competition_id">
            <option value="">Competition</option>
            <?php foreach ($competitions as $c): ?>
                <option value="<?= e((string) $c['id']) ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><select class="form-select" name="home_team_id" required><?php foreach ($teams as $t): ?><option value="<?= e((string) $t['id']) ?>"><?= e($t['name']) ?> (Home)</option><?php endforeach; ?></select></div>
    <div class="col-md-2"><select class="form-select" name="away_team_id" required><?php foreach ($teams as $t): ?><option value="<?= e((string) $t['id']) ?>"><?= e($t['name']) ?> (Away)</option><?php endforeach; ?></select></div>
    <div class="col-md-1"><select class="form-select" name="status"><option value="scheduled">Scheduled</option><option value="completed">Completed</option></select></div>
    <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">Add</button></div>
    <div class="col-md-1"><input class="form-control" name="home_score" type="number" min="0" placeholder="H"></div>
    <div class="col-md-1"><input class="form-control" name="away_score" type="number" min="0" placeholder="A"></div>
</form>

<?php foreach ($matches as $m): ?>
    <form method="post" class="row g-2 align-items-center mb-2 border rounded p-2">
        <input type="hidden" name="id" value="<?= e((string) $m['id']) ?>">
        <input type="hidden" name="mode" value="update">
        <div class="col-md-2"><input class="form-control" name="match_date" type="datetime-local" value="<?= e(str_replace(' ', 'T', substr($m['match_date'], 0, 16))) ?>" required></div>
        <div class="col-md-2"><input class="form-control" name="venue" value="<?= e((string) ($m['venue'] ?? '')) ?>"></div>
        <div class="col-md-2"><select class="form-select" name="competition_id"><option value="">Competition</option><?php foreach ($competitions as $c): ?><option value="<?= e((string) $c['id']) ?>" <?= (int) ($m['competition_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><select class="form-select" name="home_team_id" required><?php foreach ($teams as $t): ?><option value="<?= e((string) $t['id']) ?>" <?= (int) $m['home_team_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?> (H)</option><?php endforeach; ?></select></div>
        <div class="col-md-2"><select class="form-select" name="away_team_id" required><?php foreach ($teams as $t): ?><option value="<?= e((string) $t['id']) ?>" <?= (int) $m['away_team_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?> (A)</option><?php endforeach; ?></select></div>
        <div class="col-md-1"><select class="form-select" name="status"><option value="scheduled" <?= $m['status'] === 'scheduled' ? 'selected' : '' ?>>Sch</option><option value="completed" <?= $m['status'] === 'completed' ? 'selected' : '' ?>>Done</option></select></div>
        <div class="col-md-1"><input class="form-control" name="home_score" type="number" min="0" value="<?= e((string) ($m['home_score'] ?? '')) ?>"></div>
        <div class="col-md-1"><input class="form-control" name="away_score" type="number" min="0" value="<?= e((string) ($m['away_score'] ?? '')) ?>"></div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100" type="submit">Save</button></div>
        <div class="col-md-1"><button class="btn btn-outline-danger w-100" type="submit" name="mode" value="delete" onclick="return confirm('Delete match?');">Del</button></div>
    </form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>
