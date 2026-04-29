import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar.component';
import { MockDataService } from '../../services/mock-data.service';
import { Producto } from '../../interfaces/app.interfaces';

// Componente para gestionar y visualizar el inventario de productos
@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent],
  templateUrl: './admin-dashboard.html', 
  styleUrl: './admin-dashboard.css'       
})
export class AdminDashboardComponent implements OnInit {
  // Lista de productos obtenidos del servicio
  listaProductos: Producto[] = [];

  constructor(private mockService: MockDataService) { }

  // Carga los productos al inicializar el componente
  ngOnInit(): void {
    this.mockService.getProductos().subscribe(data => this.listaProductos = data);
  }
}
