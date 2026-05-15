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
// Componente principal del panel de administración que gestiona la navegación,
// el usuario autenticado, el tema oscuro, la sidebar y el carrito de compras
export class AdminDashboardComponent implements OnInit {
  private usuarioService = inject(UsuarioService);    // Servicio de autenticación y sesión
  private toastService = inject(ToastService);        // Servicio de notificaciones toast
  private router = inject(Router);                    // Router para navegación programática
  cartService = inject(CartService);                  // Servicio del carrito de compras (público para la plantilla)

  user: Usuario | null = null;          // Usuario actual autenticado
  sidebarCollapsed = false;             // Estado colapsado de la barra lateral
  darkMode = false;                     // Modo oscuro activo
  showCart = false;                     // Visibilidad del panel del carrito

  // Al iniciar, verifica la sesión, se suscribe al usuario actual y aplica el tema
  ngOnInit() {
    this.usuarioService.checkSession();
    this.usuarioService.currentUser$.subscribe(u => this.user = u);
    this.darkMode = localStorage.getItem('darkMode') === 'true';
    this.applyTheme();
  }

  // Alterna el estado colapsado de la barra lateral
  toggleSidebar() {
    this.sidebarCollapsed = !this.sidebarCollapsed;
  }

  // Fuerza el cierre de la barra lateral
  closeSidebar() {
    this.sidebarCollapsed = false;
  }

  // Alterna entre modo claro y oscuro, persiste la preferencia y aplica el tema
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

  // Devuelve la etiqueta legible del rol del usuario autenticado
  getRoleLabel(): string {
    switch (this.user?.rol) {
      case 'admin': return 'Administrador';
      case 'entrenador': return 'Entrenador';
      default: return 'Cliente';
    }
  }

  // Cierra la sesión del usuario y redirige a la pantalla de login
  logout() {
    this.usuarioService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }

  // Alterna la visibilidad del panel del carrito de compras
  toggleCart() {
    this.showCart = !this.showCart;
  }

  // Cierra el panel del carrito de compras
  cerrarCart() {
    this.showCart = false;
  }

  // Procesa la compra de todos los productos en el carrito.
  // Muestra un toast con el resultado (éxito o error)
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
