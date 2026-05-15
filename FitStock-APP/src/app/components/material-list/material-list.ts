import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MaterialesService } from '../../services/materiales.service';
import { IncidenciasService } from '../../services/incidencias.service';
import { UsuarioService } from '../../services/usuario';
import { Material, Incidencia } from '../../interfaces/app.interfaces';

@Component({
  selector: 'app-material-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './material-list.html',
  styleUrl: './material-list.css',
})
// Componente que lista y gestiona las máquinas del gimnasio con sus incidencias
export class MaterialList implements OnInit {
  private materialesService = inject(MaterialesService);     // Servicio de materiales
  private incidenciasService = inject(IncidenciasService);   // Servicio de incidencias
  private usuarioService = inject(UsuarioService);            // Servicio de autenticación
  lista: Material[] = [];        // Lista de máquinas
  incidencias: Incidencia[] = [];   // Incidencias activas asociadas
  userRole = '';                 // Rol del usuario actual

  showModal = false;
  newMaterial = { nombre: '', descripcion: '', estado: 'operativo', ubicacion: '', id_tag_material: '' };
  error = '';   // Mensaje de error

  showEditModal = false;
  editMaterial: Material | null = null;
  editData = { nombre: '', descripcion: '', estado: 'operativo', ubicacion: '', id_tag_material: '' };

  // Estados disponibles para el selector
  estadosDisponibles = [
    { value: 'operativo', label: 'Operativo' },
    { value: 'averiado', label: 'Averiado' },
    { value: 'mantenimiento', label: 'Mantenimiento' },
    { value: 'en_proceso', label: 'En Proceso' },
    { value: 'saliendo', label: 'Saliendo' },
    { value: 'en_reparacion', label: 'Mantenimiento' },
    { value: 'baja', label: 'Baja' },
  ];

  // Al iniciar, carga materiales, incidencias y obtiene el rol del usuario
  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadMateriales();
    this.loadIncidencias();
  }

  // Carga la lista de máquinas desde la API
  loadMateriales() {
    this.materialesService.getMateriales('maquina').subscribe(data => this.lista = data);
  }

  // Carga las incidencias para mostrarlas asociadas a cada máquina
  loadIncidencias() {
    this.incidenciasService.getIncidencias().subscribe(data => this.incidencias = data);
  }

  private sortByIdTag(a: Material, b: Material): number {
    const tagA = a.id_tag_material || '';
    const tagB = b.id_tag_material || '';
    return tagA.localeCompare(tagB, undefined, { numeric: true });
  }

  get maquinasOperativas(): Material[] {
    return this.lista.filter(m => m.estado === 'operativo').sort(this.sortByIdTag);
  }

  get maquinasNoOperativas(): Material[] {
    return this.lista.filter(m => m.estado !== 'operativo').sort(this.sortByIdTag);
  }

  // Busca la incidencia activa (abierta o en proceso) de una máquina específica
  getIncidenciaActiva(idMaterial: number): Incidencia | undefined {
    return this.incidencias.find(i =>
      i.id_material === idMaterial &&
      (i.estado === 'abierta' || i.estado === 'en_proceso')
    );
  }

  // Abre el modal de creación de máquina
  abrirModal() {
    this.newMaterial = { nombre: '', descripcion: '', estado: 'operativo', ubicacion: '', id_tag_material: '' };
    this.error = '';
    this.showModal = true;
  }

  // Cierra el modal de creación
  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  // Crea una nueva máquina en la API
  crearMaterial() {
    this.error = '';
    if (!this.newMaterial.nombre) {           // Validación: nombre obligatorio
      this.error = 'El nombre es obligatorio';
      return;
    }
    this.materialesService.createMaterial({
      nombre: this.newMaterial.nombre,
      descripcion: this.newMaterial.descripcion,
      ubicacion: this.newMaterial.ubicacion,
      estado: this.newMaterial.estado,
      id_tag_material: this.newMaterial.id_tag_material
    }).subscribe({
      next: () => { this.cerrarModal(); this.loadMateriales(); },
      error: () => { this.error = 'Error al crear el material'; }
    });
  }

  // Abre el modal de edición con los datos de la máquina seleccionada
  abrirEditar(m: Material) {
    this.editMaterial = m;
    this.editData = {
      nombre: m.nombre,
      descripcion: m.descripcion || '',
      ubicacion: m.ubicacion || '',
      estado: m.estado,
      id_tag_material: m.id_tag_material || '',
    };
    this.error = '';
    this.showEditModal = true;
  }

  // Cierra el modal de edición
  cerrarEditar() {
    this.showEditModal = false;
    this.editMaterial = null;
    this.error = '';
  }

  // Guarda los cambios de la máquina y actualiza la fecha de revisión automáticamente
  guardarEditar() {
    if (!this.editMaterial) return;
    this.error = '';
    if (!this.editData.nombre) {           // Validación: nombre obligatorio
      this.error = 'El nombre es obligatorio';
      return;
    }
    this.materialesService.updateMaterial(this.editMaterial.id, {
      nombre: this.editData.nombre,
      descripcion: this.editData.descripcion,
      ubicacion: this.editData.ubicacion,
      estado: this.editData.estado,
      id_tag_material: this.editData.id_tag_material,
      ultima_rev: new Date().toISOString().split('T')[0]
    }).subscribe({
      next: () => { this.cerrarEditar(); this.loadMateriales(); },
      error: () => { this.error = 'Error al actualizar el material'; }
    });
  }

  // Confirma y elimina una máquina por su ID
  borrarMaterial(id: number, nombre: string) {
    if (!confirm(`¿Borrar material "${nombre}"?`)) return;   // Confirmación del usuario
    this.materialesService.deleteMaterial(id).subscribe({
      next: () => this.loadMateriales(),
      error: () => alert('Error al borrar el material')
    });
  }

  // Convierte el valor del estado a una etiqueta legible
  getEstadoLabel(valor: string): string {
    const e = this.estadosDisponibles.find(e => e.value === valor);
    return e ? e.label : valor;
  }
}
