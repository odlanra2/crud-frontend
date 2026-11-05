import { HttpErrorResponse } from '@angular/common/http';
import {
  AfterViewInit,
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  ElementRef,
  computed,
  inject,
  signal,
  viewChild,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { finalize } from 'rxjs';

import { AuthService } from '../../core/auth.service';
import { erroresDeCampo, mensajeDeError } from '../../core/errores';

type Modo = 'login' | 'registro';
type CampoFormulario = 'nombre' | 'email' | 'password' | 'confirmar';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Login implements AfterViewInit {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);
  private readonly emailInput = viewChild<ElementRef<HTMLInputElement>>('emailInput');

  protected readonly modo = signal<Modo>('login');
  protected readonly cargando = signal(false);
  protected readonly error = signal('');
  protected readonly erroresCampo = signal<Record<string, string[]>>({});
  protected readonly verPassword = signal(false);
  protected readonly aviso = this.auth.aviso;

  protected readonly esRegistro = computed(() => this.modo() === 'registro');
  protected readonly titulo = computed(() =>
    this.esRegistro() ? 'Crea tu cuenta' : 'Bienvenido de nuevo',
  );
  protected readonly subtitulo = computed(() =>
    this.esRegistro()
      ? 'Regístrate una vez y empieza a gestionar el inventario.'
      : 'Inicia sesión para acceder al CRUD de productos.',
  );

  protected readonly form = this.fb.nonNullable.group({
    nombre: [''],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(72)]],
    confirmar: [''],
  });

  ngAfterViewInit(): void {
    this.emailInput()?.nativeElement.focus();
  }

  protected cambiarModo(modo: Modo): void {
    if (this.cargando() || modo === this.modo()) return;

    this.modo.set(modo);
    this.error.set('');
    this.erroresCampo.set({});
    this.form.patchValue({ password: '', confirmar: '' });
  }

  protected mensajeCampo(campo: CampoFormulario): string {
    const erroresServidor = this.erroresCampo()[campo];
    if (erroresServidor && erroresServidor.length > 0) return erroresServidor[0];

    const control = this.form.get(campo);
    if (!control || control.valid || !(control.touched || control.dirty)) return '';

    if (control.hasError('required')) return 'Este campo es obligatorio.';
    if (control.hasError('email')) return 'Escribe un email válido.';

    if (control.hasError('minlength')) {
      const error = control.getError('minlength') as { requiredLength: number };
      return `Mínimo ${error.requiredLength} caracteres.`;
    }

    if (control.hasError('maxlength')) {
      const error = control.getError('maxlength') as { requiredLength: number };
      return `Máximo ${error.requiredLength} caracteres.`;
    }

    return 'Valor inválido.';
  }

  protected mensajeConfirmar(): string {
    if (!this.esRegistro()) return '';

    const confirmar = this.form.controls.confirmar;
    if (!confirmar.touched && !confirmar.dirty) return '';

    return this.form.controls.password.value === confirmar.value
      ? ''
      : 'Las contraseñas no coinciden.';
  }

  protected enviar(): void {
    const esRegistro = this.esRegistro();
    const clavesDistintas =
      esRegistro && this.form.controls.password.value !== this.form.controls.confirmar.value;

    if (this.form.invalid || clavesDistintas) {
      this.form.markAllAsTouched();
      this.error.set('Revisa los campos marcados antes de continuar.');
      return;
    }

    const valores = this.form.getRawValue();
    this.cargando.set(true);
    this.error.set('');
    this.erroresCampo.set({});

    const peticion = esRegistro
      ? this.auth.registrar({
          email: valores.email.trim(),
          password: valores.password,
          nombre: valores.nombre.trim(),
        })
      : this.auth.iniciarSesion({
          email: valores.email.trim(),
          password: valores.password,
        });

    peticion
      .pipe(
        finalize(() => this.cargando.set(false)),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe({
        next: () => {
          void this.router.navigate(['/']);
        },
        error: (fallo: HttpErrorResponse) => {
          this.erroresCampo.set(erroresDeCampo(fallo));
          this.error.set(this.mensajeDeFallo(fallo));
        },
      });
  }

  private mensajeDeFallo(error: HttpErrorResponse): string {
    if (error.status === 401) return 'Email o contraseña incorrectos.';
    if (error.status === 409) return 'Ese email ya está registrado. Prueba a iniciar sesión.';

    return mensajeDeError(error);
  }
}
