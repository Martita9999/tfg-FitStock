import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, Router } from '@angular/router';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar';
import { UsuarioService, Usuario } from '../../services/usuario';
import { ToastService } from '../../services/toast.service';
import { CartService } from '../../services/cart.service';

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
  private router = inject(Router);
  cartService = inject(CartService);

  user: Usuario | null = null;
  sidebarCollapsed = false;
  darkMode = false;
  showCart = false;

  ngOnInit() {
    this.usuarioService.checkSession();
    this.usuarioService.currentUser$.subscribe(u => this.user = u);
    this.darkMode = localStorage.getItem('darkMode') === 'true';
    this.applyTheme();
  }

  toggleSidebar() {
    this.sidebarCollapsed = !this.sidebarCollapsed;
  }

  closeSidebar() {
    this.sidebarCollapsed = false;
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

  getRoleLabel(): string {
    switch (this.user?.rol) {
      case 'admin': return 'Administrador';
      case 'entrenador': return 'Entrenador';
      default: return 'Cliente';
    }
  }

  logout() {
    this.usuarioService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }

  toggleCart() {
    this.showCart = !this.showCart;
  }

  cerrarCart() {
    this.showCart = false;
  }

  async comprar() {
    const error = await this.cartService.comprar();
    if (error) {
      this.toastService.show(error, 'error');
    } else {
      this.toastService.show('Compra realizada con éxito', 'success');
      this.showCart = false;
    }
  }
}
