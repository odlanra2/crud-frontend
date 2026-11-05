import http from './http'

/**
 * Endpoints de autenticación de la API Symfony.
 * - POST /login_check devuelve { token }
 * - POST /register devuelve { user, token }
 * - GET  /me devuelve el usuario autenticado
 */
export const authApi = {
  /** @param {{ email: string, password: string }} credenciales */
  async login({ email, password }) {
    const { data } = await http.post('/login_check', { email, password })

    return data
  },

  /** @param {{ email: string, password: string, nombre: string }} payload */
  async registrar(payload) {
    const { data } = await http.post('/register', payload)

    return data
  },

  async me() {
    const { data } = await http.get('/me')

    return data
  },
}
