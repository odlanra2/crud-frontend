<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'

const props = defineProps({
  abierto: Boolean,
  producto: {
    type: Object,
    default: null,
  },
  guardando: Boolean,
  errores: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['cerrar', 'guardar'])

const form = reactive({
  nombre: '',
  descripcion: '',
  precio: '',
  stock: 0,
  activo: true,
})

const erroresLocales = ref({})

const esEdicion = computed(() => props.producto !== null)
const titulo = computed(() => (esEdicion.value ? 'Editar producto' : 'Nuevo producto'))

const erroresVisibles = computed(() => ({
  ...props.errores,
  ...erroresLocales.value,
}))

watch(
  () => [props.abierto, props.producto],
  () => {
    erroresLocales.value = {}
    if (!props.abierto) return

    const producto = props.producto
    form.nombre = producto?.nombre ?? ''
    form.descripcion = producto?.descripcion ?? ''
    form.precio = producto ? Number(producto.precio) : ''
    form.stock = producto ? Number(producto.stock) : 0
    form.activo = producto ? producto.activo : true
  },
  { immediate: true },
)

function enviar() {
  const errores = {}

  if (form.nombre.trim().length < 3) {
    errores.nombre = ['El nombre debe tener al menos 3 caracteres.']
  }

  if (form.precio === '' || form.precio === null || Number(form.precio) <= 0) {
    errores.precio = ['El precio debe ser mayor que cero.']
  }

  if (
    form.stock === '' ||
    form.stock === null ||
    Number(form.stock) < 0 ||
    !Number.isInteger(Number(form.stock))
  ) {
    errores.stock = ['El stock debe ser un entero mayor o igual a cero.']
  }

  erroresLocales.value = errores
  if (Object.keys(errores).length > 0) return

  emit('guardar', {
    nombre: form.nombre.trim(),
    descripcion: form.descripcion.trim() || null,
    precio: Number(form.precio),
    stock: Number(form.stock),
    activo: form.activo,
  })
}

function cerrar() {
  if (!props.guardando) emit('cerrar')
}

function alPresionarTecla(evento) {
  if (evento.key === 'Escape' && props.abierto) cerrar()
}

onMounted(() => window.addEventListener('keydown', alPresionarTecla))
onBeforeUnmount(() => window.removeEventListener('keydown', alPresionarTecla))
</script>

<template>
  <div v-if="abierto" class="overlay" @click.self="cerrar">
    <section class="modal" role="dialog" aria-modal="true" :aria-label="titulo">
      <header class="modal__cabecera">
        <div>
          <h2 class="modal__titulo">{{ titulo }}</h2>
          <p class="modal__subtitulo">
            {{ esEdicion ? 'Modifica los datos y guarda los cambios.' : 'Completa los datos del nuevo producto.' }}
          </p>
        </div>
        <button type="button" class="modal__cerrar" aria-label="Cerrar" @click="cerrar">×</button>
      </header>

      <form class="modal__cuerpo" novalidate @submit.prevent="enviar">
        <div class="campo">
          <label for="producto-nombre">Nombre *</label>
          <input
            id="producto-nombre"
            v-model="form.nombre"
            class="input"
            :class="{ 'input--error': erroresVisibles.nombre }"
            type="text"
            maxlength="150"
            placeholder="Ej. Teclado mecánico RGB"
            autocomplete="off"
          />
          <p v-if="erroresVisibles.nombre" class="campo__error">{{ erroresVisibles.nombre[0] }}</p>
        </div>

        <div class="campo">
          <label for="producto-descripcion">Descripción</label>
          <textarea
            id="producto-descripcion"
            v-model="form.descripcion"
            class="input"
            :class="{ 'input--error': erroresVisibles.descripcion }"
            rows="3"
            maxlength="2000"
            placeholder="Características, color, garantía…"
          ></textarea>
          <div class="campo__fila">
            <p v-if="erroresVisibles.descripcion" class="campo__error">{{ erroresVisibles.descripcion[0] }}</p>
            <span class="campo__pista">{{ form.descripcion.length }}/2000</span>
          </div>
        </div>

        <div class="fila-doble">
          <div class="campo">
            <label for="producto-precio">Precio *</label>
            <input
              id="producto-precio"
              v-model.number="form.precio"
              class="input"
              :class="{ 'input--error': erroresVisibles.precio }"
              type="number"
              min="0.01"
              step="0.01"
              placeholder="0.00"
            />
            <p v-if="erroresVisibles.precio" class="campo__error">{{ erroresVisibles.precio[0] }}</p>
          </div>

          <div class="campo">
            <label for="producto-stock">Stock *</label>
            <input
              id="producto-stock"
              v-model.number="form.stock"
              class="input"
              :class="{ 'input--error': erroresVisibles.stock }"
              type="number"
              min="0"
              step="1"
              placeholder="0"
            />
            <p v-if="erroresVisibles.stock" class="campo__error">{{ erroresVisibles.stock[0] }}</p>
          </div>
        </div>

        <div class="campo">
          <label>Estado</label>
          <label class="switch">
            <input v-model="form.activo" type="checkbox" />
            <span class="switch__pista"><span class="switch__bolita"></span></span>
            <span class="switch__texto">{{ form.activo ? 'Activo (visible para la venta)' : 'Inactivo' }}</span>
          </label>
        </div>

        <footer class="modal__pie">
          <button type="button" class="btn btn-ghost" :disabled="guardando" @click="cerrar">
            Cancelar
          </button>
          <button type="submit" class="btn btn-primary" :disabled="guardando">
            {{ guardando ? 'Guardando…' : esEdicion ? 'Guardar cambios' : 'Crear producto' }}
          </button>
        </footer>
      </form>
    </section>
  </div>
</template>

<style scoped>
.fila-doble {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.campo__fila {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.campo__fila .campo__pista {
  margin-left: auto;
}

.switch {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  color: var(--texto);
}

.switch input {
  position: absolute;
  opacity: 0;
  width: 1px;
  height: 1px;
}

.switch__pista {
  width: 42px;
  height: 24px;
  border-radius: 999px;
  background: #cbd5e1;
  padding: 3px;
  transition: background 0.18s ease;
  flex: none;
}

.switch__bolita {
  display: block;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.25);
  transition: transform 0.18s ease;
}

.switch input:checked + .switch__pista {
  background: var(--acento);
}

.switch input:checked + .switch__pista .switch__bolita {
  transform: translateX(18px);
}

.switch input:focus-visible + .switch__pista {
  box-shadow: var(--anillo);
}

.switch__texto {
  color: var(--texto-suave);
  font-size: 13.5px;
}

.modal__pie {
  border-top: 0;
  padding: 16px 0 0;
  margin-top: 2px;
}

@media (max-width: 520px) {
  .fila-doble {
    grid-template-columns: 1fr;
  }
}
</style>
