<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

const modo = ref('login')
const formulario = reactive({
  nombre: '',
  email: '',
  password: '',
  confirmar: '',
})
const erroresLocales = ref({})
const verPassword = ref(false)
const emailInput = ref(null)

const esRegistro = computed(() => modo.value === 'registro')
const titulo = computed(() => (esRegistro.value ? 'Crea tu cuenta' : 'Bienvenido de nuevo'))
const subtitulo = computed(() =>
  esRegistro.value
    ? 'Regístrate una vez y empieza a gestionar el inventario.'
    : 'Inicia sesión para acceder al CRUD de productos.',
)

const erroresVisibles = computed(() => ({
  ...auth.erroresCampo,
  ...erroresLocales.value,
}))

function errorDe(campo) {
  const valor = erroresVisibles.value[campo]

  if (!valor) return ''
  return Array.isArray(valor) ? valor[0] : valor
}

function cambiarModo(nuevoModo) {
  if (auth.cargando || nuevoModo === modo.value) return

  modo.value = nuevoModo
  erroresLocales.value = {}
  auth.error = ''
  auth.erroresCampo = {}
  formulario.password = ''
  formulario.confirmar = ''
}

function validar() {
  const errores = {}
  const email = formulario.email.trim()

  if (!email) {
    errores.email = 'El email es obligatorio.'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    errores.email = 'Escribe un email válido.'
  }

  if (!formulario.password) {
    errores.password = 'La contraseña es obligatoria.'
  } else if (formulario.password.length < 8) {
    errores.password = 'La contraseña debe tener al menos 8 caracteres.'
  } else if (formulario.password.length > 72) {
    errores.password = 'La contraseña no puede superar 72 caracteres.'
  }

  if (esRegistro.value) {
    if (formulario.password !== formulario.confirmar) {
      errores.confirmar = 'Las contraseñas no coinciden.'
    }

    if (formulario.nombre.length > 100) {
      errores.nombre = 'El nombre no puede superar 100 caracteres.'
    }
  }

  erroresLocales.value = errores

  return Object.keys(errores).length === 0
}

async function enviar() {
  if (!validar()) return

  try {
    if (esRegistro.value) {
      await auth.registrar({
        email: formulario.email.trim(),
        password: formulario.password,
        nombre: formulario.nombre.trim(),
      })
    } else {
      await auth.iniciarSesion({
        email: formulario.email.trim(),
        password: formulario.password,
      })
    }
  } catch {
    // El store de Pinia ya dejó el mensaje en auth.error y los errores por campo.
  }
}

onMounted(() => emailInput.value?.focus())
</script>

<template>
  <div class="auth-shell">
    <main class="auth-card panel">
      <header class="auth-cabecera">
        <span class="auth-logo" aria-hidden="true">V</span>
        <div>
          <h1>Productos</h1>
          <p>CRUD Symfony · Vue 3 + Pinia</p>
        </div>
      </header>

      <div class="auth-tabs" role="tablist" aria-label="Acceso a la aplicación">
        <button
          type="button"
          role="tab"
          :aria-selected="!esRegistro"
          :class="{ 'auth-tab--activa': !esRegistro }"
          @click="cambiarModo('login')"
        >
          Iniciar sesión
        </button>
        <button
          type="button"
          role="tab"
          :aria-selected="esRegistro"
          :class="{ 'auth-tab--activa': esRegistro }"
          @click="cambiarModo('registro')"
        >
          Crear cuenta
        </button>
      </div>

      <div class="auth-texto">
        <h2>{{ titulo }}</h2>
        <p>{{ subtitulo }}</p>
      </div>

      <p v-if="auth.aviso" class="auth-aviso" role="status">{{ auth.aviso }}</p>

      <form class="auth-form" novalidate @submit.prevent="enviar">
        <div v-if="esRegistro" class="campo">
          <label for="auth-nombre">
            Nombre <span class="auth-opcional">(opcional)</span>
          </label>
          <input
            id="auth-nombre"
            v-model.trim="formulario.nombre"
            class="input"
            :class="{ 'input--error': errorDe('nombre') }"
            type="text"
            autocomplete="name"
            placeholder="Ana"
            :aria-invalid="Boolean(errorDe('nombre'))"
          />
          <span v-if="errorDe('nombre')" class="campo__error">{{ errorDe('nombre') }}</span>
        </div>

        <div class="campo">
          <label for="auth-email">Email</label>
          <input
            id="auth-email"
            ref="emailInput"
            v-model.trim="formulario.email"
            class="input"
            :class="{ 'input--error': errorDe('email') }"
            type="email"
            autocomplete="email"
            inputmode="email"
            placeholder="usuario@ejemplo.com"
            :aria-invalid="Boolean(errorDe('email'))"
          />
          <span v-if="errorDe('email')" class="campo__error">{{ errorDe('email') }}</span>
        </div>

        <div class="campo">
          <label for="auth-password">Contraseña</label>
          <div class="auth-password">
            <input
              id="auth-password"
              v-model="formulario.password"
              class="input"
              :class="{ 'input--error': errorDe('password') }"
              :type="verPassword ? 'text' : 'password'"
              :autocomplete="esRegistro ? 'new-password' : 'current-password'"
              placeholder="Mínimo 8 caracteres"
              :aria-invalid="Boolean(errorDe('password'))"
            />
            <button
              type="button"
              class="auth-ojo"
              :aria-label="verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
              @click="verPassword = !verPassword"
            >
              {{ verPassword ? 'Ocultar' : 'Ver' }}
            </button>
          </div>
          <span v-if="errorDe('password')" class="campo__error">{{ errorDe('password') }}</span>
        </div>

        <div v-if="esRegistro" class="campo">
          <label for="auth-confirmar">Repite la contraseña</label>
          <input
            id="auth-confirmar"
            v-model="formulario.confirmar"
            class="input"
            :class="{ 'input--error': errorDe('confirmar') }"
            type="password"
            autocomplete="new-password"
            placeholder="Vuelve a escribirla"
            :aria-invalid="Boolean(errorDe('confirmar'))"
          />
          <span v-if="errorDe('confirmar')" class="campo__error">{{ errorDe('confirmar') }}</span>
        </div>

        <p v-if="auth.error" class="auth-error" role="alert">{{ auth.error }}</p>

        <button type="submit" class="btn btn-primary auth-submit" :disabled="auth.cargando">
          <span v-if="auth.cargando" class="auth-spinner" aria-hidden="true"></span>
          {{
            auth.cargando
              ? esRegistro
                ? 'Creando cuenta…'
                : 'Entrando…'
              : esRegistro
                ? 'Crear cuenta'
                : 'Entrar'
          }}
        </button>
      </form>

      <p class="auth-pie">
        El CRUD está protegido con JWT. El token se guarda en este navegador y caduca en 1 hora.
      </p>
    </main>
  </div>
