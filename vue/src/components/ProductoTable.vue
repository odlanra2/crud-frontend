<script setup>
defineProps({
  productos: {
    type: Array,
    default: () => [],
  },
  cargando: Boolean,
})

defineEmits(['editar', 'eliminar'])

const formatoNumero = new Intl.NumberFormat('es-CO')
const formatoMoneda = new Intl.NumberFormat('es-CO', {
  style: 'currency',
  currency: 'COP',
  maximumFractionDigits: 0,
})
const formatoFecha = new Intl.DateTimeFormat('es-CO', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
})

function fecha(iso) {
  return formatoFecha.format(new Date(iso))
}
</script>

<template>
  <div class="tabla-envoltura">
    <table class="tabla" :class="{ 'tabla--recargando': cargando && productos.length > 0 }">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Estado</th>
          <th>Inventario</th>
          <th>Actualizado</th>
          <th class="acciones-col">Acciones</th>
        </tr>
      </thead>

      <tbody>
        <template v-if="cargando && productos.length === 0">
          <tr v-for="fila in 5" :key="`esqueleto-${fila}`" class="fila-esqueleto">
            <td><span class="esqueleto" style="width: 70%"></span><span class="esqueleto esqueleto--corto"></span></td>
            <td><span class="esqueleto" style="width: 70%"></span></td>
            <td><span class="esqueleto" style="width: 40%"></span></td>
            <td><span class="esqueleto" style="width: 60%"></span></td>
            <td><span class="esqueleto" style="width: 70%"></span></td>
            <td><span class="esqueleto" style="width: 60%"></span></td>
            <td><span class="esqueleto" style="width: 50%"></span></td>
          </tr>
        </template>

        <tr v-else-if="productos.length === 0">
          <td colspan="7" class="vacio">
            <strong>No hay productos para mostrar</strong>
            <span>Prueba con otra búsqueda o crea tu primer producto.</span>
          </td>
        </tr>

        <template v-else>
          <tr v-for="producto in productos" :key="producto.id">
          <td>
            <div class="producto-celda">
              <strong>{{ producto.nombre }}</strong>
              <span :title="producto.descripcion ?? ''">
                {{ producto.descripcion || 'Sin descripción' }}
              </span>
            </div>
          </td>
          <td class="numero precio">{{ formatoMoneda.format(producto.precio) }}</td>
          <td>
            <span v-if="producto.stock === 0" class="badge badge--peligro">Sin stock</span>
            <span v-else-if="producto.stockBajo" class="badge badge--aviso">
              Bajo · {{ formatoNumero.format(producto.stock) }}
            </span>
            <span v-else class="numero">{{ formatoNumero.format(producto.stock) }}</span>
          </td>
          <td>
            <span class="badge" :class="producto.activo ? 'badge--ok' : 'badge--apagado'">
              {{ producto.activo ? 'Activo' : 'Inactivo' }}
            </span>
          </td>
          <td class="numero">{{ formatoMoneda.format(producto.valorInventario) }}</td>
          <td class="fecha">{{ fecha(producto.actualizadoEn) }}</td>
          <td>
            <div class="acciones">
              <button
                type="button"
                class="accion"
                :title="`Editar ${producto.nombre}`"
                :aria-label="`Editar ${producto.nombre}`"
                @click="$emit('editar', producto)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 20h9" />
                  <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                </svg>
              </button>

              <button
                type="button"
                class="accion accion--peligro"
                :title="`Eliminar ${producto.nombre}`"
                :aria-label="`Eliminar ${producto.nombre}`"
                @click="$emit('eliminar', producto)"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 6h18" />
                  <path d="M8 6V4h8v2" />
                  <path d="M19 6l-1 14H6L5 6" />
                  <path d="M10 11v5M14 11v5" />
                </svg>
              </button>
            </div>
          </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.tabla-envoltura {
  overflow-x: auto;
}

.tabla {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.tabla th {
  text-align: left;
  padding: 12px 18px;
  font-size: 11.5px;
  letter-spacing: 0.4px;
  text-transform: uppercase;
  color: var(--texto-suave);
  font-weight: 700;
  background: #f8fafc;
  border-bottom: 1px solid var(--borde);
  white-space: nowrap;
}

.tabla td {
  padding: 14px 18px;
  border-bottom: 1px solid var(--borde);
  vertical-align: middle;
}

.tabla tbody tr:last-child td {
  border-bottom: 0;
}

.tabla tbody tr {
  transition: background 0.15s ease;
}

.tabla tbody tr:hover {
  background: #f8fafc;
}

.tabla--recargando {
  opacity: 0.55;
  pointer-events: none;
}

.producto-celda {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 190px;
}

.producto-celda strong {
  font-weight: 600;
}

.producto-celda span {
  font-size: 12.5px;
  color: var(--texto-suave);
  max-width: 320px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.numero {
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.precio {
  font-weight: 600;
}

.fecha {
  color: var(--texto-suave);
  font-size: 13px;
  white-space: nowrap;
}

.acciones-col {
  text-align: right;
}

.acciones {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
}

.accion {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 8px;
  border: 1px solid var(--borde);
  background: #fff;
  cursor: pointer;
  color: var(--texto-suave);
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.accion:hover {
  border-color: #cbd5e1;
  color: var(--texto);
  background: #f8fafc;
}

.accion--peligro:hover {
  border-color: #fecaca;
  color: var(--peligro);
  background: var(--peligro-suave);
}

.vacio {
  padding: 46px 20px;
  text-align: center;
  color: var(--texto-suave);
  font-size: 13.5px;
}

.vacio strong {
  display: block;
  color: var(--texto);
  font-size: 15px;
  margin-bottom: 4px;
}

.fila-esqueleto td {
  padding: 16px 18px;
}

.esqueleto {
  display: block;
  height: 11px;
  border-radius: 6px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 37%, #f1f5f9 63%);
  background-size: 400% 100%;
  animation: brillo 1.3s ease infinite;
}

.esqueleto--corto {
  width: 45%;
  margin-top: 7px;
}

@keyframes brillo {
  0% {
    background-position: 100% 0;
  }
  100% {
    background-position: 0 0;
  }
}

@media (max-width: 900px) {
  .tabla th:nth-child(6),
  .tabla td:nth-child(6) {
    display: none;
  }
}
</style>
