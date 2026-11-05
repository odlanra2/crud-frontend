# API REST de Productos — Symfony 6.4 · PHP 8.1 · PostgreSQL 16

API REST en JSON pensada para consumirse desde **Vue 3** (o cualquier SPA). Mantiene la
arquitectura en capas **Controller → Service → Repository**, sin Twig ni formularios HTML.

- **Base URL:** `http://127.0.0.1:8000/api`
- **Formato:** `application/json`
- **CORS:** habilitado para `localhost` / `127.0.0.1` en cualquier puerto (Vue usa 5173 por defecto)
- **Datos:** 12 productos de ejemplo cargados con fixtures

## Arquitectura

```
src/
├── Controller/
│   ├── Api/
│   │   ├── AbstractApiController.php   # Deserialización JSON + validación → 422
│   │   └── ProductoController.php      # Endpoints REST
│   └── HomeController.php              # GET / con el índice de la API
├── Dto/
│   ├── ProductoRequestDTO.php          # Payload de entrada + constraints
│   └── ProductoResponseDTO.php         # Contrato JSON de salida
├── Entity/
│   └── Producto.php                    # Mapeo Doctrine
├── EventListener/
│   └── ApiExceptionSubscriber.php      # Errores JSON: 404, 409, 400, 500...
├── Exception/
│   ├── ProductoDuplicadoException.php
│   └── ProductoNoEncontradoException.php
├── Mapper/
│   └── ProductoMapper.php              # DTO → Entidad → DTO de respuesta
├── Repository/
│   └── ProductoRepository.php          # Única capa con Doctrine/QueryBuilder
├── Service/
│   ├── ProductoServiceInterface.php
│   └── ProductoService.php             # Reglas de negocio
└── DataFixtures/
    └── ProductoFixtures.php
```

Flujo de una petición:

```
Vue (axios) → ProductoController → ProductoService → ProductoRepository → PostgreSQL
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
| 404 | Producto inexistente o ruta inexistente |
| 409 | Nombre de producto duplicado |
| 422 | Validación de campos |
| 500 | Error interno (mensaje genérico en producción) |

```json
{ "status": 404, "message": "No se encontró el producto con ID 999." }
```

## Puesta en marcha

```powershell
cd C:\Users\arnal\OneDrive\Escritorio\crud

# 1. Dependencias
composer install

# 2. PostgreSQL en Docker
docker compose up -d

# 3. Esquema y datos de ejemplo
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# 4. Servidor
php -S 127.0.0.1:8000 -t public public/index.php
```

Comprobación rápida:

```powershell
Invoke-RestMethod http://127.0.0.1:8000/api/productos
```

> El contenedor usa el puerto **55432** porque el 5432 estaba ocupado por un PostgreSQL de WSL.
> Credenciales: usuario `app`, contraseña `app`, base `app`.

## Consumo desde Vue

En `docs/vue/productoApi.js` hay un cliente listo para copiar. Necesitas `axios`:

```powershell
npm install axios
```

```js
import { productoApi, mensajeDeError } from './api/productoApi'

const { data, meta } = await productoApi.listar({
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

Permite el dev server de Vue (`http://localhost:5173`) sin proxy. Si tu frontend vive en otro
dominio, ajusta esa variable. También se expone la cabecera `Location` para leer el recurso recién
creado.

## Ejemplos rápidos

```powershell
# Crear
$body = @{
  nombre = 'Monitor 24"'
  descripcion = 'Full HD'
  precio = 699900
  stock = 8
  activo = $true
} | ConvertTo-Json

Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8000/api/productos `
  -ContentType 'application/json' -Body $body

# Actualizar
Invoke-RestMethod -Method Put -Uri http://127.0.0.1:8000/api/productos/1 `
  -ContentType 'application/json' -Body $body

# Eliminar
Invoke-WebRequest -Method Delete -Uri http://127.0.0.1:8000/api/productos/1
```

También tienes peticiones listas en `docs/api.http` (VS Code REST Client / JetBrains HTTP Client).

## Tests

```powershell
php bin/phpunit
```

- `tests/Service/ProductoServiceTest.php`: unidad del servicio con repositorio mockeado.
- `tests/Controller/ProductoApiTest.php`: API completa con SQLite (`var/test.db`), sin depender de PostgreSQL.

Cubre listado, búsqueda, paginación, creación, validación 422, duplicado 409, actualización,
eliminación 204, 404, estadísticas, JSON inválido 400 y preflight CORS.

Resultado: **17 tests, 74 assertions**.

## Comandos útiles

```powershell
php bin/console debug:router
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
php bin/console cache:clear

# PostgreSQL dentro del contenedor
docker exec -it crud_postgres psql -U app -d app
```

## Notas de diseño

- **Sin estado / sin CSRF:** API pensada para tokens. Si añades autenticación por cookies, activa CSRF.
- **Sin autenticación todavía:** para producción añade JWT (LexikJWTAuthenticationBundle) o API keys.
- **PUT completo:** no se admite actualización parcial; si la necesitas, añade `PATCH` con un DTO parcial.
- **Precio** se guarda como `DECIMAL(10,2)` y se expone como número JSON.
- **Fechas** en ISO 8601 (`DateTimeInterface::ATOM`).
- **Nombre único:** índice único en PostgreSQL + comprobación en el servicio → `409`.
- **Ordenamiento** con lista blanca de columnas para evitar inyección.
- **Errores** centralizados en `ApiExceptionSubscriber`.

## Adaptarlo a otro dominio

1. Cambia `Producto` por tu entidad y genera la migración.
2. Renombra `ProductoRequestDTO`, `ProductoResponseDTO`, `ProductoMapper`, `ProductoRepository`,
   `ProductoService(Interface)` y `ProductoController`.
3. Ajusta las reglas del servicio (por ejemplo, documento único de un empleado).
4. Actualiza las rutas `/api/...`, las fixtures y los tests.
5. Mantén el controlador sin acceso directo al repositorio.
