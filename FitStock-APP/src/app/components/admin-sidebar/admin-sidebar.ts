import { Component, OnInit, inject, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { UsuarioService, Usuario } from '../../services/usuario';

/*
 * AdminSidebarComponent: barra lateral de navegación del panel admin.
 * Incluye enlaces por rol, submenú de usuarios, modo oscuro y logout.
 * @Input collapsed: estado colapsado del dashboard padre.
 * Emite toggleSidebar y closeSidebar al padre.
 */
@Component({
  selector: 'app-admin-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './admin-sidebar.html',
  styleUrl: './admin-sidebar.css'
})
export class AdminSidebarComponent implements OnInit {
  private usuarioService = inject(UsuarioService);
  private router = inject(Router);
  user: Usuario | null = null;
  darkMode = false;
  @Input() collapsed = false;
  @Output() toggleSidebar = new EventEmitter<void>();
  @Output() closeSidebar = new EventEmitter<void>();

  ngOnInit() {
    this.usuarioService.checkSession();
    this.usuarioService.currentUser$.subscribe(u => this.user = u);
    this.darkMode = localStorage.getItem('darkMode') === 'true';
    this.applyTheme();
  }

  toggleDarkMode() {
    this.darkMode = !this.darkMode;
    localStorage.setItem('darkMode', String(this.darkMode));
    this.applyTheme();
  }

  private applyTheme() {
    if (this.darkMode) {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  }

  get roleLabel(): string {
    switch (this.user?.rol) {
      case 'admin': return 'Administrador';
      case 'entrenador': return 'Entrenador';
      default: return 'Cliente';
    }
  }

  usuariosSubmenuOpen = false;
  prestamosSubmenuOpen = false;
  materialesSubmenuOpen = false;
  incidenciasSubmenuOpen = false;

  toggleUsuariosSubmenu() {
    this.usuariosSubmenuOpen = !this.usuariosSubmenuOpen;
    this.router.navigate(['/admin/usuarios']);
  }

  togglePrestamosSubmenu() {
    this.prestamosSubmenuOpen = !this.prestamosSubmenuOpen;
    this.router.navigate(['/admin/prestamos']);
  }

  toggleMaterialesSubmenu() {
    this.materialesSubmenuOpen = !this.materialesSubmenuOpen;
    this.router.navigate(['/admin/materiales']);
  }

  toggleIncidenciasSubmenu() {
    this.incidenciasSubmenuOpen = !this.incidenciasSubmenuOpen;
    this.router.navigate(['/admin/incidencias']);
  }

  logout() {
    this.usuarioService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
