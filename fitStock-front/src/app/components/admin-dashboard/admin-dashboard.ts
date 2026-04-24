import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar.component';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent],
  templateUrl: './admin-dashboard.html', 
  styleUrl: './admin-dashboard.css'       
})
export class AdminDashboardComponent implements OnInit {
  
  // Datos hardcodeados directamente aquí para probar rápido
  listaProductos = [
    { id: 1, nombre: 'Banda elástica', categoria: 'Accesorios', resistencia: 'media', stockDisponible: 8 },
    { id: 2, nombre: 'Banda elástica', categoria: 'Accesorios', resistencia: 'alta', stockDisponible: 5 },
    { id: 3, nombre: 'Esterilla yoga', categoria: 'Yoga & Pilates', stockDisponible: 12 },
    { id: 4, nombre: 'Pelota pilates', categoria: 'Yoga & Pilates', stockDisponible: 6 }
  ];

  constructor() { }

  ngOnInit(): void { }
}