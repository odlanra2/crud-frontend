import { HttpErrorResponse } from '@angular/common/http';

import { ApiError } from './producto.model';

/** Errores por campo que devuelve la API en un 422. */
export function erroresDeCampo(error: unknown): Record<string, string[]> {
  const cuerpo = (error as HttpErrorResponse)?.error as ApiError | string | undefined;

  if (cuerpo && typeof cuerpo === 'object' && cuerpo.errors) {
    return cuerpo.errors;
  }

  return {};
}

/** Texto legible para mostrar el error al usuario. */
export function mensajeDeError(error: unknown): string {
  const respuesta = error as HttpErrorResponse;
  const cuerpo = respuesta?.error as ApiError | string | undefined;

  if (cuerpo && typeof cuerpo === 'object') {
    if (cuerpo.errors) {
      const mensajes = Object.values(cuerpo.errors).flat();
      if (mensajes.length > 0) return mensajes.join(' ');
    }

    if (cuerpo.message) return cuerpo.message;
  }

  if (!respuesta || respuesta.status === 0) {
    return 'No se pudo conectar con la API. Comprueba que Symfony esté corriendo en http://127.0.0.1:8000.';
  }

  return 'Ocurrió un error inesperado.';
}
