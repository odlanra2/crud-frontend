<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { erroresDeCampo, mensajeDeError, productoApi } from './api/productoApi'
import AuthView from './components/AuthView.vue'
import ConfirmModal from './components/ConfirmModal.vue'
import EstadisticasCards from './components/EstadisticasCards.vue'
import ProductoFormModal from './components/ProductoFormModal.vue'
import ProductoTable from './components/ProductoTable.vue'
import { useAuthStore } from './stores/auth'

const auth = useAuthStore()

const opcionesOrden = [
  { valor: 'creadoEn', texto: 'Más recientes' },
  { valor: 'nombre', texto: 'Nombre' },
  { valor: 'precio', texto: 'Precio' },
  { valor: 'stock', texto: 'Stock' },
  { valor: 'activo', texto: 'Estado' },
  { valor: 'actualizadoEn', texto: 'Actualizados' },
]

const tamanosPagina = [5, 10, 25, 50]

const productos = ref([])
const meta = ref({ total: 0, pagina: 1, porPagina: 10, totalPaginas: 0 })
const estadisticas = ref(null)
const cargando = ref(true)
const errorConexion = ref('')
const filtros = reactive({
  q: '',
  orden: 'creadoEn',
  direccion: 'DESC',
  pagina: 1,
  porPagina: 10,
})

const modalAbierto = ref(false)
const productoEditando = ref(null)
const guardando = ref(false)
const erroresServidor = ref({})
const productoAEliminar = ref(null)
const eliminando = ref(false)
const toasts = ref([])

let temporizadorBusqueda = null
let peticionActual = 0

const totalPaginas = computed(() => meta.value.totalPaginas ?? 0)
const desde = computed(() =>
  meta.value.total === 0 ? 0 : (meta.value.pagina - 1) * meta.value.porPagina + 1,
)
const hasta = computed(() => Math.min(meta.value.pagina * meta.value.porPagina, meta.value.total))

const paginasVisibles = computed(() => {
  const total = totalPaginas.value
  if (total <= 1) return []

  const inicio = Math.max(1, Math.min(filtros.pagina - 2, total - 4))
  const fin = Math.min(total, inicio + 4)

  return Array.from({ length: fin - inicio + 1 }, (_, indice) => inicio + indice)
})

const mensajeEliminacion = computed(() =>
  productoAEliminar.value
    ? `Se eliminará "${productoAEliminar.value.nombre}". Esta acción no se puede deshacer.`
    : '',
)

async function cargar() {
  const peticion = ++peticionActual
  cargando.value = true

  try {
    const respuesta = await productoApi.listar({
      q: filtros.q.trim(),
      orden: filtros.orden,
      direccion: filtros.direccion,
      pagina: filtros.pagina,
      porPagina: filtros.porPagina,
    })

    if (peticion !== peticionActual) return

    productos.value = respuesta.data
    meta.value = respuesta.meta
    errorConexion.value = ''
  } catch (error) {
    if (peticion !== peticionActual) return

    productos.value = []
    meta.value = { total: 0, pagina: 1, porPagina: filtros.porPagina, totalPaginas: 0 }

    if (!error.response) errorConexion.value = mensajeDeError(error)
    notificar('error', mensajeDeError(error))
  } finally {
    if (peticion === peticionActual) cargando.value = false
  }
}

async function cargarEstadisticas() {
  try {
    estadisticas.value = await productoApi.estadisticas()
  } catch {
    // El listado ya muestra el aviso de conexión.
  }
}

function programarCarga() {
  clearTimeout(temporizadorBusqueda)
  temporizadorBusqueda = setTimeout(cargar, 250)
}

watch(filtros, programarCarga, { deep: true })
watch(
  () => filtros.q,
  () => {
    filtros.pagina = 1
  },
)
watch(
  () => [filtros.orden, filtros.direccion, filtros.porPagina],
  () => {
    filtros.pagina = 1
  },
)

function recargar() {
  cargar()
  cargarEstadisticas()
}

function irAPagina(numero) {
  if (numero < 1 || numero > totalPaginas.value || numero === filtros.pagina) return
  filtros.pagina = numero
}