</template>

<style scoped>
.auth-shell {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 28px 18px;
}

.auth-card {
  width: min(440px, 100%);
  padding: 28px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  animation: subir 0.24s ease-out;
}

.auth-cabecera {
  display: flex;
  align-items: center;
  gap: 13px;
}

.auth-logo {
  width: 46px;
  height: 46px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, var(--acento), var(--acento-oscuro));
  color: #fff;
  font-weight: 800;
  font-size: 21px;
  box-shadow: 0 10px 22px rgba(66, 184, 131, 0.32);
}

.auth-cabecera h1 {
  font-size: 21px;
  letter-spacing: -0.3px;
}

.auth-cabecera p {
  font-size: 12.5px;
  color: var(--texto-suave);
  margin-top: 1px;
}

.auth-tabs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
  padding: 5px;
  background: #f1f5f9;
  border-radius: 12px;
}

.auth-tabs button {
  border: 0;
  background: transparent;
  border-radius: 9px;
  padding: 9px 10px;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--texto-suave);
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}

.auth-tabs button:hover {
  color: var(--texto);
}

.auth-tab--activa,
.auth-tab--activa:hover {
  background: #fff;
  color: var(--texto);
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
}

.auth-texto h2 {
  font-size: 19px;
  letter-spacing: -0.2px;
}

.auth-texto p {
  margin-top: 4px;
  font-size: 13.5px;
  color: var(--texto-suave);
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.auth-opcional {
  font-weight: 400;
  color: var(--texto-suave);
}

.auth-password {
  position: relative;
}

.auth-password .input {
  padding-right: 68px;
}

.auth-ojo {
  position: absolute;
  top: 50%;
  right: 6px;
  transform: translateY(-50%);
  border: 0;
  background: transparent;
  border-radius: 8px;
  padding: 6px 9px;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--acento-oscuro);
  cursor: pointer;
}

.auth-ojo:hover {
  background: var(--acento-suave);
}

.auth-error,
.auth-aviso {
  border-radius: 10px;
  padding: 11px 13px;
  font-size: 13px;
}

.auth-error {
  background: var(--peligro-suave);
  border: 1px solid #fecaca;
  color: #991b1b;
}

.auth-aviso {
  background: var(--aviso-suave);
  border: 1px solid #fde68a;
  color: var(--aviso);
}

.auth-submit {
  width: 100%;
  padding: 11px 14px;
  margin-top: 2px;
}

.auth-spinner {
  width: 15px;
  height: 15px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.45);
  border-top-color: #fff;
  animation: girar 0.7s linear infinite;
}

.auth-pie {
  border-top: 1px solid var(--borde);
  padding-top: 15px;
  font-size: 12px;
  line-height: 1.55;
  color: var(--texto-suave);
  text-align: center;
}

@keyframes girar {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 480px) {
  .auth-card {
    padding: 22px 18px;
  }
}
</style>
