<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('GET');

$resource = $_GET['resource'] ?? 'albums';
$limit = min(max((int) ($_GET['limit'] ?? 50), 1), 100);

if ($resource === 'albums') {
    $statement = $pdo->prepare(
        'SELECT id, title, artist, image, released_at
         FROM albums
         ORDER BY released_at DESC, id DESC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    respond(['albums' => $statement->fetchAll()]);
}

if ($resource === 'artists') {
    $statement = $pdo->prepare(
        'SELECT id, name, image, bio
         FROM artists
         ORDER BY name ASC
         LIMIT :limit'
    );
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    respond(['artists' => $statement->fetchAll()]);
}

if ($resource === 'artist') {
    $artistId = requestId('id');
    $artistStatement = $pdo->prepare('SELECT id, name, image, bio FROM artists WHERE id = ?');
    $artistStatement->execute([$artistId]);
    $artist = $artistStatement->fetch();

    if (!$artist) {
        respond(['error' => 'Artista no encontrado.'], 404);
    }

    $albumsStatement = $pdo->prepare('SELECT id, title, artist, image, released_at FROM albums WHERE artist = ? ORDER BY released_at DESC, id DESC');
    $albumsStatement->execute([$artist['name']]);
    $songsStatement = $pdo->prepare(
        'SELECT s.id, s.title, a.name AS artist, al.image AS image, s.album_id
         FROM songs s
         INNER JOIN artists a ON a.id = s.artist_id
         LEFT JOIN albums al ON al.id = s.album_id
         WHERE s.artist_id = ?
         ORDER BY s.id DESC'
    );
    $songsStatement->execute([$artistId]);
    $artist['albums'] = $albumsStatement->fetchAll();
    $artist['songs'] = $songsStatement->fetchAll();
    respond(['artist' => $artist]);
}

if ($resource === 'songs') {
    $search = trim((string) ($_GET['search'] ?? ''));
            $sql = 'SELECT s.id, s.title, s.artist_id, a.name AS artist, al.image AS image, s.album_id
                FROM songs s
                INNER JOIN artists a ON a.id = s.artist_id
                LEFT JOIN albums al ON al.id = s.album_id';
    $params = [];

    if ($search !== '') {
        $sql .= ' WHERE s.title LIKE :title_search OR a.name LIKE :artist_search';
        $params[':title_search'] = "%{$search}%";
        $params[':artist_search'] = "%{$search}%";
    }

    $sql .= ' ORDER BY s.id DESC LIMIT :limit';
    $statement = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value, PDO::PARAM_STR);
    }
    $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
    $statement->execute();
    respond(['songs' => $statement->fetchAll()]);
}

if ($resource === 'album') {
    $albumId = requestId('id');
    $albumStatement = $pdo->prepare('SELECT id, title, artist, image, released_at FROM albums WHERE id = ?');
    $albumStatement->execute([$albumId]);
    $album = $albumStatement->fetch();

    if (!$album) {
        respond(['error' => 'Álbum no encontrado.'], 404);
    }

    $songsStatement = $pdo->prepare(
        'SELECT s.id, s.title, a.name AS artist, al.image AS image, s.album_id
         FROM songs s
         INNER JOIN artists a ON a.id = s.artist_id
         LEFT JOIN albums al ON al.id = s.album_id
         WHERE s.album_id = ?
         ORDER BY s.track_number ASC, s.id ASC'
    );
    $songsStatement->execute([$albumId]);
    $album['songs'] = $songsStatement->fetchAll();
    respond(['album' => $album]);
}

if ($resource === 'search') {
    $term = trim((string) ($_GET['q'] ?? ''));
    if ($term === '') {
        respond(['albums' => [], 'artists' => [], 'songs' => []]);
    }

    $like = "%{$term}%";
    $albums = $pdo->prepare('SELECT id, title, artist, image FROM albums WHERE title LIKE :album_title OR artist LIKE :album_artist ORDER BY title LIMIT 10');
    $albums->execute([':album_title' => $like, ':album_artist' => $like]);
    $artists = $pdo->prepare('SELECT id, name, image FROM artists WHERE name LIKE :term ORDER BY name LIMIT 10');
    $artists->execute([':term' => $like]);
    $songs = $pdo->prepare('SELECT s.id, s.title, a.name AS artist, COALESCE(s.image, al.image) AS image, s.album_id FROM songs s INNER JOIN artists a ON a.id = s.artist_id LEFT JOIN albums al ON al.id = s.album_id WHERE s.title LIKE :song_title OR a.name LIKE :song_artist ORDER BY s.title LIMIT 10');
    $songs->execute([':song_title' => $like, ':song_artist' => $like]);
    respond(['albums' => $albums->fetchAll(), 'artists' => $artists->fetchAll(), 'songs' => $songs->fetchAll()]);
}

respond(['error' => 'Recurso de catálogo no válido.'], 404);
