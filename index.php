<?php

declare(strict_types=1);

require_once __DIR__ . '/app/config/bootstrap.php';

$pdo = db();
$topPlayer = null;
$topTeam = null;
$newsItems = [];

try {
    $topPlayerStmt = $pdo->query(
        'SELECT p.id, p.name, p.position, p.matches_played, p.goals, p.assists, p.bio, p.profile_image, t.name AS team_name
         FROM players p
         LEFT JOIN teams t ON t.id = p.team_id
         WHERE p.approval_status = "approved"
         ORDER BY p.goals DESC, p.assists DESC, p.matches_played DESC, p.name ASC
         LIMIT 1'
    );
    $topPlayer = $topPlayerStmt->fetch();

    $teamRows = $pdo->query('SELECT id, name, home_ground FROM teams WHERE approval_status = "approved" ORDER BY name ASC')->fetchAll();
    $teamStandings = [];

    foreach ($teamRows as $teamRow) {
        $teamId = (int) $teamRow['id'];

        $playedStmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM matches WHERE status = "completed" AND (home_team_id = :home_id OR away_team_id = :away_id)');
        $playedStmt->execute([
            'home_id' => $teamId,
            'away_id' => $teamId,
        ]);
        $played = (int) ($playedStmt->fetch()['cnt'] ?? 0);

        $winStmt = $pdo->prepare(
            'SELECT COUNT(*) AS cnt
             FROM matches
             WHERE status = "completed"
                 AND ((home_team_id = :home_id AND home_score > away_score) OR (away_team_id = :away_id AND away_score > home_score))'
        );
        $winStmt->execute([
            'home_id' => $teamId,
            'away_id' => $teamId,
        ]);
        $wins = (int) ($winStmt->fetch()['cnt'] ?? 0);

        $drawStmt = $pdo->prepare(
            'SELECT COUNT(*) AS cnt
             FROM matches
             WHERE status = "completed"
                 AND (home_team_id = :home_id OR away_team_id = :away_id)
               AND home_score = away_score'
        );
        $drawStmt->execute([
            'home_id' => $teamId,
            'away_id' => $teamId,
        ]);
        $draws = (int) ($drawStmt->fetch()['cnt'] ?? 0);

        $losses = max(0, $played - $wins - $draws);
        $points = ($wins * 3) + $draws;

        $teamStandings[] = [
            'id' => $teamId,
            'team' => $teamRow['name'],
            'home_ground' => $teamRow['home_ground'],
            'played' => $played,
            'wins' => $wins,
            'draws' => $draws,
            'losses' => $losses,
            'points' => $points,
        ];
    }

    usort($teamStandings, static function (array $a, array $b): int {
        return $b['points'] <=> $a['points'] ?: $b['wins'] <=> $a['wins'] ?: $b['played'] <=> $a['played'];
    });

    $topTeam = $teamStandings[0] ?? null;
} catch (PDOException $e) {
    $topPlayer = null;
    $topTeam = null;
}

$hasTopPlayer = is_array($topPlayer);
$hasTopTeam = is_array($topTeam);
$showSpotlight = $hasTopPlayer || $hasTopTeam;

try {
    $newsStmt = $pdo->query(
        'SELECT id, title, content, published_at, created_at
         FROM news
         WHERE is_published = 1
         ORDER BY COALESCE(published_at, created_at) DESC, id DESC
         LIMIT 8'
    );
    $newsItems = $newsStmt->fetchAll();
} catch (PDOException $e) {
    $newsItems = [];
}

$pageTitle = 'Home';
require_once __DIR__ . '/app/views/partials/header.php';
?>

