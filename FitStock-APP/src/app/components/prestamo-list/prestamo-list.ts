import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { PrestamosService } from '../../services/prestamos.service';
import { MaterialesService } from '../../services/materiales.service';
import { UsuarioService } from '../../services/usuario';
import { Prestamo, Usuario, Material } from '../../interfaces/app.interfaces';

// Interfaz interna que agrupa materiales por nombre para facilitar la selección
interface MaterialGroup {
  nombre: string;               // Nombre del material (ej: "Mancuerna 5kg")
  descripcion: string;          // Descripción del grupo
  total: number;                // Total de unidades operativas
  disponibles: number;          // Unidades disponibles para préstamo
  seleccionados: number;        // Cantidad seleccionada por el usuario
  idsDisponibles: number[];     // IDs de las unidades disponibles
}

@Component({
  selector: 'app-prestamo-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './prestamo-list.html',
  styleUrl: './prestamo-list.css',
})
// Componente que gestiona los préstamos de material prestable a usuarios
export class PrestamoList implements OnInit {
  private prestamosService = inject(PrestamosService);       // Servicio de préstamos
  private materialesService = inject(MaterialesService);     // Servicio de materiales
  private usuarioService = inject(UsuarioService);            // Servicio de autenticación
  materiales: Material[] = [];     // Materiales de tipo 'prestable'
  prestamos: Prestamo[] = [];      // Lista de préstamos
  usuarios: Usuario[] = [];        // Usuarios (para selector admin)
  userRole = '';                   // Rol del usuario actual
  currentUserId = 0;               // ID del usuario actual

  selecciones: { [nombre: string]: number } = {};   // Cantidad seleccionada por grupo de material
  error = '';   // Mensaje de error

  showEditModal = false;                             // Control del modal de edición de préstamo
  editPrestamo: Prestamo | null = null;              // Préstamo en edición
  editFechaDevolucion = '';                          // Fecha de devolución editada

  showCreateModal = false;                                                    // Control del modal de creación de material
  newMaterial = { nombre: '', descripcion: '', cantidad: 1 };                // Datos del nuevo material prestable

  showEditGroupModal = false;                                                         // Control del modal de edición de grupo
  editGroupData = { nombreOriginal: '', nombre: '', descripcion: '', ids: [] as number[] };  // Datos del grupo en edición

