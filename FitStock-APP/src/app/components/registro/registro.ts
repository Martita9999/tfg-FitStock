import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { UsuarioService } from '../../services/usuario';

/*
 * RegistroComponent: formulario de registro de nuevos usuarios.
 * Al registrarse, redirige al login tras 1.5 segundos.
 */
@Component({
  selector: 'app-registro',
  imports: [FormsModule],
  templateUrl: './registro.html',
  styleUrl: './registro.css'
})
export class RegistroComponent {
  private usuarioService = inject(UsuarioService);
  private router = inject(Router);

  nombre = '';
  email = '';
  password = '';
  error = '';
  success = false;

  registrar() {
    this.error = '';
    this.success = false;
    this.usuarioService.registro(this.nombre, this.email, this.password).subscribe({
      next: (res) => {
        if (res.success) {
          this.success = true;
          setTimeout(() => this.router.navigate(['/login']), 1500);      // Redirige al login tras 1.5s
        } else {
          this.error = res.error || 'Error';
        }
      },
      error: () => {
        this.error = 'Error de conexión';
      }
    });
  }

  goToLogin() {
    this.router.navigate(['/login']);
  }
}
