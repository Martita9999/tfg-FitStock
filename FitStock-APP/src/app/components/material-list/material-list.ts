import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { MaterialesService } from '../../services/materiales.service';
import { IncidenciasService } from '../../services/incidencias.service';
import { UsuarioService } from '../../services/usuario';
import { Material, Incidencia } from '../../interfaces/app.interfaces';
import { ConfirmService } from '../../services/confirm.service';
import { ToastService } from '../../services/toast.service';

/*
 * MaterialList: CRUD de máquinas del gimnasio.
 * Muestra operativas y no operativas separadas, con incidencias activas.
 * Incluye modales de creación y edición.
 */
@Component({
  selector: 'app-material-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './material-list.html',
  styleUrl: './material-list.css',
})
export class MaterialList implements OnInit {
  private materialesService = inject(MaterialesService);
  private incidenciasService = inject(IncidenciasService);
  private usuarioService = inject(UsuarioService);
  private route = inject(ActivatedRoute);
  private confirmService = inject(ConfirmService);
  private toastService = inject(ToastService);
  lista: Material[] = [];
  incidencias: Incidencia[] = [];
  userRole = '';
  vista: 'completa' | 'operativas' | 'incidencias' = 'completa';

  showModal = false;
  newMaterial = { nombre: '', descripcion: '', estado: 'operativo', ubicacion: '', id_tag_material: '' };
  error = '';

  showEditModal = false;
  editMaterial: Material | null = null;
  editData = { nombre: '', descripcion: '', estado: 'operativo', ubicacion: '', id_tag_material: '' };

  estadosDisponibles = [
    { value: 'operativo', label: 'Operativo' },
    { value: 'averiado', label: 'Averiado' },
    { value: 'mantenimiento', label: 'Mantenimiento' },
    { value: 'en_proceso', label: 'En Proceso' },
    { value: 'saliendo', label: 'Saliendo' },
    { value: 'en_reparacion', label: 'Mantenimiento' },
    { value: 'baja', label: 'Baja' },
  ];

  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadMateriales();
    this.loadIncidencias();
    this.route.paramMap.subscribe(params => {
      const v = params.get('vista');
      if (v === 'operativas' || v === 'incidencias') {
        this.vista = v;
      } else {
        this.vista = 'completa';
      }
    });
  }

  loadMateriales() {
    this.materialesService.getMateriales('maquina').subscribe(data => this.lista = data);
  }

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

  /* getIncidenciaActiva: busca si hay una incidencia abierta/en_proceso para un material */
  getIncidenciaActiva(idMaterial: number): Incidencia | undefined {
    return this.incidencias.find(i =>
      i.id_material === idMaterial &&
      (i.estado === 'abierta' || i.estado === 'en_proceso')
    );
  }

  abrirModal() {
    this.newMaterial = { nombre: '', descripcion: '', estado: 'operativo', ubicacion: '', id_tag_material: '' };
    this.error = '';
    this.showModal = true;
  }

  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  crearMaterial() {
    this.error = '';
    if (!this.newMaterial.nombre) {
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

  cerrarEditar() {
    this.showEditModal = false;
    this.editMaterial = null;
    this.error = '';
  }

  guardarEditar() {
    if (!this.editMaterial) return;
    this.error = '';
    if (!this.editData.nombre) {
      this.error = 'El nombre es obligatorio';
      return;
    }
    this.materialesService.updateMaterial(this.editMaterial.id, {
      nombre: this.editData.nombre,
      descripcion: this.editData.descripcion,
      ubicacion: this.editData.ubicacion,
      estado: this.editData.estado,
      id_tag_material: this.editData.id_tag_material,
      ultima_rev: new Date().toISOString().split('T')[0]                  // Fecha de revisión = hoy
    }).subscribe({
      next: () => { this.cerrarEditar(); this.loadMateriales(); },
      error: () => { this.error = 'Error al actualizar el material'; }
    });
  }

  async borrarMaterial(id: number, nombre: string) {
    const ok = await this.confirmService.confirm(`¿Borrar material "${nombre}"?`);
    if (!ok) return;
    this.materialesService.deleteMaterial(id).subscribe({
      next: () => this.loadMateriales(),
      error: () => this.toastService.show('Error al borrar el material', 'error')
    });
  }

  getEstadoLabel(valor: string): string {
    const e = this.estadosDisponibles.find(e => e.value === valor);
    return e ? e.label : valor;
  }
}
