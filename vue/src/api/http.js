import axios from 'axios'

export const TOKEN_KEY = 'crud_token'
export const USER_KEY = 'crud_user'

/**
 * Cliente HTTP compartido por toda la aplicación.
 * La URL se puede cambiar con VITE_API_URL en un archivo .env.
 */
const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api',
  headers: {
    Accept: 'application/json',
  },
  timeout: 12000,
})

/** Adjunta el JWT guardado a todas las peticiones. */
http.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

/**
 * Si la API responde 401 en una ruta protegida, la sesión ya no sirve.
 * Se avisa a la aplicación para limpiar el estado y volver al login.
 */
http.interceptors.response.use(
  (respuesta) => respuesta,
  (error) => {
    const url = error.config?.url ?? ''
    const esRutaPublica = url.includes('/login_check') || url.includes('/register')

    if (error.response?.status === 401 && !esRutaPublica) {
      window.dispatchEvent(new CustomEvent('auth:expirada'))
    }

    return Promise.reject(error)
  },
)

export default http
