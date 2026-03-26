<?php

declare(strict_types=1);

function app_url(string $path = ''): string
{
    $base = rtrim($_ENV['APP_URL'] ?? '', '/');

    if ($base === '') {
        return $path;
    }

    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function storage_url(string $path = ''): string
{
    return app_url('/' . ltrim($path, '/'));
}

function format_datetime(?string $value, string $format = 'M j, Y g:i a'): string
{
    $trimmedValue = trim((string) $value);

    if ($trimmedValue === '') {
        return '-';
    }

    try {
        $dateTime = new DateTimeImmutable($trimmedValue);
    } catch (Throwable $e) {
        return $trimmedValue;
    }

    return $dateTime->format($format);
}
