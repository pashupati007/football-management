<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Tacticus';
$flashSuccess = get_flash('success');
$flashError = get_flash('error');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$isActive = static function (string $segment) use ($currentPath): string {
    return str_contains($currentPath, $segment) ? 'active' : '';
};
$fullTitle = $pageTitle === 'Tacticus' ? 'Tacticus' : $pageTitle . ' | Tacticus';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14191e">
    <meta name="application-name" content="Tacticus">
    <meta name="apple-mobile-web-app-title" content="Tacticus">
    <link rel="icon" href="<?= e(app_url('/assets/image/logo.png')) ?>" type="image/png">
    <link rel="shortcut icon" href="<?= e(app_url('/assets/image/logo.png')) ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= e(app_url('/assets/image/logo.png')) ?>">
    <title><?= e($fullTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?= e(app_url('/assets/css/style.css')) ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg custom-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= e(app_url('/index.php')) ?>">
            <img class="navbar-brand-logo" src="<?= e(app_url('/assets/image/logo.png')) ?>" alt="Tacticus logo">
            <span>Tacticus</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav nav-main-links mx-lg-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $isActive('/players/') ?>" href="<?= e(app_url('/players/index.php')) ?>">Players</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $isActive('/teams/') ?>" href="<?= e(app_url('/teams/index.php')) ?>">Teams</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $isActive('/coaches/') ?>" href="<?= e(app_url('/coaches/index.php')) ?>">Coaches</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $isActive('/matches/') ?>" href="<?= e(app_url('/matches/index.php')) ?>">Matches</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $isActive('/standings/') ?>" href="<?= e(app_url('/standings/index.php')) ?>">Standings</a>
                </li>
            </ul>

            <ul class="navbar-nav nav-auth-links ms-lg-auto align-items-lg-center gap-lg-2">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <span class="nav-role-badge"><?= e(ucfirst(current_role())) ?></span>
                    </li>
                    <?php if (is_player()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Player</a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= e(app_url('/player/stats.php')) ?>">My Stats</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/player/team.php')) ?>">My Team</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/player/profile.php')) ?>">My Profile</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/player/schedule.php')) ?>">Match Schedule</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if (is_coach()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Coach</a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= e(app_url('/coach/team.php')) ?>">Manage Team</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/coach/assignments.php')) ?>">Assign Players</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/coach/lineup.php')) ?>">Lineup / Formation</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/coach/stats.php')) ?>">Team Stats</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/coach/availability.php')) ?>">Player Availability</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if (current_role() === 'public'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isActive('/fan/') ?>" href="<?= e(app_url('/fan/favorites.php')) ?>">Favorites</a>
                        </li>
                    <?php endif; ?>
                    <?php if (is_admin()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin</a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= e(app_url('/admin/users.php')) ?>">Manage Users</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/admin/players.php')) ?>">Manage Players</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/admin/coaches.php')) ?>">Manage Coaches</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/admin/teams.php')) ?>">Manage Teams</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/admin/competitions.php')) ?>">Leagues/Tournaments</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/admin/approvals.php')) ?>">Approvals</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/admin/matches.php')) ?>">Matches & Results</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/admin/reports.php')) ?>">Reports & Stats</a></li>
                                <li><a class="dropdown-item" href="<?= e(app_url('/admin/news.php')) ?>">Manage News</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(app_url('/auth/logout.php')) ?>">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive('/auth/login.php') ?>" href="<?= e(app_url('/auth/login.php')) ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive('/auth/register.php') ?>" href="<?= e(app_url('/auth/register.php')) ?>">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php if ($flashSuccess !== null): ?>
        <div class="alert alert-success"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError !== null): ?>
        <div class="alert alert-danger"><?= e($flashError) ?></div>
    <?php endif; ?>
