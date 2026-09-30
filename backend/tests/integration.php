<?php
declare(strict_types=1);

$apiBase = rtrim(getenv('MUSIFY_TEST_API_URL') ?: 'http://127.0.0.1:8080/backend/api', '/');
$databaseHost = getenv('MUSIFY_DB_HOST') ?: '127.0.0.1';
$databasePort = getenv('MUSIFY_DB_PORT') ?: '3306';
$databaseName = getenv('MUSIFY_DB_NAME') ?: 'musify';
$databaseUser = getenv('MUSIFY_DB_USER') ?: 'musify';
$databasePassword = getenv('MUSIFY_DB_PASSWORD') ?: 'musify';
$sessionCookie = '';
$testUserId = null;

$pdo = new PDO(
    "mysql:host={$databaseHost};port={$databasePort};dbname={$databaseName};charset=utf8mb4",
    $databaseUser,
    $databasePassword,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function apiRequest(string $method, string $path, ?array $payload = null): array
{
    global $apiBase, $sessionCookie;

    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($sessionCookie !== '') {
        $headers[] = 'Cookie: ' . $sessionCookie;
    }

    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $payload === null ? '' : json_encode($payload, JSON_THROW_ON_ERROR),
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ];
    $body = @file_get_contents($apiBase . '/' . $path, false, stream_context_create($options));
    if ($body === false) {
        throw new RuntimeException("API request failed: {$method} {$path}");
    }

    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
            $status = (int) $matches[1];
        }
        if (preg_match('/^Set-Cookie:\s*([^;]+)/i', $header, $matches)) {
            $sessionCookie = $matches[1];
        }
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        throw new RuntimeException("API returned invalid JSON for {$method} {$path}");
    }

    return ['status' => $status, 'body' => $decoded];
}

function expectStatus(array $response, int $expected, string $step): array
{
    if ($response['status'] !== $expected) {
        throw new RuntimeException(
            "{$step}: expected HTTP {$expected}, received {$response['status']}: " .
            json_encode($response['body'], JSON_UNESCAPED_UNICODE)
        );
    }

    return $response['body'];
}

$songId = (int) $pdo->query('SELECT id FROM songs ORDER BY id LIMIT 1')->fetchColumn();
if ($songId < 1) {
    throw new RuntimeException('The integration test requires at least one seeded song.');
}

try {
    expectStatus(apiRequest('GET', 'favorites.php'), 401, 'Unauthenticated favorites');

    $demoUser = expectStatus(apiRequest('POST', 'auth.php?action=login', [
        'email' => 'demo@musify.local',
        'password' => 'MusifyDemo2026!',
    ]), 200, 'Log in demo user');
    if (($demoUser['user']['email'] ?? '') !== 'demo@musify.local') {
        throw new RuntimeException('The demo account did not log in.');
    }
    $demoFavorites = expectStatus(apiRequest('GET', 'favorites.php'), 200, 'Read demo favorites');
    if (count($demoFavorites['songs'] ?? []) < 3) {
        throw new RuntimeException('The demo account should have at least three favorites.');
    }
    expectStatus(apiRequest('POST', 'auth.php?action=logout'), 200, 'Log out demo user');
    expectStatus(apiRequest('GET', 'favorites.php'), 401, 'Reject favorites after demo logout');

    $suffix = bin2hex(random_bytes(6));
    $registration = expectStatus(apiRequest('POST', 'auth.php?action=register', [
        'username' => 'integration_' . $suffix,
        'email' => 'integration_' . $suffix . '@example.test',
        'password' => 'test-password-123',
    ]), 201, 'Register test user');
    $testUserId = (int) ($registration['user']['id'] ?? 0);
    if ($testUserId < 1 || ($registration['authenticated'] ?? false) !== true) {
        throw new RuntimeException('Registration did not return an authenticated user.');
    }

    $favorites = expectStatus(apiRequest('GET', 'favorites.php'), 200, 'Read empty favorites');
    if (($favorites['songs'] ?? null) !== []) {
        throw new RuntimeException('A new test user should not have favorites.');
    }

    expectStatus(apiRequest('POST', 'favorites.php?song_id=' . $songId), 201, 'Add favorite');
    expectStatus(apiRequest('POST', 'favorites.php?song_id=' . $songId), 201, 'Add duplicate favorite');
    $favorites = expectStatus(apiRequest('GET', 'favorites.php'), 200, 'Read added favorite');
    if (count($favorites['songs'] ?? []) !== 1 || (int) $favorites['songs'][0]['id'] !== $songId) {
        throw new RuntimeException('The favorite should appear exactly once in the API response.');
    }

    expectStatus(apiRequest('POST', 'favorites.php?song_id=999999999'), 404, 'Reject unknown song');
    expectStatus(apiRequest('DELETE', 'favorites.php?song_id=' . $songId), 200, 'Remove favorite');
    $favorites = expectStatus(apiRequest('GET', 'favorites.php'), 200, 'Read removed favorite');
    if (($favorites['songs'] ?? null) !== []) {
        throw new RuntimeException('The favorite should be absent after deletion.');
    }

    expectStatus(apiRequest('POST', 'auth.php?action=logout'), 200, 'Log out test user');
    expectStatus(apiRequest('GET', 'favorites.php'), 401, 'Reject favorites after logout');
    fwrite(STDOUT, "Backend API integration test passed.\n");
} finally {
    if ($testUserId !== null && $testUserId > 0) {
        $statement = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $statement->execute([$testUserId]);
    }
}