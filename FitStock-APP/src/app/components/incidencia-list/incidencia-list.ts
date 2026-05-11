import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IncidenciasService } from '../../services/incidencias.service';
import { MaterialesService } from '../../services/materiales.service';
import { UsuarioService } from '../../services/usuario';
import { Incidencia, Material } from '../../interfaces/app.interfaces';

@Component({
  selector: 'app-incidencia-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './incidencia-list.html',
  styleUrl: './incidencia-list.css',
})
export class IncidenciaList implements OnInit {
  private incidenciasService = inject(IncidenciasService);
  private materialesService = inject(MaterialesService);
  private usuarioService = inject(UsuarioService);
  lista: Incidencia[] = [];
  materiales: Material[] = [];
  userRole = '';

  showModal = false;
  newIncidencia = { id_material: 0, descripcion: '', prioridad: 'media' };
  error = '';

  showEditModal = false;
  editIncidencia: Incidencia | null = null;
  editData = { prioridad: '', estado: '' };

  estadosDisponibles = [
    { value: 'abierta', label: 'Averiado' },
    { value: 'en_proceso', label: 'En Reparación' },
  ];

  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadIncidencias();
    this.materialesService.getMateriales('maquina').subscribe(data => this.materiales = data);
  }

  loadIncidencias() {
    this.incidenciasService.getIncidencias().subscribe(data => this.lista = data);
  }

  get incidenciasActivas(): Incidencia[] {
    return this.lista.filter(i => i.estado !== 'resuelta');
  }

  get incidenciasResueltas(): Incidencia[] {
    return this.lista.filter(i => i.estado === 'resuelta');
  }

  getEstadoLabel(valor: string): string {
    if (valor === 'abierta') return 'Averiado';
    if (valor === 'en_proceso') return 'En Reparación';
    return 'Resuelta';
  }

  abrirModal() {
    this.newIncidencia = { id_material: 0, descripcion: '', prioridad: 'media' };
    this.error = '';
    this.showModal = true;
  }

  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  crearIncidencia() {
    this.error = '';
    if (!this.newIncidencia.id_material || !this.newIncidencia.descripcion) {
      this.error = 'Selecciona un material y escribe una descripción';
      return;
    }
    this.incidenciasService.createIncidencia(this.newIncidencia).subscribe({
      next: () => { this.cerrarModal(); this.loadIncidencias(); },
      error: () => { this.error = 'Error al crear la incidencia'; }
    });
  }

  abrirEditar(inc: Incidencia) {
    this.editIncidencia = inc;
    this.editData = { prioridad: inc.prioridad, estado: inc.estado };
    this.error = '';
    this.showEditModal = true;
  }

  cerrarEditar() {
    this.showEditModal = false;
    this.editIncidencia = null;
    this.error = '';
  }

  guardarEditar() {
    if (!this.editIncidencia) return;
    this.error = '';
    this.incidenciasService.updateIncidencia(this.editIncidencia.id, this.editData).subscribe({
      next: () => { this.cerrarEditar(); this.loadIncidencias(); },
      error: () => { this.error = 'Error al actualizar la incidencia'; }
    });
  }

  borrarIncidencia(id: number) {
    if (!confirm('¿Borrar esta incidencia?')) return;
    this.incidenciasService.deleteIncidencia(id).subscribe({
      next: () => this.loadIncidencias(),
      error: () => alert('Error al borrar la incidencia')
    });
  }
}
