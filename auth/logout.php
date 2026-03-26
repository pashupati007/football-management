<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

if (is_logged_in()) {
    logout_user();
}

header('Location: ' . app_url('/index.php'));
exit;
