# Vue 3 + Vite

This template should help get you started developing with Vue 3 in Vite. The template uses Vue 3 `<script setup>` SFCs, check out the [script setup docs](https://v3.vuejs.org/api/sfc-script-setup.html#sfc-script-setup) to learn more.

Learn more about IDE Support for Vue in the [Vue Docs Scaling up Guide](https://vuejs.org/guide/scaling-up/tooling.html#ide-support).
# Musify

## Estructura

- `frontend/`: aplicación Vue 3 + Vite.
- `backend/api/`: API PHP y conexión con MySQL.

## Arrancar el frontend

```powershell
cd frontend
npm install
npm run dev
```

La API se sirve con Apache y queda disponible en:

`http://localhost/musify/backend/api/`

El frontend usa esa ruta automáticamente. Para cambiarla, define `VITE_API_URL` antes de ejecutar Vite.
