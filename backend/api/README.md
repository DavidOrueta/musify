# Backend PHP de Musify

## Configuración

El backend usa por defecto:

- Host: `127.0.0.1`
- Base de datos: `musify`
- Usuario: `root`
- Contraseña: vacía

Se pueden cambiar con las variables `MUSIFY_DB_HOST`, `MUSIFY_DB_PORT`, `MUSIFY_DB_NAME`, `MUSIFY_DB_USER` y `MUSIFY_DB_PASSWORD`.

Con XAMPP, el backend queda disponible en:

`http://localhost/musify/api/`

## Endpoints

- `GET catalog.php?resource=albums`
- `GET catalog.php?resource=artists`
- `GET catalog.php?resource=songs&search=texto`
- `GET catalog.php?resource=album&id=1`
- `GET catalog.php?resource=search&q=texto`
- `GET auth.php?action=me`
- `POST auth.php?action=login`
- `POST auth.php?action=register`
- `POST auth.php?action=logout`
- `GET|POST|DELETE favorites.php`
- `GET|POST|DELETE playlists.php`

Las peticiones de escritura esperan JSON. Las sesiones PHP se envían mediante cookies y el CORS está limitado a los puertos de desarrollo de Vite.

## Comprobación de XAMPP

Desde PowerShell:

```powershell
& 'C:\xampp\php\php.exe' -l api\catalog.php
```

Para probar una respuesta real hay que arrancar Apache y MySQL en XAMPP y restaurar primero la base `musify`.

## Nota sobre el SQL recuperado

El volcado contiene tablas antiguas y nuevas con nombres parecidos. Estos endpoints usan la estructura nueva que consume el frontend: `users`, `albums`, `artists`, `songs`, `favorites`, `playlists` y `playlist_songs`. Si la restauración solo contiene `canciones` o `playlist`, hay que confirmar sus columnas antes de cambiar las consultas, especialmente las relaciones de canciones y usuarios.