function alternarDireccion() {
  filtros.direccion = filtros.direccion === 'ASC' ? 'DESC' : 'ASC'
}

function abrirNuevo() {
  productoEditando.value = null
  erroresServidor.value = {}
  modalAbierto.value = true
}

function abrirEditar(producto) {
  productoEditando.value = producto
  erroresServidor.value = {}
  modalAbierto.value = true
}

function cerrarModal() {
  if (guardando.value) return
  modalAbierto.value = false
}

async function guardar(payload) {
  guardando.value = true
  erroresServidor.value = {}

  try {
    if (productoEditando.value) {
      await productoApi.actualizar(productoEditando.value.id, payload)
      notificar('ok', 'Producto actualizado correctamente.')
    } else {
      await productoApi.crear(payload)
      notificar('ok', 'Producto creado correctamente.')
    }

    modalAbierto.value = false
    await Promise.all([cargar(), cargarEstadisticas()])
  } catch (error) {
    erroresServidor.value = erroresDeCampo(error)

    if (!error.response) errorConexion.value = mensajeDeError(error)
    notificar('error', mensajeDeError(error))
  } finally {
    guardando.value = false
  }
}

function pedirEliminar(producto) {
  productoAEliminar.value = producto
}

async function confirmarEliminacion() {
  const producto = productoAEliminar.value
  if (!producto) return

  eliminando.value = true

  try {
    await productoApi.eliminar(producto.id)

    const eraUltimoDeLaPagina = productos.value.length === 1 && filtros.pagina > 1
    productoAEliminar.value = null
    notificar('ok', `"${producto.nombre}" fue eliminado.`)

    if (eraUltimoDeLaPagina) {
      filtros.pagina -= 1
    } else {
      await cargar()
    }

    await cargarEstadisticas()
  } catch (error) {
    notificar('error', mensajeDeError(error))
  } finally {
    eliminando.value = false
  }
}

function notificar(tipo, texto) {
  const id = Date.now() + Math.random()
  toasts.value.push({ id, tipo, texto })

  setTimeout(() => {
    toasts.value = toasts.value.filter((toast) => toast.id !== id)
  }, 4500)
}

function limpiarProductos() {
  productos.value = []
  estadisticas.value = null
  meta.value = { total: 0, pagina: 1, porPagina: filtros.porPagina, totalPaginas: 0 }
}

function salir() {
  auth.cerrarSesion()
  limpiarProductos()
}

function alExpirarSesion() {
  if (!auth.autenticado) return

  auth.cerrarSesion('Tu sesión expiró. Inicia sesión de nuevo.')
}

watch(
  () => auth.autenticado,
  (autenticado, anterior) => {
    if (autenticado && !anterior) recargar()
    if (!autenticado) limpiarProductos()
  },
)

onMounted(async () => {
  window.addEventListener('auth:expirada', alExpirarSesion)

  if (auth.autenticado) {
    await auth.restaurarSesion()
    if (auth.autenticado) recargar()
  }
})

onBeforeUnmount(() => {
  clearTimeout(temporizadorBusqueda)
  window.removeEventListener('auth:expirada', alExpirarSesion)
})
</script>

