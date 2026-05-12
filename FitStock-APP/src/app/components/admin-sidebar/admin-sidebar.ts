import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { UsuarioService, Usuario } from '../../services/usuario';

@Component({
  selector: 'app-admin-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './admin-sidebar.html',
  styleUrl: './admin-sidebar.css'
})
// Componente de la barra lateral de navegación del panel admin
export class AdminSidebarComponent implements OnInit {
  private usuarioService = inject(UsuarioService);   // Servicio de autenticación
  private router = inject(Router);                   // Router para navegación
  user: Usuario | null = null;                        // Usuario actual (para mostrar nombre y rol en la sidebar)

  // Al iniciar, restaura sesión desde localStorage y se suscribe al usuario actual
  ngOnInit() {
    this.usuarioService.checkSession();
    this.usuarioService.currentUser$.subscribe(u => this.user = u);
  }

  logout() {
    this.usuarioService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
