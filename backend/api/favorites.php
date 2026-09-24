<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $statement = $pdo->prepare(
        'SELECT s.id, s.title, a.name AS artist, al.title AS album, s.image, s.album_id, f.created_at
         FROM favorites f
         INNER JOIN songs s ON s.id = f.song_id
         INNER JOIN artists a ON a.id = s.artist_id
         LEFT JOIN albums al ON al.id = s.album_id
         WHERE f.user_id = ?
         ORDER BY f.created_at DESC'
    );
    $statement->execute([$userId]);
    respond(['songs' => $statement->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $songId = requestId('song_id');
    $song = $pdo->prepare('SELECT id FROM songs WHERE id = ?');
    $song->execute([$songId]);
    if (!$song->fetch()) {
        respond(['error' => 'La canción no existe.'], 404);
    }

    $statement = $pdo->prepare('INSERT IGNORE INTO favorites (user_id, song_id) VALUES (?, ?)');
    $statement->execute([$userId, $songId]);
    respond(['favorite' => true, 'song_id' => $songId], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $songId = requestId('song_id');
    $statement = $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND song_id = ?');
    $statement->execute([$userId, $songId]);
    respond(['favorite' => false, 'song_id' => $songId]);
}

header('Allow: GET, POST, DELETE');
respond(['error' => 'Método no permitido.'], 405);
