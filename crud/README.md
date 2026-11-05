# API REST de Productos con autenticación JWT — Symfony 6.4 · PHP 8.1 · PostgreSQL 16

API REST en JSON pensada para consumirse desde **Vue 3** o **Angular** (o cualquier SPA).
Mantiene la arquitectura en capas **Controller → Service → Repository**, sin Twig ni formularios
HTML, y protege los endpoints con **JWT (Bearer token)**.

- **Base URL:** `http://127.0.0.1:8000/api`
- **Formato:** `application/json`
- **Autenticación:** `Authorization: Bearer <token>`
- **CORS:** habilitado para `localhost` / `127.0.0.1` en cualquier puerto (Vue usa 5173 y Angular 4200)
- **Datos:** 12 productos de ejemplo cargados con fixtures

## Arquitectura

```
src/
├── Controller/
│   ├── Api/
│   │   ├── AbstractApiController.php   # Deserialización JSON + validación → 422
│   │   ├── AuthController.php          # Registro y usuario autenticado
│   │   └── ProductoController.php      # Endpoints REST
│   └── HomeController.php              # GET / con el índice de la API
├── Dto/
│   ├── ProductoRequestDTO.php          # Payload de entrada + constraints
│   ├── ProductoResponseDTO.php         # Contrato JSON de salida
│   └── RegisterRequestDTO.php          # Payload de registro + constraints
├── Entity/
│   ├── Producto.php                    # Mapeo Doctrine
│   └── User.php                        # Usuario de autenticación (JWT)
├── EventListener/
│   └── ApiExceptionSubscriber.php      # Errores JSON: 404, 409, 400, 500...
├── Exception/
│   ├── ProductoDuplicadoException.php
│   └── ProductoNoEncontradoException.php
├── Mapper/
│   └── ProductoMapper.php              # DTO → Entidad → DTO de respuesta
├── Repository/
│   ├── ProductoRepository.php          # Única capa con Doctrine/QueryBuilder
│   └── UserRepository.php              # Persistencia de usuarios
├── Service/
│   ├── ProductoServiceInterface.php
│   └── ProductoService.php             # Reglas de negocio
└── DataFixtures/
    └── ProductoFixtures.php
```

La seguridad se configura en `config/packages/security.yaml` y
`config/packages/lexik_jwt_authentication.yaml`.

Flujo de una petición:

```
Frontend (axios/HttpClient + Bearer token)
        │
        ▼
Firewall JWT → ProductoController → ProductoService → ProductoRepository → PostgreSQL
                       │                    │
                ProductoRequestDTO     ProductoMapper
                       │
               Serializer + Validator
```

- El **controlador** no ejecuta SQL: valida HTTP/JSON y delega.
- El **servicio** aplica reglas (nombre único, paginación, orden seguro) y lanza excepciones de dominio.
- El **repositorio** encapsula todas las consultas.
- El **DTO de salida** fija el contrato JSON y evita exponer la entidad.

## Endpoints

### Autenticación (públicos)

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/register` | Registra un usuario y devuelve `user` + `token` |
| POST | `/api/login_check` | Valida email/contraseña y devuelve `{ "token": "..." }` |

`GET /api/me` requiere token y devuelve el usuario autenticado.

### Productos (requieren `Authorization: Bearer <token>`)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/productos` | Listado paginado, búsqueda y ordenamiento |
| GET | `/api/productos/estadisticas` | Totales para el dashboard |
| GET | `/api/productos/{id}` | Detalle |
| POST | `/api/productos` | Crear → `201 Created` + `Location` |
| PUT | `/api/productos/{id}` | Actualizar (reemplazo completo) |
| DELETE | `/api/productos/{id}` | Eliminar → `204 No Content` |

### Parámetros del listado

| Parámetro | Valores | Default |
|---|---|---|
| `q` | Texto a buscar en nombre/descripción | vacío |
| `orden` | `nombre`, `precio`, `stock`, `activo`, `creadoEn`, `actualizadoEn` | `creadoEn` |
| `direccion` | `ASC`, `DESC` | `DESC` |
| `pagina` | Entero ≥ 1 | `1` |
| `porPagina` | Entero 1–100 | `10` |

Ejemplo:

```
GET /api/productos?q=teclado&orden=precio&direccion=ASC&pagina=1&porPagina=10
```

