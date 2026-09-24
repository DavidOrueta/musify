<?php
declare(strict_types=1);

$requiredFiles = [
    __DIR__ . '/../api/index.php',
    __DIR__ . '/../api/bootstrap.php',
    __DIR__ . '/../api/catalog.php',
    __DIR__ . '/../api/auth.php',
    __DIR__ . '/../api/favorites.php',
    __DIR__ . '/../api/playlists.php',
];

foreach ($requiredFiles as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "Missing backend file: {$file}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Backend smoke test passed.\n");
