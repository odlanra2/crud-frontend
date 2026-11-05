# CRUD de Productos · Angular 21

Frontend en **Angular 21 standalone** que consume la API REST de Symfony en
`http://127.0.0.1:8000/api`. Incluye login y registro con **JWT** basados en
signals: el CRUD solo se muestra después de autenticarse.

## Arrancar

```powershell
npm install
npm start
```

Abre http://localhost:4200.

## Configurar la API

La URL está en `src/app/core/api.ts`:

```ts
export const API_URL = 'http://127.0.0.1:8000/api';
```

## Autenticación

- `core/auth.service.ts` mantiene `token`, `usuario` y `autenticado` como signals,
  persiste la sesión en `localStorage` y expone login, registro, restauración y logout.
- `core/auth.interceptor.ts` añade `Authorization: Bearer <token>` y, ante un `401`,
  limpia la sesión y navega a `/login`.
- `core/auth.guard.ts` protege la ruta `/` (`authGuard`) y evita abrir `/login`
  con sesión activa (`invitadoGuard`).
- `auth/login/` es el componente standalone con formulario reactivo.
- `app.config.ts` registra el interceptor y valida el token al arrancar mediante
  `provideAppInitializer`.

Si no tienes usuario, usa **Crear cuenta** en la propia pantalla de acceso.

## Estructura

```
src/app/
├── core/
│   ├── api.ts                  # URL base de la API
│   ├── sesion.ts               # tipos Usuario y persistencia en localStorage
│   ├── auth.service.ts         # signals de sesión + login/registro
│   ├── auth.interceptor.ts     # Bearer + manejo de 401
│   ├── auth.guard.ts           # authGuard e invitadoGuard
│   ├── errores.ts              # parseo de errores 422/409
│   ├── producto.model.ts       # interfaces tipadas de la API
│   └── producto.service.ts     # HttpClient
├── auth/login/
│   ├── login.ts                # formulario reactivo + signals
│   ├── login.html
│   └── login.css
├── productos/
│   ├── productos.ts            # signals, filtros y formulario reactivo
│   ├── productos.html          # tabla, dashboard y modales
│   └── productos.css
├── app.config.ts               # HttpClient + interceptor + inicializador
├── app.routes.ts               # /login público y / protegido
├── app.ts
└── app.html
```

## Funcionalidad

- Login y registro con JWT, sesión persistente y cierre de sesión.
- Expulsión automática al login cuando el token caduca (`401`).
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