## Payload de entrada

```json
{
  "nombre": "Teclado mecánico RGB",
  "descripcion": "Switches rojos y retroiluminación",
  "precio": 189900.00,
  "stock": 12,
  "activo": true
}
```

- `nombre`: obligatorio, 3–150 caracteres, único (case-insensitive).
- `descripcion`: opcional, máximo 2000 caracteres.
- `precio`: obligatorio, número mayor que 0.
- `stock`: obligatorio, entero ≥ 0.
- `activo`: obligatorio, booleano.

En `PUT` se envía el recurso completo.

## Respuesta de un producto

```json
{
  "id": 1,
  "nombre": "Teclado mecánico RGB",
  "descripcion": "Switches rojos y retroiluminación",
  "precio": 189900,
  "stock": 12,
  "activo": true,
  "stockBajo": false,
  "valorInventario": 2278800,
  "creadoEn": "2026-11-04T15:16:57+00:00",
  "actualizadoEn": "2026-11-04T15:16:57+00:00"
}
```

`stockBajo` es `true` cuando el stock es ≤ 5. `valorInventario = precio × stock`.

## Respuesta del listado

```json
{
  "data": [ { "id": 1, "nombre": "...", "...": "..." } ],
  "meta": {
    "total": 12,
    "pagina": 1,
    "porPagina": 10,
    "totalPaginas": 2,
    "termino": null,
    "orden": "creadoEn",
    "direccion": "DESC"
  }
}
```

## Formato de errores

**422 — validación** (errores por campo, listos para pintar en el formulario de Vue):

```json
{
  "status": 422,
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "nombre": ["El nombre es obligatorio."],
    "precio": ["El precio debe ser mayor que cero."],
    "stock": ["El stock es obligatorio."],
    "activo": ["El campo activo es obligatorio."]
  }
}
```

| Código | Caso |
|---|---|
| 400 | JSON mal formado o tipos incorrectos |
| 401 | Token ausente, expirado o inválido / credenciales incorrectas |
| 404 | Producto inexistente o ruta inexistente |
| 409 | Nombre de producto duplicado / email ya registrado |
| 422 | Validación de campos |
| 500 | Error interno (mensaje genérico en producción) |

```json
{ "status": 404, "message": "No se encontró el producto con ID 999." }
```

## Autenticación JWT

La API usa:

- `symfony/security-bundle` (`6.4.*`)
- `lexik/jwt-authentication-bundle` (`^2.18`, compatible con PHP 8.1)

Los endpoints públicos son `POST /api/register` y `POST /api/login_check`. Todo lo demás bajo
`/api` exige un token válido.

### Flujo

1. El cliente se registra (`POST /api/register`) o inicia sesión (`POST /api/login_check`).
2. La API devuelve un JWT firmado (validez por defecto: 1 hora).
3. El cliente envía el token en cada petición: `Authorization: Bearer <token>`.
4. El firewall `api` valida la firma y el usuario antes de llegar al controlador.
5. Sin token o con token inválido/expirado, la respuesta es `401 Unauthorized`.

### Registro

```http
POST /api/register
Content-Type: application/json

{
  "email": "usuario@ejemplo.com",
  "password": "Password123",
  "nombre": "Ana"
}
```

- `email`: obligatorio, formato válido, único (se normaliza a minúsculas).
- `password`: obligatorio, entre 8 y 72 caracteres. Se guarda con hash.
- `nombre`: opcional, máximo 100 caracteres.

Respuesta `201 Created`:

```json
{
  "user": {
    "id": 1,
    "email": "usuario@ejemplo.com",
    "nombre": "Ana",
    "roles": ["ROLE_USER"],
    "creadoEn": "2026-11-05T22:08:51+00:00"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

Errores: `422` si los datos no son válidos, `409` si el email ya existe.

### Login

```http
POST /api/login_check
Content-Type: application/json

{
  "email": "usuario@ejemplo.com",
  "password": "Password123"
}
```

Respuesta `200 OK`:

```json
{ "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..." }
```

Credenciales incorrectas: `401 Unauthorized`.

### Usar el token

```http
GET /api/productos
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

Sin la cabecera (o con un token inválido) la API responde `401`:

```json
{ "code": 401, "message": "JWT Token not found" }
```

### Usuario autenticado

```http
GET /api/me
Authorization: Bearer <token>
```

