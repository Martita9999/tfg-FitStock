import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { ResumenService, ResumenData } from '../../services/resumen.service';
import { MaterialesService } from '../../services/materiales.service';
import { PrestamosService } from '../../services/prestamos.service';
import { ComprasService } from '../../services/compras.service';
import { Material, Prestamo, Compra } from '../../interfaces/app.interfaces';
import { UsuarioService } from '../../services/usuario';
import { Usuario } from '../../interfaces/app.interfaces';

/*
 * DashboardHomeComponent: página principal del panel.
 * Admin/entrenadores: resumen con incidencias, stock bajo, máquinas, gastos.
 * Clientes: máquinas operativas/averiadas, préstamos activos y compras.
 * Incluye formulario de cambio de contraseña.
 */
@Component({
  selector: 'app-dashboard-home',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './dashboard-home.html',
  styleUrl: './dashboard-home.css'
})
export class DashboardHomeComponent implements OnInit {
  private resumenService = inject(ResumenService);
  private materialesService = inject(MaterialesService);
  private prestamosService = inject(PrestamosService);
  private comprasService = inject(ComprasService);
  private usuarioService = inject(UsuarioService);

  user: Usuario | null = null;
  resumen: ResumenData | null = null;
  maquinas: Material[] = [];
  maquinasOperativas = 0;
  misPrestamos: Prestamo[] = [];
  compras: Compra[] = [];
  totalGastado = 0;
  totalUsuarios = 0;
  prestamosPendientes: Prestamo[] = [];
  error = '';
  successMsg = '';

  currentPassword = '';
  newPassword = '';
  showPasswordForm = false;
  showGastosModal = false;                                            // Controla apertura/cierre del modal de todas las compras

  ngOnInit() {
    const userStr = localStorage.getItem('user');
    if (userStr) {
      this.user = JSON.parse(userStr);
    }
    if (this.user?.rol === 'cliente') {
      this.cargarDatosCliente();                                         // Cliente: carga vista simplificada
    } else {
      this.cargarResumen();                                              // Admin/entrenador: carga resumen completo
    }
  }

  cargarResumen() {
    this.resumenService.obtenerResumen().subscribe({
      next: (data) => this.resumen = data,
      error: () => this.error = 'Error al cargar el resumen'
    });
    this.prestamosService.getPrestamos().subscribe({
      next: (data: Prestamo[]) => {
        this.prestamosPendientes = data.filter(p => !p.devolucion);       // Filtra solo activos
      },
      error: () => {}
    });
    this.usuarioService.getUsuarios().subscribe({
      next: (data: Usuario[]) => this.totalUsuarios = data.length,
      error: () => {}
    });
  }

  private sortByIdTag(a: Material, b: Material): number {
    const tagA = a.id_tag_material || '';
    const tagB = b.id_tag_material || '';
    return tagA.localeCompare(tagB, undefined, { numeric: true });
  }

  cargarDatosCliente() {
    this.materialesService.getMateriales('maquina').subscribe({
      next: (data: Material[]) => {
        this.maquinasOperativas = data.filter(m => m.estado === 'operativo').length;  // Contador de operativas para stats
        this.maquinas = data.filter(m => m.estado === 'averiado' || m.estado === 'en_reparacion').sort(this.sortByIdTag);  // Solo incidencias en la lista
      },
      error: () => this.error = 'Error al cargar máquinas'
    });
    this.prestamosService.getPrestamos().subscribe({
      next: (data: Prestamo[]) => {
        this.misPrestamos = data.filter(p => p.id_usuario === this.user?.id && !p.devolucion);  // Solo préstamos pendientes del cliente
      },
      error: () => this.error = 'Error al cargar préstamos'
    });
    this.comprasService.getCompras().subscribe({
      next: (data: Compra[]) => {
        this.compras = data.sort((a, b) => new Date(b.fecha_compra).getTime() - new Date(a.fecha_compra).getTime());  // Más reciente primero
        this.totalGastado = data.reduce((sum, c) => sum + (+c.total || 0), 0);  // Suma total para la card
      },
      error: () => this.error = 'Error al cargar compras'
    });
  }

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
