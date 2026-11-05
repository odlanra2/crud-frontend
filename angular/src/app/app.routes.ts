import { Routes } from '@angular/router';

import { authGuard, invitadoGuard } from './core/auth.guard';

export const routes: Routes = [
  {
    path: 'login',
    title: 'Iniciar sesión · Angular + Symfony',
    canActivate: [invitadoGuard],
    loadComponent: () => import('./auth/login/login').then((archivo) => archivo.Login),
  },
  {
    path: '',
    title: 'Productos · Angular + Symfony',
    canActivate: [authGuard],
    loadComponent: () => import('./productos/productos').then((archivo) => archivo.Productos),
  },
  { path: '**', redirectTo: '' },
];
