# Frontends del CRUD de Productos

Dos frontends independientes que consumen la misma **API REST** de Symfony
(`../crud`): uno con **Vue 3** y otro con **Angular 21**.

```
crud-frontend/
├── vue/        Vue 3 + Vite + axios
└── angular/    Angular 21 (standalone) + HttpClient
```

Ambos implementan la misma funcionalidad:

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
   php -S 127.0.0.1:9000 -t public/ 
   php -S 127.0.0.1:8000 -t public public/index.php
   ```

2. **Node.js** 20.19+ (probado con Node 25.5).

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

La URL está en `angular/src/app/core/producto.service.ts`
(constante `API_URL`).

## Estructura de cada proyecto

### Vue

```
vue/src/
├── api/productoApi.js              # axios + manejo de errores
├── components/
│   ├── ConfirmModal.vue
│   ├── EstadisticasCards.vue
│   ├── ProductoFormModal.vue
│   └── ProductoTable.vue
├── App.vue                         # estado, filtros y acciones
└── style.css                       # sistema de diseño
```

### Angular

```
angular/src/app/
├── core/
│   ├── errores.ts                  # lectura de errores 422
│   ├── producto.model.ts           # interfaces de la API
│   └── producto.service.ts         # HttpClient
├── productos/
│   ├── productos.ts                # signals + formulario reactivo
│   ├── productos.html
│   └── productos.css
├── app.config.ts                   # provideHttpClient
├── app.routes.ts
└── app.ts
```

## Endpoints que consumen

| Método | Ruta |
|---|---|
| GET | `/api/productos?q=&orden=&direccion=&pagina=&porPagina=` |
| GET | `/api/productos/estadisticas` |
| GET | `/api/productos/{id}` |
| POST | `/api/productos` |
| PUT | `/api/productos/{id}` |
| DELETE | `/api/productos/{id}` |

## CORS

La API permite `localhost` y `127.0.0.1` en cualquier puerto
(`CORS_ALLOW_ORIGIN` en `../crud/.env`), así que los puertos `5173` de Vite y `4200`
de Angular funcionan sin proxy.

## Problemas frecuentes

**El dashboard muestra "Sin conexión con la API"**
Comprueba que Symfony esté corriendo en el puerto 8000 y que PostgreSQL esté arriba:

```powershell
cd ..\crud
docker compose ps
```

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
