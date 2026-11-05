export interface Usuario {
  id: number;
  email: string;
  nombre: string | null;
  roles: string[];
  creadoEn: string;
}

export interface Credenciales {
  email: string;
  password: string;
}

export interface RegistroPayload extends Credenciales {
  nombre: string;
}

export interface RespuestaLogin {
  token: string;
}

export interface RespuestaRegistro {
  user: Usuario;
  token: string;
}

const TOKEN_KEY = 'crud_token';
const USER_KEY = 'crud_user';

export function leerToken(): string | null {
  return localStorage.getItem(TOKEN_KEY);
}

export function leerUsuario(): Usuario | null {
  try {
    const guardado = localStorage.getItem(USER_KEY);

    return guardado ? (JSON.parse(guardado) as Usuario) : null;
  } catch {
    return null;
  }
}

export function guardarToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token);
}

export function guardarSesion(token: string, usuario: Usuario): void {
  guardarToken(token);
  localStorage.setItem(USER_KEY, JSON.stringify(usuario));
}

export function limpiarSesion(): void {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}
