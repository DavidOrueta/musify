<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'name' => 'Musify API',
    'status' => 'ok',
    'message' => 'La API está disponible.',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
