import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { ResumenService, ResumenData } from '../../services/resumen.service';
import { MaterialesService } from '../../services/materiales.service';
import { PrestamosService } from '../../services/prestamos.service';
import { ComprasService } from '../../services/compras.service';
import { Material, Prestamo, Compra } from '../../interfaces/app.interfaces';
import { UsuarioService, Usuario } from '../../services/usuario';

@Component({
  selector: 'app-dashboard-home',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './dashboard-home.html',
  styleUrl: './dashboard-home.css'
})
// Componente de la página de inicio del panel de administración.
// Muestra un resumen general para administradores/entrenadores o datos
// específicos del cliente (máquinas, préstamos, compras)
export class DashboardHomeComponent implements OnInit {
  // Servicio para obtener el resumen general del panel (incidencias, stock bajo, máquinas)
  private resumenService = inject(ResumenService);
  // Servicio para consultar materiales (máquinas, equipos deportivos)
  private materialesService = inject(MaterialesService);
  // Servicio para gestionar préstamos de material
  private prestamosService = inject(PrestamosService);
  // Servicio para gestionar compras de productos
  private comprasService = inject(ComprasService);
  // Servicio para obtener datos del usuario actual y gestionar sesión
  private usuarioService = inject(UsuarioService);

  user: Usuario | null = null;            // Usuario autenticado
  resumen: ResumenData | null = null;     // Datos del resumen general (admin/entrenador)
  maquinas: Material[] = [];              // Máquinas averiadas o en reparación
  maquinasOperativas = 0;                 // Conteo de máquinas en estado operativo
  misPrestamos: Prestamo[] = [];          // Préstamos del cliente actual
  compras: Compra[] = [];                 // Compras realizadas por el cliente
  totalUsuarios = 0;                      // Total de usuarios registrados
  prestamosPendientes: Prestamo[] = [];   // Préstamos aún no devueltos
  error = '';                             // Mensaje de error
  successMsg = '';                        // Mensaje de éxito

  currentPassword = '';     // Contraseña actual (formulario de cambio)
  newPassword = '';         // Nueva contraseña (formulario de cambio)
  showPasswordForm = false; // Controla la visibilidad del formulario de cambio de contraseña

  // Al iniciar, carga el usuario desde localStorage y decide qué datos cargar
  // según el rol: resumen general (admin/entrenador) o datos de cliente
  ngOnInit() {
    const userStr = localStorage.getItem('user');
    if (userStr) {
      this.user = JSON.parse(userStr);
    }
    if (this.user?.rol === 'cliente') {
      this.cargarDatosCliente();
    } else {
      this.cargarResumen();
    }
  }

  // Carga los datos del resumen general (incidencias, stock bajo, máquinas),
  // los préstamos pendientes y el total de usuarios del sistema
  cargarResumen() {
    this.resumenService.obtenerResumen().subscribe({
      next: (data) => this.resumen = data,
      error: () => this.error = 'Error al cargar el resumen'
    });
    this.prestamosService.getPrestamos().subscribe({
      next: (data: Prestamo[]) => {
        this.prestamosPendientes = data.filter(p => !p.devolucion);
      },
      error: () => {}
    });
    this.usuarioService.getUsuarios().subscribe({
      next: (data: Usuario[]) => this.totalUsuarios = data.length,
      error: () => {}
    });
  }

  // Carga los datos específicos del cliente: máquinas operativas/averiadas,
  // sus préstamos activos y las compras realizadas
  // Ordena materiales por su etiqueta ID (id_tag_material) usando comparación numérica
  private sortByIdTag(a: Material, b: Material): number {
    const tagA = a.id_tag_material || '';
    const tagB = b.id_tag_material || '';
    return tagA.localeCompare(tagB, undefined, { numeric: true });
  }

  // Carga datos específicos del cliente: conteo de máquinas operativas,
  // lista de máquinas averiadas/en reparación, préstamos activos y compras realizadas
  cargarDatosCliente() {
    this.materialesService.getMateriales('maquina').subscribe({
      next: (data: Material[]) => {
        this.maquinasOperativas = data.filter(m => m.estado === 'operativo').length;
        this.maquinas = data.filter(m => m.estado === 'averiado' || m.estado === 'en_reparacion').sort(this.sortByIdTag);
      },
      error: () => this.error = 'Error al cargar máquinas'
    });
    this.prestamosService.getPrestamos().subscribe({
      next: (data: Prestamo[]) => {
        this.misPrestamos = data.filter(p => p.id_usuario === this.user?.id);
      },
      error: () => this.error = 'Error al cargar préstamos'
    });
    this.comprasService.getCompras().subscribe({
      next: (data: Compra[]) => this.compras = data,
      error: () => this.error = 'Error al cargar compras'
    });
  }

  // Cambia la contraseña del usuario autenticado: valida que ambos campos
  // estén rellenos y llama al servicio correspondiente
  cambiarPassword() {
    this.error = '';
    this.successMsg = '';
    if (!this.currentPassword.trim()) {
      this.error = 'Introduce tu contraseña actual';
      return;
    }
    if (!this.newPassword.trim()) {
      this.error = 'Introduce la nueva contraseña';
      return;
    }
    this.usuarioService.cambiarPassword(this.currentPassword.trim(), this.newPassword.trim()).subscribe({
      next: () => {
        this.successMsg = 'Contraseña actualizada correctamente';
        this.currentPassword = '';
        this.newPassword = '';
        this.showPasswordForm = false;
      },
      error: (err) => {
        const backendError = err.error?.error;
        this.error = backendError || 'Error al cambiar la contraseña';
      }
    });
  }
}
