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
// Componente que lista y gestiona las incidencias reportadas en máquinas
export class IncidenciaList implements OnInit {
  private incidenciasService = inject(IncidenciasService);   // Servicio de incidencias
  private materialesService = inject(MaterialesService);     // Servicio de materiales
  private usuarioService = inject(UsuarioService);            // Servicio de autenticación
  lista: Incidencia[] = [];      // Lista completa de incidencias
  materiales: Material[] = [];   // Materiales tipo máquina para el selector
  userRole = '';                 // Rol del usuario actual

  showModal = false;                                                     // Control del modal de creación
  newIncidencia = { id_material: 0, descripcion: '', prioridad: 'media' };  // Datos de nueva incidencia
  error = '';   // Mensaje de error

  showEditModal = false;                                    // Control del modal de edición
  editIncidencia: Incidencia | null = null;                  // Incidencia en edición
  editData = { descripcion: '', prioridad: '', estado: '' };  // Datos editados

  // Estados disponibles para editar (excluye 'resuelta' del selector si se desea)
  estadosDisponibles = [
    { value: 'abierta', label: 'Averiado' },
    { value: 'en_proceso', label: 'En Reparación' },
  ];

  // Al iniciar, carga incidencias, materiales y obtiene el rol del usuario
  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadIncidencias();
    this.materialesService.getMateriales('maquina').subscribe(data => this.materiales = data);
  }

  // Carga la lista de incidencias desde la API
  loadIncidencias() {
    this.incidenciasService.getIncidencias().subscribe(data => this.lista = data);
  }

  // Filtra las incidencias activas (no resueltas)
  get incidenciasActivas(): Incidencia[] {
    return this.lista.filter(i => i.estado !== 'resuelta');
  }

  // Filtra las incidencias ya resueltas
  get incidenciasResueltas(): Incidencia[] {
    return this.lista.filter(i => i.estado === 'resuelta');
  }

  // Convierte el valor del estado a una etiqueta legible
  getEstadoLabel(valor: string): string {
    if (valor === 'abierta') return 'Averiado';
    if (valor === 'en_proceso') return 'En Reparación';
    return 'Resuelta';
  }

  // Abre el modal de creación de incidencia
  abrirModal() {
    this.newIncidencia = { id_material: 0, descripcion: '', prioridad: 'media' };
    this.error = '';
    this.showModal = true;
  }

  // Cierra el modal de creación
  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  // Crea una nueva incidencia en la API
  crearIncidencia() {
    this.error = '';
    if (!this.newIncidencia.id_material || !this.newIncidencia.descripcion) {   // Validación: campos obligatorios
      this.error = 'Selecciona un material y escribe una descripción';
      return;
    }
    this.incidenciasService.createIncidencia(this.newIncidencia).subscribe({
      next: () => { this.cerrarModal(); this.loadIncidencias(); },
      error: () => { this.error = 'Error al crear la incidencia'; }
    });
  }

  // Abre el modal de edición con los datos de la incidencia
  abrirEditar(inc: Incidencia) {
    this.editIncidencia = inc;
    this.editData = { descripcion: inc.descripcion, prioridad: inc.prioridad, estado: inc.estado };
    this.error = '';
    this.showEditModal = true;
  }

  // Cierra el modal de edición
  cerrarEditar() {
    this.showEditModal = false;
    this.editIncidencia = null;
    this.error = '';
  }

  // Guarda los cambios de prioridad/estado de la incidencia
  guardarEditar() {
    if (!this.editIncidencia) return;
    this.error = '';
    this.incidenciasService.updateIncidencia(this.editIncidencia.id, this.editData).subscribe({
      next: () => { this.cerrarEditar(); this.loadIncidencias(); },
      error: () => { this.error = 'Error al actualizar la incidencia'; }
    });
  }

  // Confirma y elimina una incidencia por su ID
  borrarIncidencia(id: number) {
    if (!confirm('¿Borrar esta incidencia?')) return;    // Confirmación del usuario
    this.incidenciasService.deleteIncidencia(id).subscribe({
      next: () => this.loadIncidencias(),
      error: () => alert('Error al borrar la incidencia')
    });
  }
}
