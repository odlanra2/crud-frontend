import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { API_URL } from './api';
import {
  Estadisticas,
  FiltrosProductos,
  ListaProductos,
  Producto,
  ProductoPayload,
} from './producto.model';

const PRODUCTOS_URL = `${API_URL}/productos`;

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

    return this.http.get<ListaProductos>(PRODUCTOS_URL, { params });
  }

  obtener(id: number): Observable<Producto> {
    return this.http.get<Producto>(`${PRODUCTOS_URL}/${id}`);
  }

  crear(payload: ProductoPayload): Observable<Producto> {
    return this.http.post<Producto>(PRODUCTOS_URL, payload);
  }

  actualizar(id: number, payload: ProductoPayload): Observable<Producto> {
    return this.http.put<Producto>(`${PRODUCTOS_URL}/${id}`, payload);
  }

  eliminar(id: number): Observable<void> {
    return this.http.delete<void>(`${PRODUCTOS_URL}/${id}`);
  }

  estadisticas(): Observable<Estadisticas> {
    return this.http.get<Estadisticas>(`${PRODUCTOS_URL}/estadisticas`);
  }
}
