import { Component, OnInit, inject, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { UsuarioService, Usuario } from '../../services/usuario';

// Decorador de componente: selector HTML, módulos importados,
// plantilla y hoja de estilos de la barra lateral de administración
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
  darkMode = false; // Estado del tema oscuro local
  // Propiedades de entrada/salida para controlar el colapso de la sidebar
  @Input() collapsed = false;         // Indica si la sidebar está colapsada
  @Output() toggleSidebar = new EventEmitter<void>(); // Emite evento al alternar colapso
  @Output() closeSidebar = new EventEmitter<void>();  // Emite evento al cerrar sidebar

  // Al iniciar, verifica la sesión, se suscribe al usuario actual
  // y aplica el tema guardado en localStorage
  ngOnInit() {
    this.usuarioService.checkSession();
    this.usuarioService.currentUser$.subscribe(u => this.user = u);
    this.darkMode = localStorage.getItem('darkMode') === 'true';
    this.applyTheme();
  }

  // Alterna el modo oscuro, persiste en localStorage y aplica el tema
  toggleDarkMode() {
    this.darkMode = !this.darkMode;
    localStorage.setItem('darkMode', String(this.darkMode));
    this.applyTheme();
  }

  // Aplica o elimina el atributo `data-theme` en el elemento raíz del documento
  private applyTheme() {
    if (this.darkMode) {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  }

  usuariosSubmenuOpen = false;

  toggleUsuariosSubmenu() {
    this.usuariosSubmenuOpen = !this.usuariosSubmenuOpen;
  }

  logout() {
    this.usuarioService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
