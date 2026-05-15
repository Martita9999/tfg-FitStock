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
// Componente de inicio de sesión con soporte de modo oscuro y cambio forzado de contraseña
export class LoginComponent {
  private usuarioService = inject(UsuarioService);   // Servicio de autenticación de usuarios
  private router = inject(Router);                   // Router para navegación programática

  email = '';               // Email del formulario (two-way binding)
  password = '';            // Contraseña del formulario (two-way binding)
  error = '';               // Mensaje de error a mostrar en la vista
  darkMode = false;         // Indica si el tema oscuro está activo (persistido en localStorage)

  showPasswordModal = false;    // Controla la visibilidad del modal de cambio de contraseña
  currentPassword = '';         // Contraseña actual (para verificar en el cambio forzado)
  newPassword = '';             // Nueva contraseña a establecer
  passwordError = '';           // Mensaje de error del modal de cambio de contraseña

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

  // Autentica al usuario con email y contraseña.
  // Si el usuario es cliente/entrenador con cambio forzado de contraseña pendiente,
  // muestra el modal para establecer una nueva; en caso contrario redirige al dashboard
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

  // Envía la nueva contraseña al backend para completar el cambio forzado.
  // Valida que la nueva contraseña no esté vacía antes de enviarla
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

  // Omite el cambio forzado de contraseña y accede al dashboard
  skipPassword() {
    this.router.navigate(['/admin/home']);
  }

  // Navega a la página de registro de nuevos usuarios
  goToRegistro() {
    this.router.navigate(['/registro']);
  }

  // Navega a la página de aterrizaje (portal público)
  goToPortal() {
    this.router.navigate(['/']);
  }
}
