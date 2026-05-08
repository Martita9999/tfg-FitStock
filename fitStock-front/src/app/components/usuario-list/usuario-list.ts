import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { UsuarioService } from '../../services/usuario';
import { Usuario } from '../../interfaces/app.interfaces';

// Componente que lista y gestiona los usuarios del sistema
@Component({
  selector: 'app-usuario-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './usuario-list.html',
  styleUrl: './usuario-list.css',
})
export class UsuarioList implements OnInit {
  private usuarioService = inject(UsuarioService);   // Servicio de usuarios
  lista: Usuario[] = [];                               // Lista de usuarios
  userRole = '';                                       // Rol del usuario actual (para permisos)

  showModal = false;                                                         // Control del modal
  newUser = { nombre: '', email: '', password: '', rol: 'cliente' };        // Datos del nuevo usuario
  error = '';                                           // Mensaje de error

  // Al iniciar, obtiene el rol y carga los usuarios
  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadUsuarios();
  }

  // Carga la lista de usuarios desde la API
  loadUsuarios() {
    this.usuarioService.getUsuarios().subscribe(data => this.lista = data);
  }

  // Abre el modal de creación
  abrirModal() {
    this.newUser = { nombre: '', email: '', password: '', rol: 'cliente' };
    this.error = '';
    this.showModal = true;
  }

  // Cierra el modal
  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  // Envía los datos para crear un nuevo usuario
  crearUsuario() {
    this.error = '';
    if (!this.newUser.nombre || !this.newUser.email || !this.newUser.password) {   // Validación: todos los campos obligatorios
      this.error = 'Todos los campos son obligatorios';
      return;
    }
    this.usuarioService.createUsuario(this.newUser).subscribe({
      next: () => {
        this.cerrarModal();
        this.loadUsuarios();
      },
      error: () => {
        this.error = 'Error al crear el usuario';
      }
    });
  }

  // Confirma y elimina un usuario
  borrarUsuario(id: number, nombre: string) {
    if (!confirm(`¿Borrar usuario "${nombre}"?`)) return;    // Confirmación del usuario
    this.usuarioService.deleteUsuario(id).subscribe({
      next: () => this.loadUsuarios(),
      error: () => alert('Error al borrar el usuario')
    });
  }
}
