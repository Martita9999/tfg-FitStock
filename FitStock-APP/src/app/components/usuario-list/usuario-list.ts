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
// Componente que lista, crea, edita y elimina usuarios del sistema,
// con filtro por rol y opción de forzar cambio de contraseña
export class UsuarioList implements OnInit {
  private usuarioService = inject(UsuarioService);     // Servicio de usuarios
  private route = inject(ActivatedRoute);               // Ruta activa para leer parámetros (rol)
  private router = inject(Router);                      // Router para navegación programática
  lista: Usuario[] = [];           // Lista completa de usuarios desde la API
  listaFiltrada: Usuario[] = [];   // Lista filtrada según el rol seleccionado
  userRole = '';                   // Rol del usuario autenticado
  filtroRol = '';                  // Rol por el que se filtra (desde la URL)

  showModal = false;                                                     // Control del modal de creación
  newUser = { nombre: '', email: '', password: '', rol: 'cliente' };     // Datos del nuevo usuario
  error = '';                      // Mensaje de error

  showEditModal = false;                                  // Control del modal de edición
  editUser: Usuario | null = null;                        // Usuario en edición
  editUserData = { nombre: '', email: '', rol: 'cliente' };  // Datos editados del usuario

  // Al iniciar, se suscribe al usuario actual, lee el parámetro de ruta 'rol'
  // y carga la lista de usuarios
  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.route.paramMap.subscribe(params => {
      this.filtroRol = params.get('rol') || '';
      if (this.lista.length > 0) this.aplicarFiltro();
    });
    this.loadUsuarios();
  }

  // Devuelve el título dinámico de la página según el filtro de rol activo
  get tituloPagina(): string {
    if (!this.filtroRol) return 'Usuarios';
    const nombres: Record<string, string> = { admin: 'Administradores', entrenador: 'Entrenadores', cliente: 'Clientes' };
    return nombres[this.filtroRol] || 'Usuarios';
  }

  // Carga todos los usuarios desde la API y aplica el filtro de rol si existe
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

  // Navega a la ruta de usuarios sin filtro para mostrar todos
  irATodos() {
    this.router.navigate(['/admin/usuarios']);
  }

  // Abre el modal de creación de usuario con valores por defecto
  abrirModal() {
    this.newUser = { nombre: '', email: '', password: '', rol: 'cliente' };
    this.error = '';
    this.showModal = true;
  }

  // Cierra el modal de creación y limpia errores
  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  // Valida los campos obligatorios y envía los datos para crear un nuevo usuario
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

  // Abre el modal de edición precargando los datos del usuario seleccionado
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

  // Cierra el modal de edición y limpia errores
  cerrarEditar() {
    this.showEditModal = false;
    this.editUser = null;
    this.error = '';
  }

  // Valida y envía los cambios del usuario editado al backend
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

  // Confirma y elimina un usuario por su ID tras la confirmación del usuario
  borrarUsuario(id: number, nombre: string) {
    if (!confirm(`¿Borrar usuario "${nombre}"?`)) return;
    this.usuarioService.deleteUsuario(id).subscribe({
      next: () => this.loadUsuarios(),
      error: () => alert('Error al borrar el usuario')
    });
  }
}
