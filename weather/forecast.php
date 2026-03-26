<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$apiKey = $_ENV['OPENWEATHER_API_KEY'] ?? '';
if ($apiKey === '') {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Weather API key not configured.',
    ]);
    exit;
}

$lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lon = filter_input(INPUT_GET, 'lon', FILTER_VALIDATE_FLOAT);

if ($lat === false || $lat === null || $lon === false || $lon === null) {
    // Default to UK if geolocation is not available.
    $lat = 55.3781;
    $lon = -3.4360;
}

$query = http_build_query([
    'lat' => $lat,
    'lon' => $lon,
    'appid' => $apiKey,
    'units' => 'metric',
]);

$url = 'https://api.openweathermap.org/data/2.5/forecast?' . $query;

$responseBody = null;
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $responseBody = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($responseBody === false || $httpCode >= 400) {
        http_response_code(502);
        echo json_encode([
            'ok' => false,
            'message' => 'Failed to fetch weather forecast from upstream API.',
        ]);
        exit;
    }
} else {
    $responseBody = @file_get_contents($url);
    if ($responseBody === false) {
        http_response_code(502);
        echo json_encode([
            'ok' => false,
            'message' => 'Failed to fetch weather forecast from upstream API.',
        ]);
        exit;
    }
}

$payload = json_decode($responseBody, true);
if (!is_array($payload) || !isset($payload['list']) || !is_array($payload['list'])) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'message' => 'Invalid weather response payload.',
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'city' => $payload['city']['name'] ?? null,
    'country' => $payload['city']['country'] ?? null,
    'list' => $payload['list'],
]);
