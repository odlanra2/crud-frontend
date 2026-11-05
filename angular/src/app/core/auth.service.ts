import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { Observable, catchError, map, of, switchMap, tap, throwError } from 'rxjs';

import { API_URL } from './api';
import {
  Credenciales,
  RegistroPayload,
  RespuestaLogin,
  RespuestaRegistro,
  Usuario,
  guardarSesion,
  guardarToken,
  leerToken,
  leerUsuario,
  limpiarSesion,
} from './sesion';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);

  private readonly _token = signal<string | null>(leerToken());
  private readonly _usuario = signal<Usuario | null>(leerUsuario());
  private readonly _aviso = signal('');

  readonly token = this._token.asReadonly();
  readonly usuario = this._usuario.asReadonly();
  readonly aviso = this._aviso.asReadonly();
  readonly autenticado = computed(() => this._token() !== null);
  readonly nombreVisible = computed(
    () => this._usuario()?.nombre || this._usuario()?.email || 'Usuario',
  );
  readonly inicial = computed(() => this.nombreVisible().charAt(0).toUpperCase());

  iniciarSesion(credenciales: Credenciales): Observable<Usuario> {
    return this.http.post<RespuestaLogin>(`${API_URL}/login_check`, credenciales).pipe(
      switchMap(({ token }) => {
        // El token debe estar guardado antes de pedir /me para que el interceptor lo envíe.
        guardarToken(token);
        this._token.set(token);

        return this.obtenerUsuario().pipe(tap((usuario) => this.establecerSesion(token, usuario)));
      }),
      catchError((error: HttpErrorResponse) => {
        this.cerrarSesion();
        return throwError(() => error);
      }),
    );
  }

  registrar(payload: RegistroPayload): Observable<Usuario> {
    return this.http.post<RespuestaRegistro>(`${API_URL}/register`, payload).pipe(
      tap(({ user, token }) => this.establecerSesion(token, user)),
      map(({ user }) => user),
    );
  }

  obtenerUsuario(): Observable<Usuario> {
    return this.http.get<Usuario>(`${API_URL}/me`);
  }

  /** Valida el token guardado al arrancar la aplicación. */
  restaurarSesion(): Observable<Usuario | null> {
    const token = this._token();
    if (!token) return of(null);

    return this.obtenerUsuario().pipe(
      tap((usuario) => this.establecerSesion(token, usuario)),
      catchError((error: HttpErrorResponse) => {
        // Si la API está caída no cerramos la sesión; solo si el token ya no vale.
        if (error.status === 401) this.cerrarSesion();
        return of(null);
      }),
    );
  }

  cerrarSesion(motivo = ''): void {
    limpiarSesion();
    this._token.set(null);
    this._usuario.set(null);
    this._aviso.set(motivo);
  }

  private establecerSesion(token: string, usuario: Usuario): void {
    guardarSesion(token, usuario);
    this._token.set(token);
    this._usuario.set(usuario);
    this._aviso.set('');
  }
}
