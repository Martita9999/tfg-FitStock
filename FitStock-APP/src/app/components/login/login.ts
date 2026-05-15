import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { UsuarioService } from '../../services/usuario';

// Decorador de componente Angular: selector HTML, módulos importados,
// plantilla HTML y hoja de estilos asociada
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
  darkMode = false; // Indica si el tema oscuro está activo (persistido en localStorage)

  showPasswordModal = false;
  currentPassword = '';
  newPassword = '';
  passwordError = '';

  // Al iniciar, lee la preferencia de tema oscuro desde localStorage
  // y aplica el atributo `data-theme` al elemento raíz del documento
  constructor() {
    this.darkMode = localStorage.getItem('darkMode') === 'true';
    if (this.darkMode) {
      document.documentElement.setAttribute('data-theme', 'dark');
    }
  }

  // Alterna entre modo claro y oscuro, persiste la preferencia
  // en localStorage y aplica/elimina el atributo `data-theme`
  toggleDarkMode() {
    this.darkMode = !this.darkMode;
    localStorage.setItem('darkMode', String(this.darkMode));
    if (this.darkMode) {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  }

  login() {
    this.error = '';
    this.usuarioService.login(this.email, this.password).subscribe({
      next: (res) => {
        if (res.success) {
          // Si el usuario es cliente/entrenador y tiene pendiente un cambio
          // forzado de contraseña, muestra el modal para establecer una nueva
          if ((res.user?.rol === 'cliente' || res.user?.rol === 'entrenador') && res.user?.forzar_cambio_password === 1) {
            this.currentPassword = this.password;
            this.newPassword = '';
            this.passwordError = '';
            this.showPasswordModal = true;
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
