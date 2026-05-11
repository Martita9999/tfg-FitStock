import { Component, inject, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { Usuario, UsuarioService } from '../../services/usuario';

// Componente de perfil de usuario (ver y editar datos personales)
@Component({
  selector: 'app-perfil',
  imports: [FormsModule],
  templateUrl: './perfil.html',
  styleUrl: './perfil.css'
})
export class PerfilComponent implements OnInit {
  private usuarioService = inject(UsuarioService);    // Servicio de autenticación
  private router = inject(Router);                    // Router para navegación

  usuario: Usuario = { id: 0, nombre: '', email: '', rol: '' };   // Datos del usuario
  password = '';     // Nueva contraseña (opcional)
  success = false;   // Indica si la actualización fue exitosa
  error = '';        // Mensaje de error

  // Al iniciar, carga los datos del perfil desde la API
  ngOnInit() {
    this.usuarioService.getPerfil().subscribe({
      next: (data) => {
        this.usuario = data;    // Asigna los datos recibidos
      },
      error: () => {
        this.router.navigate(['/login']);   // Si no está autenticado, redirige al login
      }
    });
  }

  // Guarda los cambios del perfil
  guardar() {
    this.error = '';
    this.success = false;
    const data: any = { nombre: this.usuario.nombre, email: this.usuario.email };
    if (this.password) {                        // Si se especificó nueva contraseña
      data.password = this.password;             // La incluye en la petición
    }

    this.usuarioService.updatePerfil(data).subscribe({
      next: () => {
        this.success = true;     // Muestra mensaje de éxito
        this.password = '';      // Limpia el campo de contraseña
      },
      error: () => {
        this.error = 'Error al guardar';
      }
    });
  }

  // Cierra la sesión y redirige al login
  logout() {
    this.usuarioService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
