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

/** Traduce los errores de autenticación a mensajes claros en español. */
export function mensajeDeAutenticacion(error) {
  const estado = error?.response?.status

  if (estado === 401) return 'Email o contraseña incorrectos.'
  if (estado === 409) return 'Ese email ya está registrado. Prueba a iniciar sesión.'
  if (estado === 422) return mensajeDeError(error)

  return mensajeDeError(error)
}
