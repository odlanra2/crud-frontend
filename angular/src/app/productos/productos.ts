import { HttpErrorResponse } from '@angular/common/http';
import {
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  HostListener,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { Subject, debounceTime, distinctUntilChanged, finalize } from 'rxjs';

import { AuthService } from '../core/auth.service';
import { erroresDeCampo, mensajeDeError } from '../core/errores';
import { Estadisticas, ListaMeta, Producto, ProductoPayload } from '../core/producto.model';
import { ProductoService } from '../core/producto.service';

interface Toast {
  id: number;
  tipo: 'ok' | 'error';
  texto: string;
}

type CampoFormulario = 'nombre' | 'descripcion' | 'precio' | 'stock' | 'activo';

@Component({
  selector: 'app-productos',
  imports: [ReactiveFormsModule],
  templateUrl: './productos.html',
  styleUrl: './productos.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Productos implements OnInit {
  private readonly api = inject(ProductoService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);
  private readonly busqueda$ = new Subject<string>();

  protected readonly usuario = this.auth.usuario;
  protected readonly nombreVisible = this.auth.nombreVisible;
  protected readonly inicial = this.auth.inicial;

  protected readonly productos = signal<Producto[]>([]);
  protected readonly meta = signal<ListaMeta | null>(null);
  protected readonly estadisticas = signal<Estadisticas | null>(null);
  protected readonly q = signal('');
  protected readonly orden = signal('creadoEn');
  protected readonly direccion = signal<'ASC' | 'DESC'>('DESC');
  protected readonly pagina = signal(1);
  protected readonly porPagina = signal(10);
  protected readonly cargando = signal(true);
  protected readonly guardando = signal(false);
  protected readonly eliminando = signal(false);
  protected readonly errorConexion = signal('');
  protected readonly modalAbierto = signal(false);
  protected readonly productoEditando = signal<Producto | null>(null);
  protected readonly erroresServidor = signal<Record<string, string[]>>({});
  protected readonly productoAEliminar = signal<Producto | null>(null);
  protected readonly toasts = signal<Toast[]>([]);

  protected readonly opcionesOrden = [
    { valor: 'creadoEn', texto: 'Más recientes' },
    { valor: 'nombre', texto: 'Nombre' },
    { valor: 'precio', texto: 'Precio' },
    { valor: 'stock', texto: 'Stock' },
    { valor: 'activo', texto: 'Estado' },
    { valor: 'actualizadoEn', texto: 'Actualizados' },
  ];

  protected readonly tamanosPagina = [5, 10, 25, 50];
  protected readonly esqueletos = [1, 2, 3, 4, 5];

  protected readonly form = this.fb.group({
    nombre: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(150)]],
    descripcion: [''],
    precio: [0, [Validators.required, Validators.min(0.01)]],
    stock: [0, [Validators.required, Validators.min(0)]],
    activo: [true],
  });

  protected readonly totalPaginas = computed(() => this.meta()?.totalPaginas ?? 0);

  protected readonly desde = computed(() => {
    const meta = this.meta();

    return meta && meta.total > 0 ? (meta.pagina - 1) * meta.porPagina + 1 : 0;
  });

  protected readonly hasta = computed(() => {
    const meta = this.meta();

    return meta ? Math.min(meta.pagina * meta.porPagina, meta.total) : 0;
  });

  protected readonly paginasVisibles = computed(() => {
    const meta = this.meta();
    if (!meta || meta.totalPaginas <= 1) return [];

    const actual = this.pagina();
    const inicio = Math.max(1, Math.min(actual - 2, meta.totalPaginas - 4));
    const fin = Math.min(meta.totalPaginas, inicio + 4);

    return Array.from({ length: fin - inicio + 1 }, (_, indice) => inicio + indice);
  });

  private readonly formatoNumero = new Intl.NumberFormat('es-CO');
  private readonly formatoMoneda = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  });
  private readonly formatoFecha = new Intl.DateTimeFormat('es-CO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });

  private peticionActual = 0;

  constructor() {
    this.busqueda$
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.cargar());
  }

  ngOnInit(): void {
    this.cargar();
    this.cargarEstadisticas();
  }

  @HostListener('document:keydown.escape')
  protected alPresionarEscape(): void {
    if (this.productoAEliminar()) {
      this.cancelarEliminar();
    } else if (this.modalAbierto()) {
      this.cerrarModal();
    }
  }

  protected cargar(): void {
    const peticion = ++this.peticionActual;
    this.cargando.set(true);

    this.api
      .listar({
        q: this.q().trim() || undefined,
        orden: this.orden(),
        direccion: this.direccion(),
        pagina: this.pagina(),
        porPagina: this.porPagina(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (respuesta) => {
          if (peticion !== this.peticionActual) return;

          this.productos.set(respuesta.data);
          this.meta.set(respuesta.meta);
          this.errorConexion.set('');
          this.cargando.set(false);
        },
        error: (error: HttpErrorResponse) => {
          if (peticion !== this.peticionActual) return;

          this.productos.set([]);
          this.meta.set(null);
          this.cargando.set(false);
          this.notificar('error', mensajeDeError(error));

          if (!error.status) {
            this.errorConexion.set(mensajeDeError(error));
          }
        },
      });
  }

  protected cargarEstadisticas(): void {
    this.api
      .estadisticas()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (datos) => this.estadisticas.set(datos),
        error: () => undefined,
      });
  }

  protected recargar(): void {
    this.cargar();
    this.cargarEstadisticas();
  }

  protected salir(): void {
    this.auth.cerrarSesion();
    void this.router.navigate(['/login']);
  }

  protected alBuscar(evento: Event): void {
    const texto = (evento.target as HTMLInputElement).value;

    this.q.set(texto);
    this.pagina.set(1);
    this.busqueda$.next(texto);
  }

  protected cambiarOrden(evento: Event): void {
    this.orden.set((evento.target as HTMLSelectElement).value);
    this.pagina.set(1);
    this.cargar();
  }

  protected cambiarPorPagina(evento: Event): void {
    this.porPagina.set(Number((evento.target as HTMLSelectElement).value));
    this.pagina.set(1);
    this.cargar();
  }

  protected alternarDireccion(): void {
    this.direccion.update((valor) => (valor === 'ASC' ? 'DESC' : 'ASC'));
    this.pagina.set(1);
    this.cargar();
  }

  protected irAPagina(numero: number): void {
    if (numero < 1 || numero > this.totalPaginas() || numero === this.pagina()) return;

    this.pagina.set(numero);
    this.cargar();
  }

  protected abrirNuevo(): void {
    this.productoEditando.set(null);
    this.erroresServidor.set({});
    this.form.reset({
      nombre: '',
      descripcion: '',
      precio: 0,
      stock: 0,
      activo: true,
    });
    this.modalAbierto.set(true);
  }

  protected abrirEditar(producto: Producto): void {
    this.productoEditando.set(producto);
    this.erroresServidor.set({});
    this.form.reset({
      nombre: producto.nombre,
      descripcion: producto.descripcion ?? '',
      precio: Number(producto.precio),
      stock: producto.stock,
      activo: producto.activo,
    });
    this.modalAbierto.set(true);
  }

  protected cerrarModal(): void {
    if (this.guardando()) return;

    this.modalAbierto.set(false);
  }

  protected guardar(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const valores = this.form.getRawValue();
    const payload: ProductoPayload = {
      nombre: (valores.nombre ?? '').trim(),
      descripcion: (valores.descripcion ?? '').trim() || null,
      precio: Number(valores.precio),
      stock: Number(valores.stock),
      activo: Boolean(valores.activo),
    };

    const editando = this.productoEditando();
    this.guardando.set(true);
    this.erroresServidor.set({});

    const peticion = editando
      ? this.api.actualizar(editando.id, payload)
      : this.api.crear(payload);

    peticion
      .pipe(
        finalize(() => this.guardando.set(false)),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe({
        next: () => {
          this.modalAbierto.set(false);
          this.notificar(
            'ok',
            editando ? 'Producto actualizado correctamente.' : 'Producto creado correctamente.',
          );
          this.cargar();
          this.cargarEstadisticas();
        },
        error: (error: HttpErrorResponse) => {
          this.erroresServidor.set(erroresDeCampo(error));
          this.notificar('error', mensajeDeError(error));

          if (!error.status) {
            this.errorConexion.set(mensajeDeError(error));
          }
        },
      });
  }

  protected pedirEliminar(producto: Producto): void {
    this.productoAEliminar.set(producto);
  }

  protected cancelarEliminar(): void {
    if (this.eliminando()) return;

    this.productoAEliminar.set(null);
  }

  protected confirmarEliminacion(): void {
    const producto = this.productoAEliminar();
    if (!producto) return;

    this.eliminando.set(true);

    this.api
      .eliminar(producto.id)
      .pipe(
        finalize(() => this.eliminando.set(false)),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe({
        next: () => {
          this.productoAEliminar.set(null);
          this.notificar('ok', `"${producto.nombre}" fue eliminado.`);

          if (this.productos().length === 1 && this.pagina() > 1) {
            this.pagina.update((numero) => numero - 1);
          }

          this.cargar();
          this.cargarEstadisticas();
        },
        error: (error: HttpErrorResponse) => {
          this.notificar('error', mensajeDeError(error));
        },
      });
  }

  protected mensajeError(campo: CampoFormulario): string | null {
    const erroresServidor = this.erroresServidor()[campo];
    if (erroresServidor && erroresServidor.length > 0) return erroresServidor[0];

    const control = this.form.get(campo);
    if (!control || control.valid || !(control.touched || control.dirty)) return null;

    if (control.hasError('required')) return 'Este campo es obligatorio.';

    if (control.hasError('minlength')) {
      const error = control.getError('minlength') as { requiredLength: number };
      return `Mínimo ${error.requiredLength} caracteres.`;
    }

    if (control.hasError('maxlength')) {
      const error = control.getError('maxlength') as { requiredLength: number };
      return `Máximo ${error.requiredLength} caracteres.`;
    }

    if (control.hasError('min')) {
      return campo === 'precio'
        ? 'El precio debe ser mayor que cero.'
        : 'El stock no puede ser negativo.';
    }

    return 'Valor inválido.';
  }

  protected longitudDescripcion(): number {
    return this.form.controls.descripcion.value?.length ?? 0;
  }

  protected formatearNumero(valor: number): string {
    return this.formatoNumero.format(valor);
  }

  protected formatearMoneda(valor: number): string {
    return this.formatoMoneda.format(valor);
  }

  protected formatearFecha(iso: string): string {
    return this.formatoFecha.format(new Date(iso));
  }

  protected totalTexto(): string {
    const datos = this.estadisticas();

    return datos ? this.formatearNumero(datos.total) : '—';
  }

  protected activosTexto(): string {
    const datos = this.estadisticas();

    return datos ? this.formatearNumero(datos.activos) : '—';
  }

  protected stockTexto(): string {
    const datos = this.estadisticas();

    return datos ? this.formatearNumero(datos.stockTotal) : '—';
  }

  protected inventarioTexto(): string {
    const datos = this.estadisticas();

    return datos ? this.formatearMoneda(datos.valorInventario) : '—';
  }

  private notificar(tipo: 'ok' | 'error', texto: string): void {
    const toast: Toast = { id: Date.now() + Math.random(), tipo, texto };

    this.toasts.update((lista) => [...lista, toast]);

    setTimeout(() => {
      this.toasts.update((lista) => lista.filter((elemento) => elemento.id !== toast.id));
    }, 4500);
  }
}
