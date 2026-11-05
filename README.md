# Frontends del CRUD de Productos con login JWT

Dos frontends independientes que consumen la misma **API REST** de Symfony
(`../crud`): uno con **Vue 3** y otro con **Angular 21**. Ambos exigen iniciar
sesión con **JWT** antes de mostrar el CRUD.

```
crud-frontend/
├── vue/        Vue 3 + Vite + axios + Pinia
└── angular/    Angular 21 (standalone) + HttpClient + signals
```

Ambos implementan la misma funcionalidad:

- Pantalla de **login y registro** conectada a `/api/login_check` y `/api/register`.
- Sesión persistida en `localStorage`, restaurada al recargar la página.
- Interceptor HTTP que añade `Authorization: Bearer <token>`.
- Cierre de sesión y expulsión automática al login cuando la API responde `401`.
- Dashboard con total de productos, activos, stock total y valor del inventario.
- Listado paginado con búsqueda por nombre/descripción y ordenamiento.
- Crear, editar y eliminar productos con confirmación.
- Validación en el cliente y muestra de los errores `422` por campo que devuelve la API.
- Estados de carga, tablas vacías, notificaciones y aviso si la API no está corriendo.
- Diseño responsive con el color de marca de cada framework.

## Requisitos

1. **API Symfony** en `http://127.0.0.1:8000`. Desde `../crud`:

   ```powershell
   docker compose up -d
   php bin/console doctrine:migrations:migrate --no-interaction
   php bin/console doctrine:fixtures:load --no-interaction
   php bin/console lexik:jwt:generate-keypair   # solo la primera vez
   php -S 127.0.0.1:8000 -t public public/index.php
   ```

   Si el puerto `8000` está ocupado, arranca Symfony en otro puerto y ajusta la
   URL de los frontends (ver más abajo).

2. **Node.js** 20.19+ (probado con Node 25.5).

3. Un **usuario registrado**. Puedes crear el primero desde la pestaña
   **Crear cuenta** de cualquiera de los dos frontends.

## Arrancar Vue 3

```powershell
cd vue
npm install
npm run dev
```

Abre **http://localhost:5173**.

La URL de la API se toma de `VITE_API_URL`; si no existe, usa
`http://127.0.0.1:8000/api`. Hay un ejemplo en `vue/.env.example`.

## Arrancar Angular

```powershell
cd angular
npm install
npm start
```

Abre **http://localhost:4200**.

La URL está en `angular/src/app/core/api.ts` (constante `API_URL`).

## Cómo funciona el login

1. Al abrir la aplicación, si no hay token se muestra la tarjeta de acceso.
2. **Iniciar sesión** llama a `POST /api/login_check`; si las credenciales son
   correctas guarda el token y consulta `GET /api/me` para obtener el usuario.
3. **Crear cuenta** llama a `POST /api/register`, que devuelve el usuario y el
   token ya listos, así que entra directamente al CRUD.
4. Cada petición a `/api` lleva `Authorization: Bearer <token>` mediante un
   interceptor de axios (Vue) o `HttpInterceptorFn` (Angular).
5. Si la API responde `401`, se borra la sesión y se vuelve a la pantalla de login.
6. El botón **Salir** elimina el token y el usuario de `localStorage`.

### Vue + Pinia

- `src/stores/auth.js`: estado (`token`, `usuario`, `cargando`, `error`) y acciones
  `iniciarSesion`, `registrar`, `restaurarSesion` y `cerrarSesion`.
- `src/api/http.js`: instancia axios compartida con interceptor de petición y de respuesta.
- `src/components/AuthView.vue`: formulario de login/registro con validación.
- `App.vue` muestra `AuthView` o el CRUD según `auth.autenticado`.

### Angular + signals

- `core/auth.service.ts`: signals `token`, `usuario` y `autenticado`, más los métodos
  `iniciarSesion`, `registrar`, `restaurarSesion`, `cerrarSesion`.
