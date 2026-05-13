import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet } from '@angular/router';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar';
import { UsuarioService } from '../../services/usuario';
import { ToastService } from '../../services/toast.service';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent, RouterOutlet],
  templateUrl: './admin-dashboard.html',
  styleUrl: './admin-dashboard.css'
})
export class AdminDashboardComponent implements OnInit {
  private usuarioService = inject(UsuarioService);
  private toastService = inject(ToastService);
  userRole = '';
  sidebarCollapsed = false; // Controla si la barra lateral está colapsada o visible

  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
  }

  // Alterna el estado colapsado de la sidebar
  toggleSidebar() {
    this.sidebarCollapsed = !this.sidebarCollapsed;
  }

  // Fuerza el cierre de la sidebar (colapsada = false)
  closeSidebar() {
    this.sidebarCollapsed = false;
  }
}
