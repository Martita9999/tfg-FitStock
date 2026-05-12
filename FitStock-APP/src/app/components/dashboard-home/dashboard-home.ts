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
  error = '';
  successMsg = '';

  newPassword = '';
  showPasswordForm = false;

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

  cargarResumen() {
    this.resumenService.obtenerResumen().subscribe({
      next: (data) => this.resumen = data,
      error: () => this.error = 'Error al cargar el resumen'
    });
  }

  cargarDatosCliente() {
    this.materialesService.getMateriales('maquina').subscribe({
      next: (data: Material[]) => {
        this.maquinasOperativas = data.filter(m => m.estado === 'operativo').length;
        this.maquinas = data.filter(m => m.estado === 'averiado' || m.estado === 'en_reparacion');
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

  cambiarPassword() {
    this.error = '';
    this.successMsg = '';
    if (!this.newPassword.trim()) {
      this.error = 'Introduce una nueva contraseña';
      return;
    }
    this.usuarioService.cambiarPassword(this.newPassword.trim()).subscribe({
      next: () => {
        this.successMsg = 'Contraseña actualizada correctamente';
        this.newPassword = '';
        this.showPasswordForm = false;
      },
      error: () => {
        this.error = 'Error al cambiar la contraseña';
      }
    });
  }
}
