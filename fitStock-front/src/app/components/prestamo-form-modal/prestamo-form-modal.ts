import { Component, EventEmitter, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

// Modal para crear un nuevo préstamo de material
@Component({
  selector: 'app-prestamo-form-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './prestamo-form-modal.html',
  styleUrl: './prestamo-form-modal.css'
})
export class PrestamoFormModal {
  // Evento para cerrar el modal
  @Output() cerrar = new EventEmitter<void>();

  // Modelo de datos para el formulario de préstamo
  prestamo = {
    productoId: '',
    usuarioNombre: '',
    usuarioTelefono: '',
    cantidad: 1,
    diasPrestamo: 1,
    observaciones: ''
  };

  // Emite evento para cerrar el modal
  cerrarModal() {
    this.cerrar.emit();
  }
}
