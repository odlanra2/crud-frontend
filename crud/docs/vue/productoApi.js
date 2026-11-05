import axios from 'axios'

/**
 * Cliente de la API de productos.
 * Copia este archivo en tu proyecto Vue (por ejemplo en src/api/productoApi.js)
 * y ejecuta `npm install axios`.
 */
const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api',
  headers: {
    Accept: 'application/json',
  },
})

export const productoApi = {
  /**
   * @param {{ q?: string, orden?: string, direccion?: 'ASC'|'DESC', pagina?: number, porPagina?: number }} params
   * @returns {Promise<{ data: object[], meta: object }>}
   */
  async listar({ q = '', orden = 'creadoEn', direccion = 'DESC', pagina = 1, porPagina = 10 } = {}) {
    const { data } = await http.get('/productos', {
      params: { q, orden, direccion, pagina, porPagina },
    })

    return data
  },

  /** @returns {Promise<object>} */
  async obtener(id) {
    const { data } = await http.get(`/productos/${id}`)

    return data
  },

  /** @param {object} payload */
  async crear(payload) {
    const { data } = await http.post('/productos', payload)

    return data
  },

  /** @param {number|string} id @param {object} payload */
  async actualizar(id, payload) {
    const { data } = await http.put(`/productos/${id}`, payload)

    return data
  },

  /** @param {number|string} id */
  async eliminar(id) {
    await http.delete(`/productos/${id}`)
  },

  /** @returns {Promise<{ total: number, activos: number, stockTotal: number, valorInventario: number }>} */
  async estadisticas() {
    const { data } = await http.get('/productos/estadisticas')

    return data
  },
}

/**
 * Convierte la respuesta de error de la API en un texto para mostrar al usuario.
 * Para errores 422 devuelve todos los mensajes por campo.
 */
export function mensajeDeError(error) {
  const respuesta = error?.response?.data

  if (respuesta?.errors && typeof respuesta.errors === 'object') {
    return Object.values(respuesta.errors).flat().join(' ')
  }

  return respuesta?.message ?? 'No se pudo conectar con la API.'
}
