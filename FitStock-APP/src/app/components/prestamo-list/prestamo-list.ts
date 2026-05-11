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

  showEditModal = false;
  editPrestamo: Prestamo | null = null;
  editFechaDevolucion = '';

  showCreateModal = false;
  newMaterial = { nombre: '', descripcion: '', cantidad: 1 };

  showEditGroupModal = false;
  editGroupData = { nombreOriginal: '', nombre: '', descripcion: '', ids: [] as number[] };

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
    return this._grupos(this.selecciones);
  }

  private _grupos(selecciones: { [nombre: string]: number }): MaterialGroup[] {
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
        seleccionados: selecciones[nombre] ?? 0,
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

  abrirEditar(p: Prestamo) {
    this.editPrestamo = p;
    this.editFechaDevolucion = p.devolucion ? p.devolucion.split('T')[0] : new Date().toISOString().split('T')[0];
    this.error = '';
    this.showEditModal = true;
  }

  cerrarEditar() {
    this.showEditModal = false;
    this.editPrestamo = null;
    this.error = '';
  }

  guardarEditar() {
    if (!this.editPrestamo) return;
    this.error = '';
    this.prestamosService.updatePrestamo(this.editPrestamo.id, {
      fecha_devolucion: this.editFechaDevolucion
    }).subscribe({
      next: () => { this.cerrarEditar(); this.loadPrestamos(); },
      error: () => { this.error = 'Error al actualizar el préstamo'; }
    });
  }

  abrirEditarGrupo(g: MaterialGroup) {
    this.editGroupData = {
      nombreOriginal: g.nombre,
      nombre: g.nombre,
      descripcion: g.descripcion,
      ids: this.materiales.filter(m => m.nombre === g.nombre).map(m => m.id)
    };
    this.error = '';
    this.showEditGroupModal = true;
  }

  cerrarEditarGrupo() {
    this.showEditGroupModal = false;
    this.error = '';
  }

  guardarEditarGrupo() {
    this.error = '';
    if (!this.editGroupData.nombre) {
      this.error = 'El nombre es obligatorio';
      return;
    }
    const promesas = this.editGroupData.ids.map(id =>
      firstValueFrom(this.materialesService.updateMaterial(id, {
        nombre: this.editGroupData.nombre,
        descripcion: this.editGroupData.descripcion
      }))
    );
    Promise.all(promesas).then(() => {
      this.loadMateriales();
      this.cerrarEditarGrupo();
    }).catch(() => {
      this.error = 'Error al actualizar el grupo';
    });
  }

  borrarUnidad(g: MaterialGroup) {
    const disponible = g.idsDisponibles[0];
    if (!disponible) {
      this.error = 'No hay unidades disponibles para borrar';
      return;
    }
    if (!confirm(`¿Borrar una unidad de "${g.nombre}"?`)) return;
    this.error = '';
    this.materialesService.deleteMaterial(disponible).subscribe({
      next: () => this.loadMateriales(),
      error: () => { this.error = 'Error al borrar el material'; }
    });
  }

  abrirCrearMaterial() {
    this.newMaterial = { nombre: '', descripcion: '', cantidad: 1 };
    this.error = '';
    this.showCreateModal = true;
  }

  cerrarCrearMaterial() {
    this.showCreateModal = false;
    this.error = '';
  }

  crearMaterial() {
    this.error = '';
    if (!this.newMaterial.nombre) {
      this.error = 'El nombre es obligatorio';
      return;
    }
    if (this.newMaterial.cantidad < 1) {
      this.error = 'La cantidad debe ser al menos 1';
      return;
    }
    const promesas: Promise<any>[] = [];
    for (let i = 0; i < this.newMaterial.cantidad; i++) {
      promesas.push(firstValueFrom(this.materialesService.createMaterial({
        nombre: this.newMaterial.nombre,
        descripcion: this.newMaterial.descripcion,
        estado: 'operativo',
        tipo: 'prestable'
      })));
    }
    Promise.all(promesas).then(() => {
      this.loadMateriales();
      this.cerrarCrearMaterial();
    }).catch((err) => {
      console.error('Error al crear material:', err);
      this.error = 'Error al crear el material. Revisa que el servidor PHP esté corriendo.';
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
