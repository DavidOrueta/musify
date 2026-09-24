# Musify

Musify es una app de música tipo streaming que combina un frontend en Vue 3 con una API en PHP y MySQL. El proyecto incluye catálogo, búsqueda, artistas, álbumes, favoritos y playlists, con un flujo real de autenticación y gestión de contenido.

![CI](https://github.com/USUARIO/MUSIFY/actions/workflows/ci.yml/badge.svg)

## Stack

- Frontend: Vue 3 + Vite
- Backend: PHP + PDO + Apache
- Base de datos: MySQL
- Routing: Vue Router

## Características

- Catálogo de artistas, álbumes y canciones
- Búsqueda general del catálogo
- Perfil de artista y detalle de álbum
- Favoritos por usuario
- Playlists personales
- Reproducción básica de canciones
- Autenticación de usuarios

## Estructura

- `frontend/`: aplicación cliente
- `backend/api/`: endpoints PHP
- `src/`: posible zona de trabajo extra o archivos compartidos
- `README.md`: documentación principal

## Requisitos

- Node.js 18+
- npm
- PHP 8+
- Apache o XAMPP
- MySQL 8+

## Arranque rápido con XAMPP

1. Clona el repositorio.
2. Copia la app a tu carpeta de XAMPP (por ejemplo `C:/xampp/htdocs/musify`).
3. Crea la base de datos MySQL con el nombre `musify`.
4. Configura las variables de entorno si tu setup lo requiere.
5. Levanta Apache y MySQL en XAMPP.
6. En el frontend:

```powershell
cd frontend
npm install
npm run dev
```

La API queda accesible, por defecto, en:

`http://localhost/musify/backend/api`

Si quieres cambiar la URL base del backend, crea un archivo `.env` dentro de `frontend` con:

```env
VITE_API_URL=http://localhost/musify/backend/api
```

## Arranque con Docker

1. Crea el archivo `.env` a partir del ejemplo:

```powershell
copy .env.example .env
```

2. Levanta la pila:

```powershell
docker compose up --build
```

Para ejecutarlo en segundo plano:

```powershell
docker compose up --build -d
```

Para detenerlo:

```powershell
docker compose down
```

3. Frontend disponible en:

`http://localhost:5173`

4. Backend disponible en:

`http://localhost:8080/backend/api`

## Variables de entorno

Archivo raíz `.env.example`:

```env
APP_ENV=development
MUSIFY_DB_HOST=db
MUSIFY_DB_PORT=3306
MUSIFY_DB_NAME=musify
MUSIFY_DB_USER=musify
MUSIFY_DB_PASSWORD=musify
```

Archivo frontend `.env.example`:

```env
VITE_API_URL=http://localhost:8080/backend/api
```

## Comprobaciones locales

```powershell
cd frontend
npm ci
npm run build
cd ..
php backend/tests/smoke.php
```

El workflow de GitHub Actions ejecuta automáticamente el build de Vue, el lint de todos los archivos PHP, el smoke test y la validación de Docker Compose en cada push a `main` y en cada pull request.

## Datos y privacidad

- `database/schema.sql` contiene el esquema público.
- `database/seed.example.sql` contiene datos públicos de demostración.
- El archivo local `database/init.sql` puede contener el volcado completo de desarrollo, incluyendo usuarios, tokens o hashes, y está excluido de Git mediante `.gitignore`. No lo publiques tal cual en un repositorio público.

Para una publicación limpia, conserva el seed de ejemplo y genera datos de prueba nuevos.

## Publicar en GitHub

```powershell
git add .
git status
git commit -m "Prepare Musify portfolio project"
git branch -M main
git remote add origin https://github.com/USUARIO/musify.git
git push -u origin main
```

Sustituye `USUARIO` por tu nombre de GitHub. Comprueba `git status` antes del commit y confirma que `database/init.sql`, `.env` y cualquier contraseña no aparecen entre los archivos preparados.

## Roadmap sugerido

## Próximos pasos

- añadir capturas reales de la aplicación al README
- incorporar tests de interacción para las vistas principales
- añadir paginación y filtros de catálogo
- desplegar frontend y API en un entorno público
- sustituir las URLs de audio de demostración por recursos propios o con licencia

- tests automáticos del backend y del frontend
- CI con GitHub Actions
- Docker Compose más completo
- manejo de errores con mensajes de usuario amigables
- filtros y ordenación de catálogo
- mejoras de UX para mobile
- documentación de arquitectura y endpoints

## Licencia

Este proyecto se usa como ejemplo de desarrollo y como base para aprendizaje y portfolio profesional.
