import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar.component';
import { PrestamoFormModal } from '../prestamo-form-modal/prestamo-form-modal';
import { MockDataService } from '../../services/mock-data.service';
import { Prestamo } from '../../interfaces/app.interfaces';

// Componente para listar y gestionar préstamos de material
@Component({
  selector: 'app-prestamo-list',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent, PrestamoFormModal],
  templateUrl: './prestamo-list.html',
  styleUrl: './prestamo-list.css'
})
export class PrestamoListComponent implements OnInit {
  // Lista de préstamos y estado del modal
  listaPrestamos: Prestamo[] = [];
  modalAbierto = false;

  constructor(private mockService: MockDataService) { }

  // Carga los préstamos al inicializar el componente
  ngOnInit(): void {
    this.mockService.getPrestamos().subscribe(data => {
      this.listaPrestamos = data;
    });
  }

  // Abre el modal para crear un nuevo préstamo
  abrirModal() {
    this.modalAbierto = true;
  }

  // Devuelve la clase CSS según el estado del préstamo
  getEstadoClass(estado: string): string {
    switch (estado) {
      case 'Pendiente': return 'status-pendiente';
      case 'Entregado': return 'status-entregado';
      case 'Devuelto': return 'status-devuelto';
      default: return '';
    }
  }
}
