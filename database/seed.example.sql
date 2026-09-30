-- Seed mínimo para pruebas y CI; cargar después de schema.sql.
-- Solo incluye esta cuenta pública de demostración, documentada en README.md.

INSERT INTO artists (name, image, bio) VALUES
    ('Demo Artist', 'https://placehold.co/600x600/10251b/8fe7c2?text=Artist', 'Artista de demostración.')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO albums (title, artist, image, released_at) VALUES
    ('Demo Album', 'Demo Artist', 'https://placehold.co/600x600/10251b/8fe7c2?text=Album', '2026-01-01')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO songs (title, artist_id, album_id, duration, track_number, image)
SELECT demo_songs.title, artists.id, albums.id, demo_songs.duration, demo_songs.track_number, albums.image
FROM (
    SELECT 'Demo Song 1' AS title, 180 AS duration, 1 AS track_number
    UNION ALL SELECT 'Demo Song 2', 192, 2
    UNION ALL SELECT 'Demo Song 3', 205, 3
) AS demo_songs
INNER JOIN artists ON artists.name = 'Demo Artist'
INNER JOIN albums ON albums.artist = artists.name AND albums.title = 'Demo Album'
WHERE NOT EXISTS (
    SELECT 1 FROM songs
    WHERE songs.title = demo_songs.title
      AND songs.artist_id = artists.id
      AND songs.album_id = albums.id
);

INSERT INTO users (name, email, password_hash) VALUES
    ('musify-demo', 'demo@musify.local', '$2y$10$6lmCWQPb54p6oMhjGe1cM.OZ12YGuZpa8ZCEJWy.ZXoyNUtwVUY/i')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password_hash = VALUES(password_hash);

INSERT IGNORE INTO favorites (user_id, song_id)
SELECT users.id, songs.id
FROM users
CROSS JOIN songs
WHERE users.email = 'demo@musify.local'
ORDER BY songs.id
LIMIT 3;
