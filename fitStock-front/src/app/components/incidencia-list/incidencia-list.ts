import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { IncidenciasService } from '../../services/incidencias.service';
import { MaterialesService } from '../../services/materiales.service';
import { UsuarioService } from '../../services/usuario';
import { Incidencia, Material } from '../../interfaces/app.interfaces';

// Componente que lista y gestiona las incidencias reportadas
@Component({
  selector: 'app-incidencia-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './incidencia-list.html',
  styleUrl: './incidencia-list.css',
})
export class IncidenciaList implements OnInit {
  private incidenciasService = inject(IncidenciasService);   // Servicio de incidencias
  private materialesService = inject(MaterialesService);     // Servicio de materiales (para selector)
  private usuarioService = inject(UsuarioService);           // Servicio de usuario (para el rol)
  lista: Incidencia[] = [];                                   // Lista de incidencias
  materiales: Material[] = [];                                // Lista de materiales (para selector)
  userRole = '';                                              // Rol del usuario actual

  showModal = false;                                                              // Control del modal
  newIncidencia = { id_material: 0, descripcion: '', prioridad: 'media' };       // Datos de nueva incidencia
  error = '';                                               // Mensaje de error

  // Al iniciar, obtiene el rol y carga los datos
  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadIncidencias();
    this.materialesService.getMateriales('maquina').subscribe(data => this.materiales = data);   // Carga solo máquinas para selector
  }

  // Carga la lista de incidencias desde la API
  loadIncidencias() {
    this.incidenciasService.getIncidencias().subscribe(data => this.lista = data);
  }

  // Abre el modal de creación de incidencia
  abrirModal() {
    this.newIncidencia = { id_material: 0, descripcion: '', prioridad: 'media' };
    this.error = '';
    this.showModal = true;
  }

  // Cierra el modal
  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  // Envía los datos para crear una nueva incidencia
  crearIncidencia() {
    this.error = '';
    if (!this.newIncidencia.id_material || !this.newIncidencia.descripcion) {   // Validación: material y descripción obligatorios
      this.error = 'Selecciona un material y escribe una descripción';
      return;
    }
    this.incidenciasService.createIncidencia(this.newIncidencia).subscribe({
      next: () => { this.cerrarModal(); this.loadIncidencias(); },
      error: () => { this.error = 'Error al crear la incidencia'; }
    });
  }

  // Confirma y elimina una incidencia
  borrarIncidencia(id: number) {
    if (!confirm('¿Borrar esta incidencia?')) return;    // Confirmación del usuario
    this.incidenciasService.deleteIncidencia(id).subscribe({
      next: () => this.loadIncidencias(),
      error: () => alert('Error al borrar la incidencia')
    });
  }
}
