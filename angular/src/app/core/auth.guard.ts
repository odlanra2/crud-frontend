import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

import { AuthService } from './auth.service';

/** Solo deja pasar a usuarios autenticados. */
export const authGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return auth.autenticado() ? true : router.createUrlTree(['/login']);
};

/** Evita que un usuario ya autenticado vuelva a la pantalla de login. */
export const invitadoGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return auth.autenticado() ? router.createUrlTree(['/']) : true;
};