<template>
  <AuthView v-if="!auth.autenticado" />

  <template v-else>
    <div class="app-shell">
      <header class="topbar">
        <div class="marca">
          <span class="marca__logo" aria-hidden="true">V</span>
          <div>
            <h1>Productos</h1>
            <p>CRUD conectado a la API Symfony · Vue 3 + Vite</p>
          </div>
        </div>

        <div class="sesion">
          <span class="chip-api">API · /api/productos</span>

          <div class="usuario">
            <span class="usuario__avatar" aria-hidden="true">{{ auth.inicial }}</span>
            <div class="usuario__datos">
              <strong>{{ auth.nombreVisible }}</strong>
              <span>{{ auth.usuario?.email }}</span>
            </div>
            <button type="button" class="btn btn-ghost btn--compacto" @click="salir">
              Salir
            </button>
          </div>
        </div>
      </header>

    <div v-if="errorConexion" class="alerta alerta--error" role="alert">
      <div class="alerta__texto">
        <strong>Sin conexión con la API</strong>
        <span>
          Arranca Symfony con
          <code>php -S 127.0.0.1:8000 -t public public/index.php</code>
          y pulsa reintentar.
        </span>
      </div>
      <button type="button" class="btn btn-ghost btn--compacto" @click="recargar">Reintentar</button>
    </div>

    <EstadisticasCards :stats="estadisticas" :cargando="cargando && !estadisticas" />

    <section class="panel panel--tabla">
      <div class="barra-carga" :class="{ 'barra-carga--activa': cargando }" aria-hidden="true"></div>

      <div class="toolbar">
        <div class="buscador">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7" />
            <line x1="20" y1="20" x2="16.5" y2="16.5" />
          </svg>
          <input
            v-model="filtros.q"
            class="input"
            type="search"
            placeholder="Buscar por nombre o descripción…"
            aria-label="Buscar productos"
          />
        </div>

        <select v-model="filtros.orden" class="select" aria-label="Ordenar por">
          <option v-for="opcion in opcionesOrden" :key="opcion.valor" :value="opcion.valor">
            {{ opcion.texto }}
          </option>
        </select>

        <button
          type="button"
          class="btn btn-ghost btn--dir"
          :title="filtros.direccion === 'ASC' ? 'Cambiar a descendente' : 'Cambiar a ascendente'"
          @click="alternarDireccion"
        >
          {{ filtros.direccion === 'ASC' ? '↑ ASC' : '↓ DESC' }}
        </button>

        <select v-model.number="filtros.porPagina" class="select" aria-label="Productos por página">
          <option v-for="tamano in tamanosPagina" :key="tamano" :value="tamano">
            {{ tamano }} / pág.
          </option>
        </select>

        <button type="button" class="btn btn-primary" @click="abrirNuevo">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Nuevo producto
        </button>
      </div>

      <ProductoTable
        :productos="productos"
        :cargando="cargando"
        @editar="abrirEditar"
        @eliminar="pedirEliminar"
      />

      <footer v-if="meta.total > 0" class="paginacion">
        <p class="paginacion__info">
          Mostrando <strong>{{ desde }}–{{ hasta }}</strong> de
          <strong>{{ meta.total }}</strong> productos
        </p>

        <div class="paginacion__controles">
          <button
            type="button"
            class="btn btn-ghost btn--compacto"
            :disabled="filtros.pagina <= 1 || cargando"
            @click="irAPagina(filtros.pagina - 1)"
          >
            ← Anterior
          </button>

          <button
            v-for="numero in paginasVisibles"
            :key="numero"
            type="button"
            class="pagina"
            :class="{ 'pagina--activa': numero === filtros.pagina }"
            :aria-current="numero === filtros.pagina ? 'page' : undefined"
            @click="irAPagina(numero)"
          >
            {{ numero }}
          </button>

          <button
            type="button"
            class="btn btn-ghost btn--compacto"
            :disabled="filtros.pagina >= totalPaginas || cargando"
            @click="irAPagina(filtros.pagina + 1)"
          >
            Siguiente →
          </button>
        </div>
      </footer>
    </section>

    <p class="pie">Symfony 6.4 · PostgreSQL 16 · API REST en JSON</p>
  </div>

  <ProductoFormModal
    :abierto="modalAbierto"
    :producto="productoEditando"
    :guardando="guardando"
    :errores="erroresServidor"
    @cerrar="cerrarModal"
    @guardar="guardar"
  />

  <ConfirmModal
    :abierto="productoAEliminar !== null"
    titulo="Eliminar producto"
    :mensaje="mensajeEliminacion"
    :procesando="eliminando"
    texto-confirmar="Sí, eliminar"
    @cancelar="productoAEliminar = null"
    @confirmar="confirmarEliminacion"
  />

      <div class="toasts" aria-live="polite" aria-atomic="false">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast"
          :class="toast.tipo === 'ok' ? 'toast--ok' : 'toast--error'"
          role="status"
        >
          <span aria-hidden="true">{{ toast.tipo === 'ok' ? '✓' : '!' }}</span>
          <div>
            <strong>{{ toast.tipo === 'ok' ? 'Listo' : 'Error' }}</strong>
            <p>{{ toast.texto }}</p>
          </div>
        </div>
      </div>
    </template>
