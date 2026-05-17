import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { PrestamosService } from '../../services/prestamos.service';
import { MaterialesService } from '../../services/materiales.service';
import { UsuarioService } from '../../services/usuario';
import { Prestamo, Usuario, Material } from '../../interfaces/app.interfaces';

/* MaterialGroup: agrupa materiales del mismo nombre para mostrar unidades disponibles.
 * Ej: "Banco" con 3 unidades -> total:3, disponibles:2, seleccionados:1 */
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
  private route = inject(ActivatedRoute);
  materiales: Material[] = [];
  prestamos: Prestamo[] = [];
  prestamosPendientesDev: Prestamo[] = [];         // Préstamos en estado pendiente_devolucion
  usuarios: Usuario[] = [];
  userRole = '';
  currentUserId = 0;

  /* selecciones: mapa de nombre_material -> cantidad seleccionada por el usuario */
  selecciones: { [nombre: string]: number } = {};
  error = '';
  vista: 'completa' | 'activos' | 'materiales' | 'completados' | 'pendientes-devolucion' = 'completa';

  /* Modales */
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
    /* Lee la URL para determinar qué vista de préstamos mostrar (activos, completados, etc.) */
    this.route.paramMap.subscribe(params => {
      const v = params.get('vista');
      if (v === 'activos') { this.vista = 'activos'; }
      else if (v === 'materiales') { this.vista = 'materiales'; }
      else if (v === 'completados') { this.vista = 'completados'; }
      else if (v === 'pendientes-devolucion') { this.vista = 'pendientes-devolucion'; }
      else { this.vista = 'completa'; }
      this.cargarPendientes();
    });
  }

  /* loadMateriales: carga materiales de tipo 'prestable' desde la API */
  loadMateriales() {
    this.materialesService.getMateriales('prestable').subscribe(data => this.materiales = data);
  }

  /* loadPrestamos: carga todos los préstamos (cliente solo ve los suyos por backend) */
  loadPrestamos() {
    this.prestamosService.getPrestamos().subscribe(data => this.prestamos = data);
  }

  /* cargarPendientes: carga los préstamos marcados como pendiente_devolucion (solo admin/entrenador) */
  cargarPendientes() {
    if (this.userRole === 'cliente') return;
    this.prestamosService.getPrestamosPorEstado('pendiente_devolucion').subscribe(data => this.prestamosPendientesDev = data);
  }

  /* idsEnPrestamoActivo: Set con los ids de material actualmente en préstamo (pendiente o activo, sin devolver).
   * Se usa para ocultar materiales no disponibles de la lista seleccionable. */
  get idsEnPrestamoActivo(): Set<number> {
    return new Set(
      this.prestamos.filter(p => (p.estado === 'pendiente' || p.estado === 'activo') && !p.devolucion).map(p => p.id_material).filter((id): id is number => id != null)
    );
  }

  /* grupos: computa los grupos de material disponibles organizados por nombre,
   * excluyendo los que ya están en préstamo. */
  get grupos(): MaterialGroup[] {
    return this._grupos(this.selecciones);
  }

  /* sortByIdTag: ordena materiales por id_tag_material (ej: BAN-001, BAN-002) */
  private sortByIdTag(a: Material, b: Material): number {
    const tagA = a.id_tag_material || '';
    const tagB = b.id_tag_material || '';
    return tagA.localeCompare(tagB, undefined, { numeric: true });
  }

  /* _grupos: lógica interna de agrupación. Agrupa por nombre, cuenta operativos,
   * descuenta los que están en préstamo activo, y respeta selecciones actuales. */
  private _grupos(selecciones: { [nombre: string]: number }): MaterialGroup[] {
    const sorted = [...this.materiales].sort(this.sortByIdTag);
    const agrupados: { [key: string]: Material[] } = {};
    for (const m of sorted) {
      if (!agrupados[m.nombre]) agrupados[m.nombre] = [];
      agrupados[m.nombre].push(m);
    }

    const idsActivos = this.idsEnPrestamoActivo;

    const grupos = Object.entries(agrupados).map(([nombre, items]) => {
      const operativos = items.filter(m => m.estado === 'operativo');
      const disponibles = operativos.filter(m => !idsActivos.has(m.id)).sort(this.sortByIdTag);
      return {
        nombre,
        descripcion: items[0].descripcion || '',
        total: operativos.length,
        disponibles: disponibles.length,
        seleccionados: selecciones[nombre] ?? 0,
        idsDisponibles: disponibles.map(m => m.id)
      };
    });

    return grupos.sort((a, b) => {
      const tagA = (agrupados[a.nombre]?.[0]?.id_tag_material || '').toLowerCase();
      const tagB = (agrupados[b.nombre]?.[0]?.id_tag_material || '').toLowerCase();
      return tagA.localeCompare(tagB, undefined, { numeric: true });
    });
  }

  /* incrementar: suma 1 a la selección de un grupo, sin superar los disponibles */
  incrementar(g: MaterialGroup) {
    const actual = this.selecciones[g.nombre] ?? 0;
    if (actual < g.disponibles) {
      this.selecciones[g.nombre] = actual + 1;
    }
  }

  /* decrementar: resta 1 a la selección de un grupo */
  decrementar(g: MaterialGroup) {
    const actual = this.selecciones[g.nombre] ?? 0;
    if (actual > 0) {
      this.selecciones[g.nombre] = actual - 1;
    }
  }

  /* haySeleccion: true si hay al menos un material seleccionado para préstamo */
  get haySeleccion(): boolean {
    return Object.values(this.selecciones).some(v => v > 0);
  }

  /* prestarSeleccion: ejecuta el préstamo de todos los materiales seleccionados.
   * Cliente: crea préstamos en estado 'pendiente' (requiere aprobación).
   * Admin/entrenador: pasa por abrirModalUsuario() para seleccionar usuario primero. */
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

  /* abrirModalUsuario: admin/entrenador selecciona a qué usuario asignar el préstamo */
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

  /* prestarSeleccionAdmin: crea préstamos directamente como 'activo' (sin pasar por pendiente).
   * Solo admin/entrenador. */
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
            id_usuario: this.newPrestamoUsuario,
            estado: 'activo'
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

  /* abrirEditar: abre modal para editar fecha de devolución de un préstamo */
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

  /* guardarEditar: envía la nueva fecha de devolución a la API */
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

  /* abrirEditarGrupo: abre modal para cambiar nombre/descripción de todo un grupo de materiales */
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

  /* guardarEditarGrupo: actualiza nombre/descripción de todas las unidades del grupo */
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

  /* borrarUnidad: elimina una unidad física de material del grupo */
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

  /* abrirCrearMaterial: abre modal para crear una o varias unidades de material */
  abrirCrearMaterial() {
    this.newMaterial = { nombre: '', descripcion: '', cantidad: 1 };
    this.error = '';
    this.showCreateModal = true;
  }

  cerrarCrearMaterial() {
    this.showCreateModal = false;
    this.error = '';
  }

  /* crearMaterial: envía a la API tantas peticiones como unidades se quieran crear */
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
      this.error = 'Error al crear el material. Revisa que el servidor PHP esté corriendo.';
    });
  }

  /* borrarPrestamo: elimina un préstamo (solo admin/entrenador) */
  borrarPrestamo(id: number) {
    if (!confirm('¿Borrar este préstamo?')) return;
    this.prestamosService.deletePrestamo(id).subscribe({
      next: () => { this.loadPrestamos(); this.cargarPendientes(); },
      error: () => alert('Error al borrar el préstamo')
    });
  }

  /* devolverPrestamo: el cliente marca el préstamo para devolución (estado -> pendiente_devolucion) */
  devolverPrestamo(id: number) {
    this.prestamosService.devolverPrestamo(id).subscribe({
      next: () => {
        this.loadPrestamos();
        this.error = '';
      },
      error: () => { this.error = 'Error al devolver el préstamo'; }
    });
  }

  /* aprobarPrestamo: admin/entrenador aprueba préstamo pendiente (estado -> activo) */
  aprobarPrestamo(id: number) {
    this.prestamosService.aprobarPrestamo(id).subscribe({
      next: () => { this.loadPrestamos(); this.cargarPendientes(); },
      error: () => { this.error = 'Error al aprobar el préstamo'; }
    });
  }

  /* confirmarDevolucionPrestamo: admin/entrenador confirma devolución (estado -> devuelto + fecha) */
  confirmarDevolucionPrestamo(id: number) {
    this.prestamosService.confirmarDevolucion(id).subscribe({
      next: () => { this.loadPrestamos(); this.cargarPendientes(); },
      error: () => { this.error = 'Error al confirmar la devolución'; }
    });
  }

  /* totalSeleccionados: suma total de materiales seleccionados en todos los grupos */
  get totalSeleccionados(): number {
    return Object.values(this.selecciones).reduce((sum, v) => sum + v, 0);
  }

  /* limpiarSeleccion: resetea todas las selecciones a 0 */
  limpiarSeleccion() {
    this.selecciones = {};
  }

  /* prestamosUser: cliente solo ve sus préstamos; admin/entrenador ven todos */
  get prestamosUser() {
    if (this.userRole === 'cliente') {
      return this.prestamos.filter(p => p.id_usuario === this.currentUserId);
    }
    return this.prestamos;
  }

  /* prestamosActivos: filtra préstamos NO devueltos (pendiente, activo, pendiente_devolucion) */
  get prestamosActivos() {
    return this.prestamosUser.filter(p => p.estado !== 'devuelto' && !p.devolucion);
  }

  /* prestamosCompletados: filtra préstamos ya devueltos */
  get prestamosCompletados() {
    return this.prestamosUser.filter(p => p.estado === 'devuelto' || p.devolucion);
  }

  /* exportarCSV: descarga los préstamos completados como archivo CSV con BOM para Excel */
  exportarCSV() {
    const filas = this.prestamosCompletados.map(p => [
      p.id,
      p.material,
      p.usuario,
      p.fecha ? new Date(p.fecha).toLocaleDateString() : '',
      p.devolucion ? new Date(p.devolucion).toLocaleDateString() : ''
    ]);
    const cabeceras = ['ID', 'Material', 'Usuario', 'Fecha Préstamo', 'Fecha Devolución'];
    const csv = [cabeceras.join(','), ...filas.map(f => f.map(v => `"${v}"`).join(','))].join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `prestamos_completados_${new Date().toISOString().slice(0,10)}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
  }

  /* exportarPDF: descarga los préstamos completados como PDF con jspdf + autoTable */
  exportarPDF() {
    const doc = new jsPDF();
    const filas = this.prestamosCompletados.map(p => [
      p.id.toString(),
      p.material,
      p.usuario,
      p.fecha ? new Date(p.fecha).toLocaleDateString() : '',
      p.devolucion ? new Date(p.devolucion).toLocaleDateString() : ''
    ]);
    autoTable(doc, {
      head: [['ID', 'Material', 'Usuario', 'Fecha Préstamo', 'Fecha Devolución']],
      body: filas,
      styles: { fontSize: 8 }
    });
    doc.save(`prestamos_completados_${new Date().toISOString().slice(0,10)}.pdf`);
  }
}
