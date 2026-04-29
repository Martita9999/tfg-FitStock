import { Component, EventEmitter, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

// Modal para reportar una nueva incidencia
@Component({
  selector: 'app-incidencia-form-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './incidencia-form-modal.html',
  styleUrl: './incidencia-form-modal.css'
})
export class IncidenciaFormModal {
  // Evento para cerrar el modal
  @Output() cerrar = new EventEmitter<void>();

  // Modelo de datos para el formulario de incidencia
  incidencia = {
    area: '',
    maquinaEquipo: '',
    titulo: '',
    descripcion: '',
    urgencia: 'Moderada',
    usuarioNombre: ''
  };

  // Emite evento para cerrar el modal
  cerrarModal() {
    this.cerrar.emit();
  }
}
