import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar.component';
import { MockDataService } from '../../services/mock-data.service';
import { Producto, Prestamo, Incidencia } from '../../interfaces/app.interfaces';

// Componente que muestra el dashboard principal con estadísticas generales
@Component({
  selector: 'app-admin-dashboard-section',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent],
  templateUrl: './admin-dashboard-section.html',
  styleUrl: './admin-dashboard-section.css'
})
export class AdminDashboardSectionComponent implements OnInit {
  // Listas para almacenar los datos obtenidos del servicio mock
  listaProductos: Producto[] = [];
  listaPrestamos: Prestamo[] = [];
  listaIncidencias: Incidencia[] = [];

  constructor(private mockService: MockDataService) { }

  // Inicializa el componente cargando los datos de productos, préstamos e incidencias
  ngOnInit(): void {
    this.mockService.getProductos().subscribe(data => this.listaProductos = data);
    this.mockService.getPrestamos().subscribe(data => this.listaPrestamos = data);
    this.mockService.getIncidencias().subscribe(data => this.listaIncidencias = data);
  }

  // Calcula el número de categorías únicas en el inventario
  getCategoriasUnicas(): number {
    return [...new Set(this.listaProductos.map(p => p.categoria))].length;
  }

  // Calcula el número de socios únicos que han realizado préstamos
  getSociosUnicos(): number {
    const socios = new Set(this.listaPrestamos.map(p => p.usuarioNombre));
    return socios.size || 0;
  }

  getStockTotal(): number {
    return this.listaProductos.reduce((sum, p) => sum + p.stockDisponible, 0);
  }
}
