import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { UsuarioService } from '../../services/usuario';
import { Usuario } from '../../interfaces/app.interfaces';

@Component({
  selector: 'app-usuario-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './usuario-list.html',
  styleUrl: './usuario-list.css',
})
export class UsuarioList implements OnInit {
  private usuarioService = inject(UsuarioService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  lista: Usuario[] = [];
  listaFiltrada: Usuario[] = [];
  userRole = '';
  filtroRol = '';

  showModal = false;
  newUser = { nombre: '', email: '', password: '', rol: 'cliente' };
  error = '';

  showEditModal = false;
  editUser: Usuario | null = null;
  editUserData = { nombre: '', email: '', rol: 'cliente' };

  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.route.paramMap.subscribe(params => {
      this.filtroRol = params.get('rol') || '';
      if (this.lista.length > 0) this.aplicarFiltro();
    });
    this.loadUsuarios();
  }

  get tituloPagina(): string {
    if (!this.filtroRol) return 'Usuarios';
    const nombres: Record<string, string> = { admin: 'Administradores', entrenador: 'Entrenadores', cliente: 'Clientes' };
    return nombres[this.filtroRol] || 'Usuarios';
  }

  loadUsuarios() {
    this.usuarioService.getUsuarios().subscribe(data => {
      this.lista = data;
      this.aplicarFiltro();
    });
  }

  private aplicarFiltro() {
    if (this.filtroRol) {
      this.listaFiltrada = this.lista.filter(u => u.rol === this.filtroRol);
    } else {
      this.listaFiltrada = [...this.lista];
    }
  }

  irATodos() {
    this.router.navigate(['/admin/usuarios']);
  }

  abrirModal() {
    this.newUser = { nombre: '', email: '', password: '', rol: 'cliente' };
    this.error = '';
    this.showModal = true;
  }

  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  crearUsuario() {
    this.error = '';
    if (!this.newUser.nombre || !this.newUser.email || !this.newUser.password) {
      this.error = 'Todos los campos son obligatorios';
      return;
    }
    this.usuarioService.createUsuario({
      nombre: this.newUser.nombre,
      email: this.newUser.email,
      password: this.newUser.password,
      rol: this.newUser.rol
    }).subscribe({
      next: () => {
        this.cerrarModal();
        this.loadUsuarios();
      },
      error: () => {
        this.error = 'Error al crear el usuario';
      }
    });
  }

  abrirEditar(u: Usuario) {
    this.editUser = u;
    this.editUserData = {
      nombre: u.nombre,
      email: u.email,
      rol: u.rol
    };
    this.error = '';
    this.showEditModal = true;
  }

  cerrarEditar() {
    this.showEditModal = false;
    this.editUser = null;
    this.error = '';
  }

  guardarEditar() {
    if (!this.editUser) return;
    this.error = '';
    if (!this.editUserData.nombre || !this.editUserData.email) {
      this.error = 'Nombre y email son obligatorios';
      return;
    }
    const data: any = {
      nombre: this.editUserData.nombre,
      email: this.editUserData.email,
      rol: this.editUserData.rol
    };
    this.usuarioService.updateUsuario(this.editUser.id, data).subscribe({
      next: () => { this.cerrarEditar(); this.loadUsuarios(); },
      error: () => { this.error = 'Error al actualizar el usuario'; }
    });
  }

  // Solicita al backend que fuerce al usuario a cambiar su contraseña
  // en el próximo inicio de sesión. Actualiza localmente el indicador.
  forzarCambioPassword(u: Usuario) {
    if (!confirm(`¿Enviar solicitud de cambio de contraseña a "${u.nombre}"?`)) return;
    this.usuarioService.forzarCambioPassword(u.id).subscribe({
      next: () => {
        u.forzar_cambio_password = 1;
        this.error = '';
      },
      error: () => { this.error = 'Error al enviar la solicitud'; }
    });
  }

  borrarUsuario(id: number, nombre: string) {
    if (!confirm(`¿Borrar usuario "${nombre}"?`)) return;
    this.usuarioService.deleteUsuario(id).subscribe({
      next: () => this.loadUsuarios(),
      error: () => alert('Error al borrar el usuario')
    });
  }
}
