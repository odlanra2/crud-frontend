import axios from 'axios'

/**
 * Cliente HTTP de la API de productos (Symfony + PostgreSQL).
 * La URL se puede cambiar con VITE_API_URL en un archivo .env.
 */
const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api',
  headers: {
    Accept: 'application/json',
  },
  timeout: 12000,
})

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

/** Errores por campo que devuelve la API en un 422. */
export function erroresDeCampo(error) {
  const cuerpo = error?.response?.data

  return cuerpo && typeof cuerpo.errors === 'object' && cuerpo.errors !== null ? cuerpo.errors : {}
}

/** Texto legible para mostrar el error al usuario. */
export function mensajeDeError(error) {
  const cuerpo = error?.response?.data

  if (cuerpo?.errors && typeof cuerpo.errors === 'object') {
    const mensajes = Object.values(cuerpo.errors).flat()
    if (mensajes.length > 0) return mensajes.join(' ')
  }

  if (cuerpo?.message) return cuerpo.message

  if (!error?.response) {
    return 'No se pudo conectar con la API. Comprueba que Symfony esté corriendo en http://127.0.0.1:8000.'
  }

  return 'Ocurrió un error inesperado.'
}
