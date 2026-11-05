# CRUD de Productos · Angular 21

Frontend en **Angular 21 standalone** que consume la API REST de Symfony en
`http://127.0.0.1:8000/api`.

## Arrancar

```powershell
npm install
npm start
```

Abre http://localhost:4200.

## Configurar la API

La URL está en `src/app/core/producto.service.ts`:

```ts
const API_URL = 'http://127.0.0.1:8000/api/productos';
```

## Estructura

```
src/app/
├── core/
│   ├── errores.ts              # parseo de errores 422/409
│   ├── producto.model.ts       # interfaces tipadas de la API
│   └── producto.service.ts     # HttpClient
├── productos/
│   ├── productos.ts            # signals, filtros y formulario reactivo
│   ├── productos.html          # tabla, dashboard y modales
│   └── productos.css
├── app.config.ts               # provideHttpClient(withFetch())
├── app.routes.ts               # carga perezosa del feature
├── app.ts
└── app.html
```

## Funcionalidad

- Dashboard con estadísticas de la API.
- Búsqueda con debounce, orden, dirección y paginación.
- Formulario reactivo para crear/editar con validación.
- Errores por campo del `422` y mensaje de duplicado `409`.
- Eliminación con confirmación.
- Toasts, esqueletos de carga y aviso de API caída.
- Componente `OnPush` basado en signals (sin Zone.js).

## Build y tests

```powershell
npm run build
npm test
```
