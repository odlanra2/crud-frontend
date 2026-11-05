import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import {
  Estadisticas,
  FiltrosProductos,
  ListaProductos,
  Producto,
  ProductoPayload,
} from './producto.model';

/**
 * URL base de la API Symfony.
 * Si despliegas el backend en otro host, cambia solo esta constante.
 */
const API_URL = 'http://127.0.0.1:8000/api/productos';

@Injectable({ providedIn: 'root' })
export class ProductoService {
  private readonly http = inject(HttpClient);

  listar(filtros: FiltrosProductos): Observable<ListaProductos> {
    let params = new HttpParams();

    for (const [clave, valor] of Object.entries(filtros)) {
      if (valor !== undefined && valor !== null && valor !== '') {
        params = params.set(clave, String(valor));
      }
    }

    return this.http.get<ListaProductos>(API_URL, { params });
  }

  obtener(id: number): Observable<Producto> {
    return this.http.get<Producto>(`${API_URL}/${id}`);
  }

  crear(payload: ProductoPayload): Observable<Producto> {
    return this.http.post<Producto>(API_URL, payload);
  }

  actualizar(id: number, payload: ProductoPayload): Observable<Producto> {
    return this.http.put<Producto>(`${API_URL}/${id}`, payload);
  }

  eliminar(id: number): Observable<void> {
    return this.http.delete<void>(`${API_URL}/${id}`);
  }

  estadisticas(): Observable<Estadisticas> {
    return this.http.get<Estadisticas>(`${API_URL}/estadisticas`);
  }
}