  // Al iniciar, se suscribe al usuario, carga materiales y préstamos
  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => {
      this.userRole = u?.rol ?? '';
      this.currentUserId = u?.id ?? 0;
      if (this.userRole !== 'cliente') {
        this.usuarioService.getUsuarios().subscribe(data => this.usuarios = data);
      }
    });
    this.loadMateriales();     // Carga materiales prestables
    this.loadPrestamos();      // Carga todos los préstamos
  }

  // Carga los materiales de tipo 'prestable' desde la API
  loadMateriales() {
    this.materialesService.getMateriales('prestable').subscribe(data => this.materiales = data);
  }

  // Carga todos los préstamos desde la API
  loadPrestamos() {
    this.prestamosService.getPrestamos().subscribe(data => this.prestamos = data);
  }

  // Obtiene los IDs de materiales que están actualmente prestados (sin devolución)
  get idsEnPrestamoActivo(): Set<number> {
    return new Set(
      this.prestamos.filter(p => !p.devolucion).map(p => p.id_material).filter((id): id is number => id != null)
    );
  }

  // Propiedad pública que expone los grupos de materiales con su disponibilidad
  get grupos(): MaterialGroup[] {
    return this._grupos(this.selecciones);
  }

  private sortByIdTag(a: Material, b: Material): number {
    const tagA = a.id_tag_material || '';
    const tagB = b.id_tag_material || '';
    return tagA.localeCompare(tagB, undefined, { numeric: true });
  }

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

  // Incrementa la selección de un grupo de material (si hay disponibilidad)
  incrementar(g: MaterialGroup) {
    const actual = this.selecciones[g.nombre] ?? 0;
    if (actual < g.disponibles) {
      this.selecciones[g.nombre] = actual + 1;
    }
  }

  // Decrementa la selección de un grupo de material
  decrementar(g: MaterialGroup) {
    const actual = this.selecciones[g.nombre] ?? 0;
    if (actual > 0) {
      this.selecciones[g.nombre] = actual - 1;
    }
  }

  // Indica si hay al menos un material seleccionado
  get haySeleccion(): boolean {
    return Object.values(this.selecciones).some(v => v > 0);
  }

  // Crea los préstamos para los materiales seleccionados (modo cliente: préstamo propio)
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
            data.id_usuario = this.newPrestamoUsuario;   // Asigna usuario seleccionado por admin
          }
          promesas.push(firstValueFrom(this.prestamosService.createPrestamo(data)));
        }
      }
    }

    if (promesas.length === 0) return;

    Promise.all(promesas).then(() => {
      this.loadPrestamos();       // Recarga la lista
      this.limpiarSeleccion();    // Limpia la selección
    }).catch(() => {
      this.error = 'Error al crear uno o más préstamos';
    });
  }

  newPrestamoUsuario = 0;       // ID del usuario seleccionado por admin
  showUserModal = false;         // Control del modal de selección de usuario

  // Abre el modal para que el admin seleccione un usuario destinatario
  abrirModalUsuario() {
    this.newPrestamoUsuario = 0;
    this.error = '';
    this.showUserModal = true;
  }

  // Confirma la selección de usuario y procede a crear los préstamos
  confirmarUsuario() {
    if (!this.newPrestamoUsuario) {
      this.error = 'Selecciona un usuario';
      return;
    }
    this.showUserModal = false;
    this.prestarSeleccionAdmin();
  }

  // Cierra el modal de selección de usuario
  cerrarModalUsuario() {
    this.showUserModal = false;
    this.error = '';
  }

  // Crea los préstamos para admin con usuario destinatario específico
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

  // Abre el modal de edición de un préstamo para cambiar su fecha de devolución
  abrirEditar(p: Prestamo) {
    this.editPrestamo = p;
    this.editFechaDevolucion = p.devolucion ? p.devolucion.split('T')[0] : new Date().toISOString().split('T')[0];
    this.error = '';
    this.showEditModal = true;
  }

  // Cierra el modal de edición de préstamo
  cerrarEditar() {
    this.showEditModal = false;
    this.editPrestamo = null;
    this.error = '';
  }

  // Guarda la nueva fecha de devolución del préstamo
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

  // Abre el modal de edición de un grupo de material (nombre y descripción)
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

  // Cierra el modal de edición de grupo
  cerrarEditarGrupo() {
    this.showEditGroupModal = false;
    this.error = '';
  }

  // Guarda los cambios del grupo (aplica a todas las unidades del mismo nombre)
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

  // Elimina una unidad disponible de un grupo de material
  borrarUnidad(g: MaterialGroup) {
    const disponible = g.idsDisponibles[0];
    if (!disponible) {
      this.error = 'No hay unidades disponibles para borrar';
      return;
    }
    if (!confirm(`¿Borrar una unidad de "${g.nombre}"?`)) return;   // Confirmación del usuario
    this.error = '';
    this.materialesService.deleteMaterial(disponible).subscribe({
      next: () => this.loadMateriales(),
      error: () => { this.error = 'Error al borrar el material'; }
    });
  }

  // Abre el modal para crear nuevo material prestable
  abrirCrearMaterial() {
    this.newMaterial = { nombre: '', descripcion: '', cantidad: 1 };
    this.error = '';
    this.showCreateModal = true;
  }

  // Cierra el modal de creación de material
  cerrarCrearMaterial() {
    this.showCreateModal = false;
    this.error = '';
  }

  // Crea una o varias unidades de material prestable (según la cantidad especificada)
  crearMaterial() {
    this.error = '';
    if (!this.newMaterial.nombre) {              // Validación: nombre obligatorio
      this.error = 'El nombre es obligatorio';
      return;
    }
    if (this.newMaterial.cantidad < 1) {         // Validación: cantidad mínima 1
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

  // Confirma y elimina un préstamo por su ID
  borrarPrestamo(id: number) {
    if (!confirm('¿Borrar este préstamo?')) return;    // Confirmación del usuario
    this.prestamosService.deletePrestamo(id).subscribe({
      next: () => this.loadPrestamos(),
      error: () => alert('Error al borrar el préstamo')
    });
  }

  // Marca un préstamo como devuelto (cliente)
  devolverPrestamo(id: number) {
    this.prestamosService.devolverPrestamo(id).subscribe({
      next: () => {
        this.loadPrestamos();
        this.error = '';
      },
      error: () => { this.error = 'Error al devolver el préstamo'; }
    });
  }

  // Número total de materiales seleccionados
  get totalSeleccionados(): number {
    return Object.values(this.selecciones).reduce((sum, v) => sum + v, 0);
  }

  // Limpia todas las selecciones de material
  limpiarSeleccion() {
    this.selecciones = {};
  }

  // Filtra préstamos según el rol (cliente ve solo los suyos)
  get prestamosUser() {
    if (this.userRole === 'cliente') {
      return this.prestamos.filter(p => p.id_usuario === this.currentUserId);
    }
    return this.prestamos;
  }
}
