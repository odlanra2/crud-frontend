import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';

import { AuthService } from './auth.service';
import { leerToken } from './sesion';

/**
 * Adjunta el JWT a las peticiones protegidas y, si la API responde 401,
 * limpia la sesión y vuelve a la pantalla de login.
 */
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const router = inject(Router);
  const token = leerToken();
  const esRutaPublica = req.url.includes('/login_check') || req.url.includes('/register');

  const peticion =
    token && !esRutaPublica
      ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } })
      : req;

  return next(peticion).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401 && !esRutaPublica) {
        auth.cerrarSesion('Tu sesión expiró. Inicia sesión de nuevo.');

        if (router.url !== '/login') {
          void router.navigate(['/login']);
        }
      }

      return throwError(() => error);
    }),
  );
};
