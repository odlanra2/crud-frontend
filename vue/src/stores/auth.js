import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { authApi } from '../api/authApi'
import { mensajeDeAutenticacion, erroresDeCampo } from '../api/errores'
import { TOKEN_KEY, USER_KEY } from '../api/http'

function leerUsuarioGuardado() {
  try {
    const guardado = localStorage.getItem(USER_KEY)

    return guardado ? JSON.parse(guardado) : null
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem(TOKEN_KEY))
  const usuario = ref(leerUsuarioGuardado())
  const cargando = ref(false)
  const error = ref('')
  const erroresCampo = ref({})
  const aviso = ref('')

  const autenticado = computed(() => Boolean(token.value))
  const nombreVisible = computed(
    () => usuario.value?.nombre || usuario.value?.email || 'Usuario',
  )
  const inicial = computed(() => nombreVisible.value.charAt(0).toUpperCase())

  function guardarSesion(nuevoToken, nuevoUsuario) {
    token.value = nuevoToken
    usuario.value = nuevoUsuario
    aviso.value = ''

    localStorage.setItem(TOKEN_KEY, nuevoToken)
    localStorage.setItem(USER_KEY, JSON.stringify(nuevoUsuario))
  }

  function limpiarSesion(motivo) {
    token.value = null
    usuario.value = null
    error.value = ''
    erroresCampo.value = {}

    if (motivo !== undefined) {
      aviso.value = motivo
    }

    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  }

  async function iniciarSesion({ email, password }) {
    cargando.value = true
    error.value = ''
    erroresCampo.value = {}
    aviso.value = ''

    try {
      const { token: nuevoToken } = await authApi.login({ email, password })

      // Guardamos el token antes de pedir /me para que el interceptor lo envíe.
      token.value = nuevoToken
      localStorage.setItem(TOKEN_KEY, nuevoToken)

      const datosUsuario = await authApi.me()
      guardarSesion(nuevoToken, datosUsuario)
    } catch (fallo) {
      limpiarSesion()
      error.value = mensajeDeAutenticacion(fallo)
      erroresCampo.value = erroresDeCampo(fallo)
      throw fallo
    } finally {
      cargando.value = false
    }
  }

  async function registrar({ email, password, nombre }) {
    cargando.value = true
    error.value = ''
    erroresCampo.value = {}
    aviso.value = ''

    try {
      const { user, token: nuevoToken } = await authApi.registrar({
        email,
        password,
        nombre,
      })

      guardarSesion(nuevoToken, user)
    } catch (fallo) {
      error.value = mensajeDeAutenticacion(fallo)
      erroresCampo.value = erroresDeCampo(fallo)
      throw fallo
    } finally {
      cargando.value = false
    }
  }

  /** Valida el token guardado al recargar la página. */
  async function restaurarSesion() {
    if (!token.value) return

    try {
      const datosUsuario = await authApi.me()
      guardarSesion(token.value, datosUsuario)
    } catch (fallo) {
      // Si la API está caída no cerramos la sesión; solo si el token ya no vale.
      if (fallo?.response?.status === 401) limpiarSesion()
    }
  }

  function cerrarSesion(motivo = '') {
    limpiarSesion(motivo)
  }

  return {
    token,
    usuario,
    cargando,
    error,
    erroresCampo,
    aviso,
    autenticado,
    nombreVisible,
    inicial,
    iniciarSesion,
    registrar,
    restaurarSesion,
    cerrarSesion,
  }
})
