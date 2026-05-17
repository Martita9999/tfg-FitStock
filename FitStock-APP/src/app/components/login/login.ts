import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { UsuarioService } from '../../services/usuario';

/*
 * LoginComponent: formulario de inicio de sesión.
 * Incluye modo oscuro, cambio forzado de contraseña y enlaces a portal/registro.
 */
@Component({
  selector: 'app-login',
  imports: [FormsModule, CommonModule],
  templateUrl: './login.html',
  styleUrl: './login.css'
})
export class LoginComponent {
  private usuarioService = inject(UsuarioService);
  private router = inject(Router);

  email = '';
  password = '';
  error = '';
  darkMode = false;

  showPasswordModal = false;                                             // Modal para cambio forzado de contraseña
  currentPassword = '';
  newPassword = '';
  passwordError = '';

  /* Al iniciar, restauramos la preferencia de modo oscuro */
  constructor() {
    this.darkMode = localStorage.getItem('darkMode') === 'true';
    if (this.darkMode) {
      document.documentElement.setAttribute('data-theme', 'dark');
    }
  }

  /* toggleDarkMode: alterna modo claro/oscuro y lo persiste en localStorage */
  toggleDarkMode() {
    this.darkMode = !this.darkMode;
    localStorage.setItem('darkMode', String(this.darkMode));
    if (this.darkMode) {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  }

  /* login: si el usuario tiene forzar_cambio_password=1, abre modal de cambio.
     Si no, redirige al dashboard. */
  login() {
    this.error = '';
    this.usuarioService.login(this.email, this.password).subscribe({
      next: (res) => {
        if (res.success) {
          if ((res.user?.rol === 'cliente' || res.user?.rol === 'entrenador') && res.user?.forzar_cambio_password === 1) {
            this.currentPassword = this.password;
            this.newPassword = '';
            this.passwordError = '';
            this.showPasswordModal = true;                               // Abrimos modal de cambio forzado
          } else {
            this.router.navigate(['/admin/home']);
          }
        } else {
          this.error = res.error || 'Error desconocido';
        }
      },
      error: () => {
        this.error = 'Error de conexión';
      }
    });
  }

  /* cambiarPassword: completa el cambio forzado de contraseña */
  cambiarPassword() {
    this.passwordError = '';
    if (!this.newPassword.trim()) {
      this.passwordError = 'Introduce la nueva contraseña';
      return;
    }
    this.usuarioService.cambiarPassword(this.currentPassword, this.newPassword.trim()).subscribe({
      next: () => {
        this.showPasswordModal = false;
        this.router.navigate(['/admin/home']);
      },
      error: (err) => {
        this.passwordError = err.error?.error || 'Error al cambiar la contraseña';
      }
    });
  }

  skipPassword() {
    this.router.navigate(['/admin/home']);
  }

  goToRegistro() {
    this.router.navigate(['/registro']);
  }

  goToPortal() {
    this.router.navigate(['/']);
  }
}
