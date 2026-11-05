import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: '',
    title: 'Productos · Angular + Symfony',
    loadComponent: () => import('./productos/productos').then((archivo) => archivo.Productos),
  },
  { path: '**', redirectTo: '' },
];
