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
export class MaterialList implements OnInit {
  private materialesService = inject(MaterialesService);
  private incidenciasService = inject(IncidenciasService);
  private usuarioService = inject(UsuarioService);
  lista: Material[] = [];
  incidencias: Incidencia[] = [];
  userRole = '';

  showModal = false;
  newMaterial = { nombre: '', descripcion: '', estado: 'operativo', ubicacion: '' };
  error = '';

  showEditModal = false;
  editMaterial: Material | null = null;
  editData = { nombre: '', descripcion: '', estado: 'operativo', ubicacion: '' };

  estadosDisponibles = [
    { value: 'operativo', label: 'Operativo' },
    { value: 'averiado', label: 'Averiado' },
    { value: 'mantenimiento', label: 'Mantenimiento' },
    { value: 'en_proceso', label: 'En Proceso' },
    { value: 'saliendo', label: 'Saliendo' },
    { value: 'en_reparacion', label: 'En Reparación' },
    { value: 'baja', label: 'Baja' },
  ];

  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadMateriales();
    this.loadIncidencias();
  }

  loadMateriales() {
    this.materialesService.getMateriales('maquina').subscribe(data => this.lista = data);
  }

  loadIncidencias() {
    this.incidenciasService.getIncidencias().subscribe(data => this.incidencias = data);
  }

  get maquinasOperativas(): Material[] {
    return this.lista.filter(m => m.estado === 'operativo');
  }

  get maquinasNoOperativas(): Material[] {
    return this.lista.filter(m => m.estado !== 'operativo');
  }

  getIncidenciaActiva(idMaterial: number): Incidencia | undefined {
    return this.incidencias.find(i =>
      i.id_material === idMaterial &&
      (i.estado === 'abierta' || i.estado === 'en_proceso')
    );
  }

  abrirModal() {
    this.newMaterial = { nombre: '', descripcion: '', estado: 'operativo', ubicacion: '' };
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
      estado: this.newMaterial.estado
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
      ultima_rev: new Date().toISOString().split('T')[0]
    }).subscribe({
      next: () => { this.cerrarEditar(); this.loadMateriales(); },
      error: () => { this.error = 'Error al actualizar el material'; }
    });
  }

  borrarMaterial(id: number, nombre: string) {
    if (!confirm(`¿Borrar material "${nombre}"?`)) return;
    this.materialesService.deleteMaterial(id).subscribe({
      next: () => this.loadMateriales(),
      error: () => alert('Error al borrar el material')
    });
  }

  getEstadoLabel(valor: string): string {
    const e = this.estadosDisponibles.find(e => e.value === valor);
    return e ? e.label : valor;
  }
}
