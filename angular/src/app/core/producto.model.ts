export interface Producto {
  id: number;
  nombre: string;
  descripcion: string | null;
  precio: number;
  stock: number;
  activo: boolean;
  stockBajo: boolean;
  valorInventario: number;
  creadoEn: string;
  actualizadoEn: string;
}

export interface ProductoPayload {
  nombre: string;
  descripcion: string | null;
  precio: number;
  stock: number;
  activo: boolean;
}

export interface ListaMeta {
  total: number;
  pagina: number;
  porPagina: number;
  totalPaginas: number;
  termino: string | null;
  orden: string;
  direccion: 'ASC' | 'DESC';
}

export interface ListaProductos {
  data: Producto[];
  meta: ListaMeta;
}

export interface Estadisticas {
  total: number;
  activos: number;
  stockTotal: number;
  valorInventario: number;
}

export interface FiltrosProductos {
  q?: string;
  orden?: string;
  direccion?: 'ASC' | 'DESC';
  pagina?: number;
  porPagina?: number;
}

export interface ApiError {
  status: number;
  message: string;
  errors?: Record<string, string[]>;
}
