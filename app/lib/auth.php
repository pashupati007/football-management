<?php

declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && (($user['role'] ?? 'public') === 'admin');
}

function is_coach(): bool
{
    return current_role() === 'coach';
}

function is_player(): bool
{
    return current_role() === 'player';
}

function current_role(): string
{
    $user = current_user();
    return $user['role'] ?? 'public';
}

function role_rank(string $role): int
{
    return match ($role) {
        'public' => 1,
        'player' => 2,
        'coach' => 3,
        'admin' => 4,
        default => 1,
    };
}

function can_manage_assignments(): bool
{
    return is_coach() || is_admin();
}

function require_role(array $roles): void
{
    require_login();
    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}

function require_admin(): void
{
    require_role(['admin']);
}

function require_coach_or_admin(): void
{
    require_role(['coach', 'admin']);
}

function require_player(): void
{
    require_role(['player']);
}

function current_coach_id(PDO $pdo): ?int
{
    $user = current_user();
    if ($user === null) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id FROM coaches WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $user['email']]);
    $row = $stmt->fetch();

    return $row === false ? null : (int) $row['id'];
}

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'] ?? 'public',
    ];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . app_url('/auth/login.php'));
        exit;
    }
}

function set_flash(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $message = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $message;
}
