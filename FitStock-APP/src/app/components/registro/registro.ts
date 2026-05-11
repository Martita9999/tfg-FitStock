import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { UsuarioService } from '../../services/usuario';

// Componente de registro de nuevos usuarios
@Component({
  selector: 'app-registro',
  imports: [FormsModule],
  templateUrl: './registro.html',
  styleUrl: './registro.css'
})
export class RegistroComponent {
  private usuarioService = inject(UsuarioService);    // Servicio de autenticación
  private router = inject(Router);                    // Router para navegación

  nombre = '';      // Nombre del formulario (two-way binding)
  email = '';       // Email del formulario (two-way binding)
  password = '';    // Contraseña del formulario (two-way binding)
  error = '';       // Mensaje de error a mostrar
  success = false;  // Indica si el registro fue exitoso

  // Envía los datos de registro al servidor
  registrar() {
    this.error = '';
    this.success = false;
    this.usuarioService.registro(this.nombre, this.email, this.password).subscribe({
      next: (res) => {
        if (res.success) {
          this.success = true;                                          // Muestra mensaje de éxito
          setTimeout(() => this.router.navigate(['/login']), 1500);    // Redirige al login tras 1.5s
        } else {
          this.error = res.error || 'Error';
        }
      },
      error: () => {
        this.error = 'Error de conexión';
      }
    });
  }

  // Navega a la página de login
  goToLogin() {
    this.router.navigate(['/login']);
  }
}