<section class="hero-section">
    <div class="hero-grid">
        <div class="hero-copy">
            <p class="hero-kicker">Football management platform</p>
            <h1 class="hero-title" aria-label="Master your squad. Dominate the pitch. Reign supreme.">
                <span class="hero-title-line">Master your squad.</span>
                <span class="hero-title-line">Dominate the pitch.</span>
                <span class="hero-title-line">Reign supreme.</span>
            </h1>
            <p class="hero-description">
                Manage players, track performance, follow standings, and stay ahead with focused insights in one place.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary btn-lg" href="<?= e(app_url('/players/index.php')) ?>">Start Managing</a>
                <a class="btn btn-outline-light btn-lg" href="<?= e(app_url('/matches/index.php')) ?>">Watch Match Data</a>
            </div>
            <div class="hero-points">
                <span>Player profiles</span>
                <span>Team standings</span>
                <span>Match updates</span>
            </div>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <div class="hero-orbit hero-orbit-a"></div>
            <div class="hero-orbit hero-orbit-b"></div>
            <div class="hero-board">
                <div class="hero-board-topbar">
                    <span>3-3-3</span>
                    <span>Live squad</span>
                    <span class="hero-board-dot"></span>
                </div>
                <div class="hero-board-inner">
                    <div class="hero-sidecard hero-sidecard-left">
                        <span class="hero-sidecard-label">⚔️ Match stats</span>
                        <strong>78%</strong>
                        <small>Possession</small>
                    </div>
                    <div class="hero-pitch">
                        <div class="hero-position hero-position-goalkeeper">GK</div>
                        <div class="hero-position hero-position-defender hero-position-left">CB</div>
                        <div class="hero-position hero-position-defender hero-position-center">CB</div>
                        <div class="hero-position hero-position-defender hero-position-right">RB</div>
                        <div class="hero-position hero-position-mid hero-position-mid-left">CM</div>
                        <div class="hero-position hero-position-mid hero-position-mid-center">AM</div>
                        <div class="hero-position hero-position-mid hero-position-mid-right">CM</div>
                        <div class="hero-position hero-position-forward hero-position-wing-left">LW</div>
                        <div class="hero-position hero-position-forward hero-position-striker">ST</div>
                        <div class="hero-position hero-position-forward hero-position-wing-right">RW</div>
                    </div>
                    <div class="hero-sidecard hero-sidecard-right">
                        <span class="hero-sidecard-label">Performance</span>
                        <strong>98</strong>
                        <small>Team rating</small>
                    </div>
                </div>
               
            </div>
        </div>
    </div>
</section>

