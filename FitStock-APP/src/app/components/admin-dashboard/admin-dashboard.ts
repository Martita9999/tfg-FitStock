import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, Router } from '@angular/router';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar';
import { UsuarioService, Usuario } from '../../services/usuario';
import { ToastService } from '../../services/toast.service';
import { CartService } from '../../services/cart.service';

/*
 * AdminDashboardComponent: layout principal del panel de administración.
 * Gestiona sesión, sidebar colapsable, modo oscuro, carrito y logout.
 */
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
    this.usuarioService.checkSession();                                   // Restaura sesión desde localStorage
    this.usuarioService.currentUser$.subscribe(u => this.user = u);       // Escucha cambios de usuario
    this.cartService.openCart$.subscribe(() => this.showCart = true);     // Abre carrito al añadir producto
    this.darkMode = localStorage.getItem('darkMode') === 'true';
    this.applyTheme();
  }

  getImagenUrl(nombre: string): string {
    return '/images/productos/' + encodeURIComponent(nombre) + '.jpg';
  }

  toggleSidebar() {
    this.sidebarCollapsed = !this.sidebarCollapsed;                       // Colapsa/expande sidebar
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
      document.documentElement.setAttribute('data-theme', 'dark');         // Activa modo oscuro
    } else {
      document.documentElement.removeAttribute('data-theme');              // Vuelve a modo claro
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
    this.usuarioService.logout().subscribe(() => {                       // Cierra sesión
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
    const error = await this.cartService.comprar();                       // Procesa compra del carrito
    if (error) {
      this.toastService.show(error, 'error');
    } else {
      this.toastService.show('Compra realizada con éxito', 'success');
      this.showCart = false;
    }
  }
}
