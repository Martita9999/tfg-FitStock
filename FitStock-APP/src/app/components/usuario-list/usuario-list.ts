import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { UsuarioService } from '../../services/usuario';
import { ComprasService } from '../../services/compras.service';
import { PrestamosService } from '../../services/prestamos.service';
import { IncidenciasService } from '../../services/incidencias.service';
import { Usuario } from '../../interfaces/app.interfaces';
import { ConfirmService } from '../../services/confirm.service';
import { ToastService } from '../../services/toast.service';

/*
 * UsuarioList: CRUD de usuarios del sistema.
 * Filtro por rol desde URL (/admin/usuarios/:rol).
 * Título dinámico, creación, edición, forzar cambio de contraseña y eliminación.
 */
@Component({
  selector: 'app-usuario-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './usuario-list.html',
  styleUrl: './usuario-list.css',
})
export class UsuarioList implements OnInit {
  private usuarioService = inject(UsuarioService);
  private comprasService = inject(ComprasService);
  private prestamosService = inject(PrestamosService);
  private incidenciasService = inject(IncidenciasService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private confirmService = inject(ConfirmService);
  private toastService = inject(ToastService);
  lista: Usuario[] = [];
  listaFiltrada: Usuario[] = [];
  userRole = '';
  filtroRol = '';
  busquedaEmail = '';

  /* resumenUsuario: mapa de resúmenes por id de usuario */
  resumenUsuario: Record<number, { totalGastado: number; prestamosPendientes: number; tieneIncidencias: boolean }> = {};

  /* comprasPorUsuario: mapa de compras por id de usuario para el modal */
  comprasPorUsuario: Record<number, any[]> = {};

  /* Modal de compras */
  showComprasModal = false;
  selectedUserCompras: any[] = [];
  selectedUserName = '';

  showModal = false;
  newUser = { nombre: '', email: '', password: '', rol: 'cliente' };
  error = '';

  showEditModal = false;
  editUser: Usuario | null = null;
  editUserData = { nombre: '', email: '', rol: 'cliente' };

  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.route.paramMap.subscribe(params => {
      this.filtroRol = params.get('rol') || '';                           // Lee :rol de la URL
      if (this.lista.length > 0) this.aplicarFiltro();
    });
    this.loadUsuarios();
    this.cargarResumenes();
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

  aplicarFiltro() {
    let filtrados = this.filtroRol
      ? this.lista.filter(u => u.rol === this.filtroRol)
      : [...this.lista];
    if (this.userRole === 'entrenador') {
      filtrados = filtrados.filter(u => u.rol !== 'admin');
    }
    if (this.busquedaEmail) {
      const q = this.busquedaEmail.toLowerCase();
      filtrados = filtrados.filter(u => u.email.toLowerCase().includes(q));
    }
    this.listaFiltrada = filtrados;
  }

  irATodos() {
    this.router.navigate(['/admin/usuarios']);
  }

  /* cargarResumenes: obtiene compras, préstamos e incidencias para calcular resumen por usuario */
  private cargarResumenes() {
    /* Iniciamos todos en 0/false */
    this.resumenUsuario = {};
    this.comprasPorUsuario = {};

    this.comprasService.getCompras().subscribe(compras => {
      for (const c of compras) {
        if (!this.resumenUsuario[c.id_usuario]) {
          this.resumenUsuario[c.id_usuario] = { totalGastado: 0, prestamosPendientes: 0, tieneIncidencias: false };
        }
        this.resumenUsuario[c.id_usuario].totalGastado += c.total;
        if (!this.comprasPorUsuario[c.id_usuario]) {
          this.comprasPorUsuario[c.id_usuario] = [];
        }
        this.comprasPorUsuario[c.id_usuario].push(c);
      }
    });

    this.prestamosService.getPrestamos().subscribe(prestamos => {
      for (const p of prestamos) {
        if (!p.id_usuario) continue;
        if (!this.resumenUsuario[p.id_usuario]) {
          this.resumenUsuario[p.id_usuario] = { totalGastado: 0, prestamosPendientes: 0, tieneIncidencias: false };
        }
        if (!p.devolucion) {
          this.resumenUsuario[p.id_usuario].prestamosPendientes++;
        }
      }
    });

    this.incidenciasService.getIncidencias().subscribe(incidencias => {
      for (const i of incidencias) {
        if (!i.id_user_rep) continue;
        if (!this.resumenUsuario[i.id_user_rep]) {
          this.resumenUsuario[i.id_user_rep] = { totalGastado: 0, prestamosPendientes: 0, tieneIncidencias: false };
        }
        this.resumenUsuario[i.id_user_rep].tieneIncidencias = true;
      }
    });
  }

  abrirComprasModal(userId: number, userName: string) {
    this.selectedUserCompras = this.comprasPorUsuario[userId] || [];
    this.selectedUserName = userName;
    this.showComprasModal = true;
  }

  cerrarComprasModal() {
    this.showComprasModal = false;
    this.selectedUserCompras = [];
    this.selectedUserName = '';
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

  /* forzarCambioPassword: marca usuario para que cambie contraseña en próximo login */
  async forzarCambioPassword(u: Usuario) {
    const ok = await this.confirmService.confirm(`¿Enviar solicitud de cambio de contraseña a "${u.nombre}"?`);
    if (!ok) return;
    this.usuarioService.forzarCambioPassword(u.id).subscribe({
      next: () => {
        u.forzar_cambio_password = 1;                                     // Actualiza visualmente
        this.error = '';
      },
      error: () => { this.error = 'Error al enviar la solicitud'; }
    });
  }

  async borrarUsuario(id: number, nombre: string) {
    const ok = await this.confirmService.confirm(`¿Borrar usuario "${nombre}"?`);
    if (!ok) return;
    this.usuarioService.deleteUsuario(id).subscribe({
      next: () => this.loadUsuarios(),
      error: () => this.toastService.show('Error al borrar el usuario', 'error')
    });
  }
}
