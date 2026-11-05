# CRUD de Productos · Vue 3

Frontend en **Vue 3 + Vite** que consume la API REST de Symfony en
`http://127.0.0.1:8000/api`.

## Arrancar

```powershell
npm install
npm run dev
```

Abre http://localhost:5173.

## Configurar la API

Por defecto usa `http://127.0.0.1:8000/api`. Para cambiarla, crea un `.env`:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

## Estructura

```
src/
├── api/productoApi.js              # axios, listar/crear/actualizar/eliminar
├── components/
│   ├── ConfirmModal.vue            # confirmación de borrado
│   ├── EstadisticasCards.vue       # tarjetas del dashboard
│   ├── ProductoFormModal.vue       # alta y edición + validación
│   └── ProductoTable.vue           # tabla, badges y esqueletos
├── App.vue                         # estado, filtros, paginación y toasts
├── main.js
└── style.css                       # tokens y estilos base
```

## Funcionalidad

- Búsqueda con debounce, orden y dirección.
- Paginación y selector de tamaño de página.
- Crear/editar en modal; errores del `422` mostrados por campo.
- Eliminación con confirmación.
- Estadísticas actualizadas tras cada cambio.
- Aviso claro si la API no responde.

## Build

```powershell
npm run build
npm run preview
```
