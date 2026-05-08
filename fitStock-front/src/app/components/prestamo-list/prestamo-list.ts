import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { PrestamosService } from '../../services/prestamos.service';
import { MaterialesService } from '../../services/materiales.service';
import { UsuarioService } from '../../services/usuario';
import { Prestamo, Usuario, Material } from '../../interfaces/app.interfaces';

interface MaterialGroup {
  nombre: string;
  descripcion: string;
  total: number;
  disponibles: number;
  seleccionados: number;
  idsDisponibles: number[];
}

@Component({
  selector: 'app-prestamo-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './prestamo-list.html',
  styleUrl: './prestamo-list.css',
})
export class PrestamoList implements OnInit {
  private prestamosService = inject(PrestamosService);
  private materialesService = inject(MaterialesService);
  private usuarioService = inject(UsuarioService);
  materiales: Material[] = [];
  prestamos: Prestamo[] = [];
  usuarios: Usuario[] = [];
  userRole = '';
  currentUserId = 0;

  selecciones: { [nombre: string]: number } = {};
  error = '';

  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => {
      this.userRole = u?.rol ?? '';
      this.currentUserId = u?.id ?? 0;
      if (this.userRole !== 'cliente') {
        this.usuarioService.getUsuarios().subscribe(data => this.usuarios = data);
      }
    });
    this.loadMateriales();
    this.loadPrestamos();
  }

  loadMateriales() {
    this.materialesService.getMateriales('prestable').subscribe(data => this.materiales = data);
  }

  loadPrestamos() {
    this.prestamosService.getPrestamos().subscribe(data => this.prestamos = data);
  }

  get idsEnPrestamoActivo(): Set<number> {
    return new Set(
      this.prestamos.filter(p => !p.devolucion).map(p => p.id_material).filter((id): id is number => id != null)
    );
  }

  get grupos(): MaterialGroup[] {
    const agrupados: { [key: string]: Material[] } = {};
    for (const m of this.materiales) {
      if (!agrupados[m.nombre]) agrupados[m.nombre] = [];
      agrupados[m.nombre].push(m);
    }

    const idsActivos = this.idsEnPrestamoActivo;

    return Object.entries(agrupados).map(([nombre, items]) => {
      const operativos = items.filter(m => m.estado === 'operativo');
      const disponibles = operativos.filter(m => !idsActivos.has(m.id));
      return {
        nombre,
        descripcion: items[0].descripcion || '',
        total: operativos.length,
        disponibles: disponibles.length,
        seleccionados: this.selecciones[nombre] ?? 0,
        idsDisponibles: disponibles.map(m => m.id)
      };
    });
  }

  incrementar(g: MaterialGroup) {
    const actual = this.selecciones[g.nombre] ?? 0;
    if (actual < g.disponibles) {
      this.selecciones[g.nombre] = actual + 1;
    }
  }

  decrementar(g: MaterialGroup) {
    const actual = this.selecciones[g.nombre] ?? 0;
    if (actual > 0) {
      this.selecciones[g.nombre] = actual - 1;
    }
  }

  get haySeleccion(): boolean {
    return Object.values(this.selecciones).some(v => v > 0);
  }

  prestarSeleccion() {
    this.error = '';
    const promesas: Promise<any>[] = [];

    for (const g of this.grupos) {
      const cantidad = this.selecciones[g.nombre] ?? 0;
      for (let i = 0; i < cantidad; i++) {
        const idMaterial = g.idsDisponibles[i];
        if (idMaterial) {
          const data: any = { id_material: idMaterial };
          if (this.userRole !== 'cliente') {
            data.id_usuario = this.newPrestamoUsuario;
          }
          promesas.push(firstValueFrom(this.prestamosService.createPrestamo(data)));
        }
      }
    }

    if (promesas.length === 0) return;

    Promise.all(promesas).then(() => {
      this.loadPrestamos();
      this.limpiarSeleccion();
    }).catch(() => {
      this.error = 'Error al crear uno o más préstamos';
    });
  }

  newPrestamoUsuario = 0;
  showUserModal = false;

  abrirModalUsuario() {
    this.newPrestamoUsuario = 0;
    this.error = '';
    this.showUserModal = true;
  }

  confirmarUsuario() {
    if (!this.newPrestamoUsuario) {
      this.error = 'Selecciona un usuario';
      return;
    }
    this.showUserModal = false;
    this.prestarSeleccionAdmin();
  }

  cerrarModalUsuario() {
    this.showUserModal = false;
    this.error = '';
  }

  prestarSeleccionAdmin() {
    this.error = '';
    const promesas: Promise<any>[] = [];

    for (const g of this.grupos) {
      const cantidad = this.selecciones[g.nombre] ?? 0;
      for (let i = 0; i < cantidad; i++) {
        const idMaterial = g.idsDisponibles[i];
        if (idMaterial) {
          promesas.push(firstValueFrom(this.prestamosService.createPrestamo({
            id_material: idMaterial,
            id_usuario: this.newPrestamoUsuario
          })));
        }
      }
    }

    if (promesas.length === 0) return;

    Promise.all(promesas).then(() => {
      this.loadPrestamos();
      this.limpiarSeleccion();
    }).catch(() => {
      this.error = 'Error al crear uno o más préstamos';
    });
  }

  borrarPrestamo(id: number) {
    if (!confirm('¿Borrar este préstamo?')) return;
    this.prestamosService.deletePrestamo(id).subscribe({
      next: () => this.loadPrestamos(),
      error: () => alert('Error al borrar el préstamo')
    });
  }

  get totalSeleccionados(): number {
    return Object.values(this.selecciones).reduce((sum, v) => sum + v, 0);
  }

  limpiarSeleccion() {
    this.selecciones = {};
  }

  get prestamosUser() {
    if (this.userRole === 'cliente') {
      return this.prestamos.filter(p => p.id_usuario === this.currentUserId);
    }
    return this.prestamos;
  }
}
