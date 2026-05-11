import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { UsuarioService } from '../../services/usuario';

// Componente de inicio de sesión
@Component({
  selector: 'app-login',
  imports: [FormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css'
})
export class LoginComponent {
  private usuarioService = inject(UsuarioService);    // Servicio de autenticación
  private router = inject(Router);                    // Router para navegación

  email = '';       // Email del formulario (two-way binding)
  password = '';    // Contraseña del formulario (two-way binding)
  error = '';       // Mensaje de error a mostrar

  // Intenta iniciar sesión con los datos del formulario
  login() {
    this.error = '';
    this.usuarioService.login(this.email, this.password).subscribe({
      next: (res) => {
        if (res.success) {
          this.router.navigate(['/admin/inventario']);   // Redirige al panel admin
        } else {
          this.error = res.error || 'Error desconocido';
        }
      },
      error: () => {
        this.error = 'Error de conexión';
      }
    });
  }

  // Navega a la página de registro
  goToRegistro() {
    this.router.navigate(['/registro']);
  }
}