- `core/auth.interceptor.ts`: añade el Bearer y gestiona el `401`.
- `core/auth.guard.ts`: `authGuard` protege `/` y `invitadoGuard` evita volver al login
  estando autenticado.
- `app/auth/login/`: componente standalone de login/registro.
- `app.config.ts` registra el interceptor y un `provideAppInitializer` que valida el
  token guardado al arrancar.

## Estructura de cada proyecto

### Vue

```
vue/src/
├── api/
│   ├── http.js                     # axios compartido + interceptores JWT
│   ├── authApi.js                  # login, registro y /me
│   ├── errores.js                  # mensajes de error de la API
│   └── productoApi.js              # listar/crear/actualizar/eliminar
├── stores/auth.js                  # Pinia: sesión y acciones de auth
├── components/
│   ├── AuthView.vue                # login y registro
│   ├── ConfirmModal.vue
│   ├── EstadisticasCards.vue
│   ├── ProductoFormModal.vue
│   └── ProductoTable.vue
├── App.vue                         # gate de sesión + estado del CRUD
├── main.js                         # createApp + Pinia
└── style.css                       # sistema de diseño
```

### Angular

```
angular/src/app/
├── core/
│   ├── api.ts                      # URL base de la API
│   ├── sesion.ts                   # tipos y persistencia en localStorage
│   ├── auth.service.ts             # signals de sesión + login/registro
│   ├── auth.interceptor.ts         # Bearer + manejo de 401
│   ├── auth.guard.ts               # authGuard e invitadoGuard
│   ├── errores.ts                  # lectura de errores 422
│   ├── producto.model.ts           # interfaces de la API
│   └── producto.service.ts         # HttpClient
├── auth/login/
│   ├── login.ts                    # formulario reactivo + signals
│   ├── login.html
│   └── login.css
├── productos/
│   ├── productos.ts                # signals + formulario reactivo
│   ├── productos.html
│   └── productos.css
├── app.config.ts                   # HttpClient + interceptor + inicializador
├── app.routes.ts                   # /login público y / protegido
└── app.ts
```

## Endpoints que consumen

| Método | Ruta | Auth |
|---|---|---|
| POST | `/api/register` | pública |
| POST | `/api/login_check` | pública |
| GET | `/api/me` | Bearer |
| GET | `/api/productos?q=&orden=&direccion=&pagina=&porPagina=` | Bearer |
| GET | `/api/productos/estadisticas` | Bearer |
| GET | `/api/productos/{id}` | Bearer |
| POST | `/api/productos` | Bearer |
| PUT | `/api/productos/{id}` | Bearer |
| DELETE | `/api/productos/{id}` | Bearer |

## CORS

La API permite `localhost` y `127.0.0.1` en cualquier puerto
(`CORS_ALLOW_ORIGIN` en `../crud/.env`), así que los puertos `5173` de Vite y `4200`
de Angular funcionan sin proxy. También está permitido el header `Authorization`
que envía el JWT.

## Problemas frecuentes

**El dashboard muestra "Sin conexión con la API"**
Comprueba que Symfony esté corriendo en el puerto 8000 y que PostgreSQL esté arriba:

```powershell
cd ..\crud
docker compose ps
```

**La API responde 401 o me devuelve al login**
El token caduca en 1 hora o las claves JWT no coinciden. Vuelve a iniciar sesión.
Si acabas de clonar el backend, genera las claves con:

```powershell
cd ..\crud
php bin/console lexik:jwt:generate-keypair
```

**No existe ningún usuario**
Regístrate desde la pestaña **Crear cuenta**. El primer registro crea el usuario
con rol `ROLE_USER` y devuelve el token automáticamente.

**Error de credenciales en PostgreSQL**
La base del proyecto es `app` / usuario `app` / contraseña `app` en el puerto
**55432** (no 5432, que pertenece a WSL).

## Compilar para producción

```powershell
cd vue
npm run build

cd ..\angular
npm run build
```
