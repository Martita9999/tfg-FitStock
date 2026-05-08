import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { UsuarioService, Usuario } from '../../services/usuario';

// Componente de la barra lateral de navegación del panel admin
@Component({
  selector: 'app-admin-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule],        // Módulos: directivas comunes + enrutamiento
  templateUrl: './admin-sidebar.html',
  styleUrl: './admin-sidebar.css'
})
export class AdminSidebarComponent implements OnInit {
  private usuarioService = inject(UsuarioService);   // Servicio de autenticación
  private router = inject(Router);                    // Router para navegación
  user: Usuario | null = null;                         // Usuario actual (para mostrar datos y controlar visibilidad)

  // Al iniciar, restaura la sesión y se suscribe al usuario actual
  ngOnInit() {
    this.usuarioService.checkSession();                                     // Restaura sesión desde localStorage
    this.usuarioService.currentUser$.subscribe(u => this.user = u);        // Se suscribe a cambios del usuario
  }

  // Cierra la sesión y redirige al login
  logout() {
    this.usuarioService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