```json
{
  "id": 1,
  "email": "usuario@ejemplo.com",
  "nombre": "Ana",
  "roles": ["ROLE_USER"],
  "creadoEn": "2026-11-05T22:08:51+00:00"
}
```

### Configuración

`config/packages/security.yaml`:

- `password_hashers`: hash automático de contraseñas.
- `providers.app_user_provider`: carga el usuario por `email`.
- Firewall `login`: `json_login` en `/api/login_check` con los handlers de Lexik.
- Firewall `api`: `jwt: ~` para todo `^/api`.
- `access_control`: deja públicos el registro, el login y el preflight `OPTIONS`; el resto exige
  `ROLE_USER`.

`config/packages/lexik_jwt_authentication.yaml` apunta a las claves:

```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
```

Variables en `.env`:

```env
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=<frase-secreta>
```

Las claves `config/jwt/*.pem` están en `.gitignore`; cada entorno debe generarlas. El
`JWT_PASSPHRASE` debe coincidir con la frase usada al generarlas.

### Frontends

Los frontends actuales (`../vue` y `../angular`) todavía no implementan login ni envían el
header `Authorization`, por lo que recibirán `401` hasta que se les añada:

1. Pantalla de login/registro.
2. Almacenamiento del token (`localStorage`, Pinia o un signal).
3. Interceptor HTTP que añada `Authorization: Bearer <token>`.

El CORS ya permite el header `Authorization`, no hay que cambiar Nelmio.

## Puesta en marcha

```powershell
cd C:\Users\arnal\OneDrive\Escritorio\crud-frontend\crud

# 1. Dependencias
composer install

# 2. PostgreSQL en Docker
docker compose up -d

# 3. Esquema y datos de ejemplo
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# 4. Claves JWT (solo la primera vez o si borras config/jwt)
$env:OPENSSL_CONF='C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\extras\ssl\openssl.cnf'
php bin/console lexik:jwt:generate-keypair

# 5. Servidor
php -S 127.0.0.1:8000 -t public public/index.php
```

> En Windows con Laragon es necesario `OPENSSL_CONF`: sin esa variable PHP no encuentra
> `openssl.cnf` y el comando de generación falla.
>
> El puerto `8000` debe estar libre. Si otro servicio lo ocupa (por ejemplo WordPress), usa otro
> puerto y ajusta la URL de los frontends.

Comprobación rápida:

```powershell
# 1. Registrar un usuario (o usar uno existente)
$body = @{
  email = 'demo@ejemplo.com'
  password = 'Password123'
  nombre = 'Demo'
} | ConvertTo-Json

$registro = Invoke-RestMethod -Method Post `
  -Uri http://127.0.0.1:8000/api/register `
  -ContentType 'application/json' -Body $body

# 2. Consumir un endpoint protegido con el token devuelto
Invoke-RestMethod http://127.0.0.1:8000/api/productos `
  -Headers @{ Authorization = "Bearer $($registro.token)" }
```

> El contenedor usa el puerto **55432** porque el 5432 estaba ocupado por un PostgreSQL de WSL.
> Credenciales: usuario `app`, contraseña `app`, base `app`.

## Consumo desde Vue

En `docs/vue/productoApi.js` hay un cliente listo para copiar. Necesitas `axios`:

```powershell
npm install axios
```

Como la API está protegida, el cliente debe guardar el token del login y enviarlo en cada
petición. Puedes configurar axios así:

```js
// src/api/productoApi.js
import axios from 'axios'

const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api',
  headers: {
    Accept: 'application/json',
  },
})

// Adjunta el token guardado tras el login a todas las peticiones
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})
```

Login y uso del cliente existente:

```js
import axios from 'axios'
import { productoApi, mensajeDeError } from './api/productoApi'

// 1. Login: guarda el token
const { data } = await axios.post('http://127.0.0.1:8000/api/login_check', {
  email: 'usuario@ejemplo.com',
  password: 'Password123',
})
localStorage.setItem('token', data.token)

// 2. El interceptor añade Authorization automáticamente
const { data: respuesta, meta } = await productoApi.listar({
  q: busqueda.value,
  pagina: 1,
  porPagina: 10,
  orden: 'nombre',
  direccion: 'ASC',
})