</template>

<style scoped>
.app-shell {
  width: min(1200px, 100%);
  margin: 0 auto;
  padding: 30px 24px 54px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  flex-wrap: wrap;
}

.marca {
  display: flex;
  align-items: center;
  gap: 14px;
}

.marca__logo {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, var(--acento), var(--acento-oscuro));
  color: #fff;
  font-weight: 800;
  font-size: 22px;
  box-shadow: 0 10px 22px rgba(66, 184, 131, 0.32);
}

.marca h1 {
  font-size: 25px;
  letter-spacing: -0.4px;
}

.marca p {
  font-size: 13.5px;
  color: var(--texto-suave);
  margin-top: 2px;
}

.chip-api {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--texto-suave);
  background: #fff;
  border: 1px solid var(--borde);
  border-radius: 999px;
  padding: 7px 13px;
}

.chip-api::before {
  content: '';
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--acento);
  box-shadow: 0 0 0 3px var(--acento-suave);
}

.sesion {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.usuario {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fff;
  border: 1px solid var(--borde);
  border-radius: 999px;
  padding: 5px 6px 5px 5px;
  box-shadow: var(--sombra);
}

.usuario__avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: var(--acento-suave);
  color: var(--acento-oscuro);
  font-size: 14px;
  font-weight: 800;
}

.usuario__datos {
  display: flex;
  flex-direction: column;
  line-height: 1.25;
  min-width: 0;
}

.usuario__datos strong {
  font-size: 13px;
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.usuario__datos span {
  font-size: 11.5px;
  color: var(--texto-suave);
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.alerta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  flex-wrap: wrap;
  padding: 14px 16px;
  border-radius: 12px;
  font-size: 14px;
}

.alerta--error {
  background: var(--peligro-suave);
  border: 1px solid #fecaca;
  color: #991b1b;
}

.alerta code {
  background: #fff;
  border: 1px solid #fecaca;
  border-radius: 6px;
  padding: 1px 6px;
  font-size: 12.5px;
}

.alerta__texto {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.panel--tabla {
  position: relative;
  overflow: hidden;
}

.toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  padding: 16px 18px;
  border-bottom: 1px solid var(--borde);
}

.buscador {
  position: relative;
  flex: 1 1 240px;
  min-width: 200px;
}

.buscador svg {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.buscador .input {
  padding-left: 38px;
}

.toolbar .select {
  width: auto;
  min-width: 130px;
}

.btn--dir {
  min-width: 46px;
}

.barra-carga {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  overflow: hidden;
  opacity: 0;
  transition: opacity 0.2s ease;
  z-index: 2;
}

.barra-carga--activa {
  opacity: 1;
}

.barra-carga::after {
  content: '';
  display: block;
  width: 40%;
  height: 100%;
  background: var(--acento);
  border-radius: 999px;
  animation: barra 1s ease-in-out infinite;
}

@keyframes barra {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(350%);
  }
}

.paginacion {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 14px 18px;
  border-top: 1px solid var(--borde);
}

.paginacion__info {
  font-size: 13.5px;
  color: var(--texto-suave);
}

.paginacion__info strong {
  color: var(--texto);
}

.paginacion__controles {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.pagina {
  min-width: 34px;
  height: 34px;
  padding: 0 8px;
  border-radius: 9px;
  border: 1px solid var(--borde);
  background: #fff;
  cursor: pointer;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--texto-suave);
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.pagina:hover {
  border-color: #cbd5e1;
  color: var(--texto);
}

.pagina--activa {
  background: var(--acento);
  border-color: var(--acento);
  color: #fff;
}

.pagina--activa:hover {
  color: #fff;
}

.pie {
  text-align: center;
  font-size: 12.5px;
  color: var(--texto-suave);
}

@media (max-width: 720px) {
  .app-shell {
    padding: 20px 14px 42px;
  }

  .marca h1 {
    font-size: 22px;
  }

  .sesion {
    width: 100%;
    justify-content: space-between;
  }

  .toolbar .select {
    flex: 1 1 140px;
  }

  .paginacion {
    justify-content: center;
  }
}
</style>
