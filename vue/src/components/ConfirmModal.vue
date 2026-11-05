<script setup>
import { onBeforeUnmount, onMounted } from 'vue'

const props = defineProps({
  abierto: Boolean,
  titulo: {
    type: String,
    default: 'Confirmar acción',
  },
  mensaje: {
    type: String,
    default: '',
  },
  procesando: Boolean,
  textoConfirmar: {
    type: String,
    default: 'Confirmar',
  },
})

const emit = defineEmits(['cancelar', 'confirmar'])

function cancelar() {
  if (!props.procesando) emit('cancelar')
}

function alPresionarTecla(evento) {
  if (evento.key === 'Escape' && props.abierto) cancelar()
}

onMounted(() => window.addEventListener('keydown', alPresionarTecla))
onBeforeUnmount(() => window.removeEventListener('keydown', alPresionarTecla))
</script>

<template>
  <div v-if="abierto" class="overlay" @click.self="cancelar">
    <section class="modal modal--confirmar" role="alertdialog" aria-modal="true" :aria-label="titulo">
      <header class="modal__cabecera">
        <div>
          <h2 class="modal__titulo">{{ titulo }}</h2>
          <p class="modal__subtitulo">{{ mensaje }}</p>
        </div>
        <button type="button" class="modal__cerrar" aria-label="Cerrar" @click="cancelar">×</button>
      </header>

      <footer class="modal__pie">
        <button type="button" class="btn btn-ghost" :disabled="procesando" @click="cancelar">
          Cancelar
        </button>
        <button type="button" class="btn btn-peligro" :disabled="procesando" @click="emit('confirmar')">
          {{ procesando ? 'Eliminando…' : textoConfirmar }}
        </button>
      </footer>
    </section>
  </div>
</template>

<style scoped>
.modal--confirmar {
  width: min(430px, 100%);
}

.modal__subtitulo {
  line-height: 1.55;
}

.modal__pie {
  margin-top: 20px;
}
</style>
