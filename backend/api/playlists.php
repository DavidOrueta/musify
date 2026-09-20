<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$userId = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $playlistId = isset($_GET['id']) ? requestId() : null;
    if ($playlistId !== null) {
        $statement = $pdo->prepare('SELECT id, user_id, name, image, created_at, updated_at FROM playlists WHERE id = ? AND user_id = ?');
        $statement->execute([$playlistId, $userId]);
        $playlist = $statement->fetch();
        if (!$playlist) {
            respond(['error' => 'Lista no encontrada.'], 404);
        }
        $songs = $pdo->prepare(
            'SELECT s.id, s.title, a.name AS artist, COALESCE(s.image, al.image) AS image, s.album_id
             FROM playlist_songs ps
             INNER JOIN songs s ON s.id = ps.song_id
             INNER JOIN artists a ON a.id = s.artist_id
             LEFT JOIN albums al ON al.id = s.album_id
             WHERE ps.playlist_id = ?
             ORDER BY ps.position ASC, ps.created_at ASC'
        );
        $songs->execute([$playlistId]);
        $playlist['songs'] = $songs->fetchAll();
        respond(['playlist' => $playlist]);
    }

    $statement = $pdo->prepare('SELECT p.id, p.name, p.image, p.created_at, p.updated_at, (SELECT COUNT(*) FROM playlist_songs ps WHERE ps.playlist_id = p.id) AS song_count FROM playlists p WHERE p.user_id = ? ORDER BY p.updated_at DESC');
    $statement->execute([$userId]);
    respond(['playlists' => $statement->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = jsonInput();

    if (($_GET['action'] ?? '') === 'add-song') {
        $playlistId = requestId();
        $songId = (int) ($input['song_id'] ?? 0);
        if ($songId < 1) {
            respond(['error' => 'La canción no es válida.'], 422);
        }
        $owner = $pdo->prepare('SELECT id FROM playlists WHERE id = ? AND user_id = ?');
        $owner->execute([$playlistId, $userId]);
        if (!$owner->fetch()) {
            respond(['error' => 'Lista no encontrada.'], 404);
        }
        $existing = $pdo->prepare('SELECT 1 FROM playlist_songs WHERE playlist_id = ? AND song_id = ? LIMIT 1');
        $existing->execute([$playlistId, $songId]);
        if ($existing->fetch()) {
            respond(['error' => 'La canción ya está en esta lista.'], 409);
        }
        $statement = $pdo->prepare('INSERT IGNORE INTO playlist_songs (playlist_id, song_id, position) SELECT ?, ?, COALESCE(MAX(position), 0) + 1 FROM playlist_songs WHERE playlist_id = ?');
        $statement->execute([$playlistId, $songId, $playlistId]);
        respond(['added' => true, 'song_id' => $songId]);
    }

    $name = trim((string) ($input['name'] ?? ''));
    $image = trim((string) ($input['image'] ?? '')) ?: null;
    if ($name === '' || mb_strlen($name) > 120) {
        respond(['error' => 'El nombre de la lista no es válido.'], 422);
    }

    $statement = $pdo->prepare('INSERT INTO playlists (user_id, name, image) VALUES (?, ?, ?)');
    $statement->execute([$userId, $name, $image]);
    $playlistId = (int) $pdo->lastInsertId();
    respond(['playlist' => ['id' => $playlistId, 'user_id' => $userId, 'name' => $name, 'image' => $image]], 201);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $playlistId = requestId();
    $input = jsonInput();
    $name = trim((string) ($input['name'] ?? ''));
    $image = trim((string) ($input['image'] ?? '')) ?: null;

    if ($name === '' || mb_strlen($name) > 120) {
        respond(['error' => 'El nombre de la lista no es válido.'], 422);
    }

    $statement = $pdo->prepare('UPDATE playlists SET name = ?, image = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
    $statement->execute([$name, $image, $playlistId, $userId]);
    if ($statement->rowCount() === 0) {
        respond(['error' => 'Lista no encontrada.'], 404);
    }

    respond(['playlist' => ['id' => $playlistId, 'user_id' => $userId, 'name' => $name, 'image' => $image]]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $playlistId = requestId();

    if (($_GET['action'] ?? '') === 'remove-song') {
        $songId = (int) ($_GET['song_id'] ?? 0);
        $statement = $pdo->prepare('DELETE FROM playlist_songs WHERE playlist_id = ? AND song_id = ? AND EXISTS (SELECT 1 FROM playlists WHERE id = ? AND user_id = ?)');
        $statement->execute([$playlistId, $songId, $playlistId, $userId]);
        respond(['deleted' => $statement->rowCount() > 0]);
    }

    $statement = $pdo->prepare('DELETE FROM playlists WHERE id = ? AND user_id = ?');
    $statement->execute([$playlistId, $userId]);
    respond(['deleted' => $statement->rowCount() > 0]);
}

header('Allow: GET, POST, DELETE');
respond(['error' => 'Método no permitido.'], 405);
