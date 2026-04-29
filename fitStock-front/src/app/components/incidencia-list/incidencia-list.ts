import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar.component';
import { IncidenciaFormModal } from '../incidencia-form-modal/incidencia-form-modal';
import { MockDataService } from '../../services/mock-data.service';
import { Incidencia } from '../../interfaces/app.interfaces';

// Componente para listar y gestionar incidencias reportadas
@Component({
  selector: 'app-incidencia-list',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent, IncidenciaFormModal],
  templateUrl: './incidencia-list.html',
  styleUrl: './incidencia-list.css'
})
export class IncidenciaListComponent implements OnInit {
  // Lista de incidencias y estado del modal
  listaIncidencias: Incidencia[] = [];
  modalAbierto = false;

  constructor(private mockService: MockDataService) { }

  // Carga las incidencias al inicializar el componente
  ngOnInit(): void {
    this.mockService.getIncidencias().subscribe(data => {
      this.listaIncidencias = data;
    });
  }

  // Abre el modal para crear una nueva incidencia
  abrirModal() {
    this.modalAbierto = true;
  }

  // Devuelve la clase CSS según el nivel de urgencia
  getUrgenciaClass(urgencia: string): string {
    switch (urgencia) {
      case 'Baja': return 'urg-baja';
      case 'Moderada': return 'urg-moderada';
      case 'Alta': return 'urg-alta';
      case 'Crítica': return 'urg-critica';
      default: return '';
    }
  }

  // Devuelve la clase CSS según el estado de la incidencia
  getEstadoClass(estado: string): string {
    switch (estado) {
      case 'Abierta': return 'est-abierta';
      case 'En Proceso': return 'est-proceso';
      case 'Resuelta': return 'est-resuelta';
      default: return '';
    }
  }
}