<?php if ($showSpotlight): ?>
<section class="mt-4">
    <div class="spotlight-shell card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <p class="text-uppercase text-muted fw-semibold small mb-1">Spotlight</p>
                    <h2 class="h4 mb-0">Top Performers</h2>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if ($hasTopPlayer): ?>
                <div class="col-12 col-lg-6">
                    <article class="spotlight-card h-100">
                        <div class="spotlight-card-header">
                            <div>
                                <p class="spotlight-eyebrow mb-1">Top Player</p>
                                <h3 class="h5 mb-0">Best Form</h3>
                            </div>
                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">Rank #1</span>
                        </div>
                        <div class="spotlight-card-body">
                            <?php
                            $topPlayerName = (string) $topPlayer['name'];
                            $topPlayerInitials = strtoupper(substr($topPlayerName, 0, 1));
                            $topPlayerImage = trim((string) ($topPlayer['profile_image'] ?? ''));
                            ?>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-3">
                                <div class="player-avatar player-avatar-xl spotlight-avatar mx-auto mx-sm-0">
                                    <?php if ($topPlayerImage !== ''): ?>
                                        <img src="<?= e(storage_url($topPlayerImage)) ?>" alt="<?= e($topPlayerName) ?> profile image">
                                    <?php else: ?>
                                        <span><?= e($topPlayerInitials) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="h3 mb-1"><?= e($topPlayerName) ?></h4>
                                    <p class="text-muted mb-0">
                                        <?= e($topPlayer['position']) ?><?php if (!empty($topPlayer['team_name'])): ?> · <?= e($topPlayer['team_name']) ?><?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6 col-xl-3"><div class="stat-chip stat-chip-sm"><strong><?= e((string) $topPlayer['matches_played']) ?></strong><span>⚔️ Matches</span></div></div>
                                <div class="col-6 col-xl-3"><div class="stat-chip stat-chip-sm"><strong><?= e((string) $topPlayer['goals']) ?></strong><span>⚽ Goals</span></div></div>
                                <div class="col-6 col-xl-3"><div class="stat-chip stat-chip-sm"><strong><?= e((string) $topPlayer['assists']) ?></strong><span>👥 Assists</span></div></div>
                                <div class="col-6 col-xl-3"><div class="stat-chip stat-chip-sm"><strong>#1</strong><span>Rank</span></div></div>
                            </div>
                            <?php if (!empty($topPlayer['bio'])): ?>
                                <p class="mb-0 text-body-secondary spotlight-text"><?= e((string) $topPlayer['bio']) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
                <?php endif; ?>

                <?php if ($hasTopTeam): ?>
                <div class="col-12 col-lg-6">
                    <article class="spotlight-card h-100 spotlight-card-team">
                        <div class="spotlight-card-header">
                            <div>
                                <p class="spotlight-eyebrow mb-1">Top Team</p>
                                <h3 class="h5 mb-0">League Leader</h3>
                            </div>
                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Table Leader</span>
                        </div>
                        <div class="spotlight-card-body">
                            <?php
                            $topTeamName = (string) $topTeam['team'];
                            $topTeamInitials = strtoupper(implode('', array_slice(preg_split('/\s+/', $topTeamName) ?: [], 0, 2)));
                            $topTeamGround = trim((string) ($topTeam['home_ground'] ?? ''));
                            ?>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-3">
                                <div class="team-avatar mx-auto mx-sm-0 px-2">
                                    <span class="h5"><?= e($topTeamInitials !== '' ? $topTeamInitials : 'T') ?></span>
                                </div>
                                <div>
                                    <h4 class="h3 mb-1"><?= e($topTeamName) ?></h4>
                                    <p class="text-muted mb-0">
                                        <?= $topTeamGround !== '' ? e($topTeamGround) : 'Home ground not set' ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6 col-xl-3"><div class="stat-chip stat-chip-sm"><strong><?= e((string) $topTeam['played']) ?></strong><span>Played</span></div></div>
                                <div class="col-6 col-xl-3"><div class="stat-chip stat-chip-sm"><strong><?= e((string) $topTeam['wins']) ?></strong><span>Wins</span></div></div>
                                <div class="col-6 col-xl-3"><div class="stat-chip stat-chip-sm"><strong><?= e((string) $topTeam['draws']) ?></strong><span>Draws</span></div></div>
                                <div class="col-6 col-xl-3"><div class="stat-chip stat-chip-sm"><strong><?= e((string) $topTeam['points']) ?></strong><span>Points</span></div></div>
                            </div>
                            <div class="spotlight-metrics d-flex flex-wrap gap-2">
                                <span class="badge rounded-pill text-bg-light border">L: <?= e((string) $topTeam['losses']) ?></span>
                                <span class="badge rounded-pill text-bg-light border">⚔️ <?= e((string) $topTeam['played']) ?></span>
                                <span class="badge rounded-pill text-bg-light border">Pts: <?= e((string) $topTeam['points']) ?></span>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="mt-4" id="weather-section" data-endpoint="<?= e(app_url('/weather/forecast.php')) ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Weather Forecast (5 Day / 3 Hour)</h2>
    </div>
    <div id="weather-forecast" class="border rounded p-3 bg-white">
        <p class="mb-0 text-muted">Loading weather forecast...</p>
    </div>
</section>

<section class="mt-4">
    <div class="card border-0 shadow-sm news-section-card">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <p class="text-uppercase text-muted fw-semibold small mb-1">Updates</p>
                    <h2 class="h4 mb-0">Latest News</h2>
                </div>
                <?php if (is_admin()): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('/admin/news.php')) ?>">Manage News</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <?php if ($newsItems === []): ?>
                <div class="alert alert-info mb-0">No news published yet.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-lg-2 g-3">
                    <?php foreach ($newsItems as $news): ?>
                        <div class="col">
                            <article class="card h-100 border-0 shadow-sm news-item-card">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">News</span>
                                        <small class="text-muted text-end"><?= e(format_datetime((string) ($news['published_at'] ?? $news['created_at']))) ?></small>
                                    </div>
                                    <h3 class="h5 card-title mb-3"><?= e($news['title']) ?></h3>
                                    <div class="news-item-content mb-3">
                                        <?= nl2br(e($news['content'])) ?>
                                    </div>
                                    <div class="mt-auto pt-3 border-top small text-muted">
                                        Published <?= e(format_datetime((string) ($news['published_at'] ?? $news['created_at']))) ?>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/app/views/partials/footer.php'; ?>
