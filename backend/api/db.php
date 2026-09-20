<?php
declare(strict_types=1);

$host = getenv('MUSIFY_DB_HOST') ?: '127.0.0.1';
$port = getenv('MUSIFY_DB_PORT') ?: '3306';
$database = getenv('MUSIFY_DB_NAME') ?: 'musify';
$user = getenv('MUSIFY_DB_USER') ?: 'root';
$password = getenv('MUSIFY_DB_PASSWORD') ?: '';

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'No se pudo conectar con la base de datos.',
        'detail' => getenv('APP_ENV') === 'development' ? $exception->getMessage() : null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