try {
  await productoApi.crear({
    nombre: 'Teclado mecánico',
    descripcion: 'Switches rojos',
    precio: 189900,
    stock: 12,
    activo: true,
  })
} catch (error) {
  alert(mensajeDeError(error)) // muestra los errores por campo del 422
}
```

Crea un `.env` en el proyecto Vue si quieres cambiar la URL:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

### CORS

La configuración está en `config/packages/nelmio_cors.yaml` y la expresión regular de orígenes
permitidos en `.env`:

```env
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

Permite los dev servers de Vue (`http://localhost:5173`) y Angular (`http://localhost:4200`) sin
proxy. Si tu frontend vive en otro dominio, ajusta esa variable. También se expone la cabecera
`Location` para leer el recurso recién creado y se permite el header `Authorization` para el JWT.

## Ejemplos rápidos

```powershell
# 0. Obtener el token (registro o login)
$login = @{
  email = 'demo@ejemplo.com'
  password = 'Password123'
} | ConvertTo-Json

$auth = Invoke-RestMethod -Method Post `
  -Uri http://127.0.0.1:8000/api/login_check `
  -ContentType 'application/json' -Body $login

$headers = @{ Authorization = "Bearer $($auth.token)" }

# Crear
$body = @{
  nombre = 'Monitor 24"'
  descripcion = 'Full HD'
  precio = 699900
  stock = 8
  activo = $true
} | ConvertTo-Json

Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8000/api/productos `
  -ContentType 'application/json' -Headers $headers -Body $body

# Actualizar
Invoke-RestMethod -Method Put -Uri http://127.0.0.1:8000/api/productos/1 `
  -ContentType 'application/json' -Headers $headers -Body $body

# Eliminar
Invoke-WebRequest -Method Delete -Uri http://127.0.0.1:8000/api/productos/1 `
  -Headers $headers
```

También tienes peticiones listas en `docs/api.http` (VS Code REST Client / JetBrains HTTP Client).

## Tests

```powershell
php bin/phpunit
```

- `tests/Service/ProductoServiceTest.php`: unidad del servicio con repositorio mockeado.
- `tests/Controller/ApiTestCase.php`: base con esquema SQLite (`var/test.db`) y helpers para crear
  usuarios y generar tokens.
- `tests/Controller/ProductoApiTest.php`: API completa autenticada con JWT.
- `tests/Controller/AuthApiTest.php`: registro, login, email duplicado, validación 422, `/api/me`,
  401 sin token y 401 con token inválido.

Cubre listado, búsqueda, paginación, creación, validación 422, duplicado 409, actualización,
eliminación 204, 404, estadísticas, JSON inválido 400, preflight CORS y autenticación JWT.

Resultado: **25 tests, 103 assertions**.

## Comandos útiles

```powershell
php bin/console debug:router
php bin/console debug:config security
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
php bin/console cache:clear
php bin/console lexik:jwt:generate-keypair

# PostgreSQL dentro del contenedor
docker exec -it crud_postgres psql -U app -d app
```

## Notas de diseño

- **API stateless:** los firewalls `api` y `login` no usan sesión; la identidad viaja en el JWT.
- **JWT:** emitido por Lexik con clave RSA; por defecto caduca en 1 hora. Sin token o con firma
  inválida → `401`.
- **Contraseñas:** se guardan con el hasher automático de Symfony y nunca se serializan.
- **Email único:** índice único en PostgreSQL + comprobación en el registro → `409`.
- **PUT completo:** no se admite actualización parcial; si la necesitas, añade `PATCH` con un DTO parcial.
- **Precio** se guarda como `DECIMAL(10,2)` y se expone como número JSON.
- **Fechas** en ISO 8601 (`DateTimeInterface::ATOM`).
- **Ordenamiento** con lista blanca de columnas para evitar inyección.
- **Errores** centralizados en `ApiExceptionSubscriber`.

## Adaptarlo a otro dominio

1. Cambia `Producto` por tu entidad y genera la migración.
2. Renombra `ProductoRequestDTO`, `ProductoResponseDTO`, `ProductoMapper`, `ProductoRepository`,
   `ProductoService(Interface)` y `ProductoController`.
3. Ajusta las reglas del servicio (por ejemplo, documento único de un empleado).
4. Actualiza las rutas `/api/...`, las fixtures y los tests.
5. Mantén el controlador sin acceso directo al repositorio.
