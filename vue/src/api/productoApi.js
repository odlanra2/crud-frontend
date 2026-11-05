import http from './http'

export { erroresDeCampo, mensajeDeError } from './errores'

/**
 * Cliente de la API de productos (Symfony + PostgreSQL).
 * Usa la instancia compartida de http.js, que adjunta el JWT automáticamente.
 */
export const productoApi = {
  /**
   * @param {{ q?: string, orden?: string, direccion?: 'ASC'|'DESC', pagina?: number, porPagina?: number }} filtros
   * @returns {Promise<{ data: object[], meta: object }>}
   */
  async listar({ q = '', orden = 'creadoEn', direccion = 'DESC', pagina = 1, porPagina = 10 } = {}) {
    const { data } = await http.get('/productos', {
      params: {
        q: q || undefined,
        orden,
        direccion,
        pagina,
        porPagina,
      },
    })

    return data
  },

  /** @param {number|string} id */
  async obtener(id) {
    const { data } = await http.get(`/productos/${id}`)

    return data
  },

  /** @param {{ nombre: string, descripcion: string|null, precio: number, stock: number, activo: boolean }} payload */
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

