<?php
declare(strict_types=1);

session_start();

$allowedOrigin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:5173';
$allowedOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];

if (in_array($allowedOrigin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$allowedOrigin}");
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/db.php';

function jsonInput(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $payload = json_decode($raw, true);
    return is_array($payload) ? $payload : [];
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requireMethod(string ...$methods): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        respond(['error' => 'Método no permitido.'], 405);
    }
}

function requireAuth(): int
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!is_numeric($userId)) {
        respond(['error' => 'Necesitas iniciar sesión.'], 401);
    }

    return (int) $userId;
}

function requestId(string $key = 'id'): int
{
    $value = $_GET[$key] ?? $_POST[$key] ?? null;
    if (!is_numeric($value) || (int) $value < 1) {
        respond(['error' => "El parámetro {$key} no es válido."], 422);
    }

    return (int) $value;
}

set_exception_handler(static function (Throwable $exception): never {
    error_log((string) $exception);
    respond(['error' => 'Error interno del servidor.'], 500);
});
