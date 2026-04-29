import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar.component';
import { MockDataService } from '../../services/mock-data.service';
import { Producto } from '../../interfaces/app.interfaces';

// Componente para listar todos los productos del inventario
@Component({
  selector: 'app-producto-list',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent],
  templateUrl: './producto-list.html',
  styleUrl: './producto-list.css'
})
export class ProductoListComponent implements OnInit {
  // Lista de productos obtenida del servicio
  listaProductos: Producto[] = [];

  constructor(private mockService: MockDataService) { }

  // Carga los productos al inicializar el componente
  ngOnInit(): void {
    this.mockService.getProductos().subscribe(data => {
      this.listaProductos = data;
    });
  }

  // Devuelve información adicional del producto (resistencia o tamaño)
  getDetalleExtra(producto: Producto): string {
    if (producto.resistencia) return `Resistencia: ${producto.resistencia}`;
    if (producto.tamano) return `Tamaño: ${producto.tamano}`;
    return '';
  }
}
