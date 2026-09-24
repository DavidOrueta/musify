-- Seed público de ejemplo para una instalación limpia.
-- El esquema completo de desarrollo se encuentra en init.sql.
-- No incluyas usuarios reales, tokens ni hashes privados en un repositorio público.

INSERT INTO artists (name, image, bio) VALUES
    ('Demo Artist', 'https://placehold.co/600x600/10251b/8fe7c2?text=Artist', 'Artista de demostración.')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO albums (title, artist, image, released_at) VALUES
    ('Demo Album', 'Demo Artist', 'https://placehold.co/600x600/10251b/8fe7c2?text=Album', '2026-01-01')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO songs (title, artist_id, album_id, duration, track_number, image)
SELECT 'Demo Song', artists.id, albums.id, 180, 1, albums.image
FROM artists
INNER JOIN albums ON albums.artist = artists.name
WHERE artists.name = 'Demo Artist' AND albums.title = 'Demo Album'
  AND NOT EXISTS (
      SELECT 1 FROM songs
      WHERE title = 'Demo Song' AND artist_id = artists.id AND album_id = albums.id
  );
