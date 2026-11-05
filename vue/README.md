# CRUD de Productos · Vue 3

Frontend en **Vue 3 + Vite + Pinia** que consume la API REST de Symfony en
`http://127.0.0.1:8000/api`. Incluye login y registro con **JWT**: el CRUD solo
se muestra después de autenticarse.

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

## Autenticación

- `src/stores/auth.js` (Pinia) guarda `token` y `usuario` en `localStorage`
  y expone `iniciarSesion`, `registrar`, `restaurarSesion` y `cerrarSesion`.
- `src/api/http.js` es la instancia axios compartida: añade
  `Authorization: Bearer <token>` y avisa cuando la API responde `401`.
- `src/components/AuthView.vue` contiene las pestañas de login y registro.
- `App.vue` muestra `AuthView` o el CRUD según `auth.autenticado`; al recargar
  valida el token contra `/api/me`.

Si no tienes usuario, usa **Crear cuenta** en la propia pantalla de acceso.

## Estructura

```
src/
├── api/
│   ├── http.js                     # axios + interceptores JWT
│   ├── authApi.js                  # /login_check, /register y /me
│   ├── errores.js                  # mensajes de error de la API
│   └── productoApi.js              # listar/crear/actualizar/eliminar
├── stores/auth.js                  # estado global de sesión con Pinia
├── components/
│   ├── AuthView.vue                # login y registro
│   ├── ConfirmModal.vue            # confirmación de borrado
│   ├── EstadisticasCards.vue       # tarjetas del dashboard
│   ├── ProductoFormModal.vue       # alta y edición + validación
│   └── ProductoTable.vue           # tabla, badges y esqueletos
├── App.vue                         # gate de sesión + estado del CRUD
├── main.js                         # createApp + Pinia
└── style.css                       # tokens y estilos base
```

## Funcionalidad

- Login y registro con JWT, sesión persistente y cierre de sesión.
- Expulsión automática al login cuando el token caduca (`401`).
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
