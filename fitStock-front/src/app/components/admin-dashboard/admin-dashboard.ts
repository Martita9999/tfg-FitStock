import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet } from '@angular/router';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar.component';
import { UsuarioService } from '../../services/usuario';

// Componente layout del panel de administración (sidebar + contenido)
@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent, RouterOutlet],
  templateUrl: './admin-dashboard.html',
  styleUrl: './admin-dashboard.css'
})
export class AdminDashboardComponent implements OnInit {
  private usuarioService = inject(UsuarioService);   // Servicio para obtener el rol del usuario
  userRole = '';                                       // Rol del usuario actual

  // Al iniciar, se suscribe al usuario actual para conocer su rol
  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
  }
}
