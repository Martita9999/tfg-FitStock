import { Component, OnInit, AfterViewInit, HostListener, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, Router } from '@angular/router';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar';
import { UsuarioService, Usuario } from '../../services/usuario';
import { ToastService } from '../../services/toast.service';
import { CartService, CompraResult } from '../../services/cart.service';
import { StripeService } from '../../services/stripe.service';

/*
 * AdminDashboardComponent: layout principal del panel de administración.
 *
 * Gestiona:
 * - Sesión del usuario y sidebar colapsable
 * - Carrito de compras con flujo de pago Stripe
 * - Modo oscuro, logout
 *
 * Flujo de pago:
 *   1. Usuario pulsa "Comprar Ahora"
 *   2. Se crea un PaymentIntent en Stripe
 *   3. Se muestra el formulario de tarjeta Stripe
 *   4. Usuario introduce los datos de su tarjeta
 *   5. Stripe confirma el pago
 *   6. Se finaliza la compra (descuenta stock, registra en BD)
 */
@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent, RouterOutlet],
  templateUrl: './admin-dashboard.html',
  styleUrl: './admin-dashboard.css'
})
export class AdminDashboardComponent implements OnInit, AfterViewInit {
  private usuarioService = inject(UsuarioService);
  private toastService = inject(ToastService);
  private stripeService = inject(StripeService);
  private router = inject(Router);
  cartService = inject(CartService);

  user: Usuario | null = null;
  sidebarCollapsed = window.innerWidth <= 768;
  darkMode = false;
  showCart = false;
  showCardForm = false;      // true cuando hay que mostrar el formulario de Stripe
  clientSecret = '';         // client_secret del PaymentIntent para confirmar el pago
  processingPayment = false; // bloquea el botón mientras se procesa

  ngOnInit() {
    this.usuarioService.checkSession();
    this.usuarioService.currentUser$.subscribe(u => this.user = u);
    this.cartService.openCart$.subscribe(() => this.showCart = true);
    this.darkMode = localStorage.getItem('darkMode') === 'true';
    this.applyTheme();
  }

  getImagenUrl(nombre: string): string {
    return '/API/images/productos/' + encodeURIComponent(nombre) + '.jpg';
  }

  toggleSidebar() {
    this.sidebarCollapsed = !this.sidebarCollapsed;
  }

  closeSidebar() {
    this.sidebarCollapsed = true;
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

  @HostListener('window:resize')
  onResize() {
    this.sidebarCollapsed = window.innerWidth <= 768;
  }

  getRoleLabel(): string {
    switch (this.user?.rol) {
      case 'admin': return 'Administrador';
      case 'entrenador': return 'Entrenador';
      default: return 'Cliente';
    }
  }

  logout() {
    this.stripeService.reset();
    this.usuarioService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }

  ngAfterViewInit() {}

  /*
   * toggleCart: abre/cierra el desplegable del carrito.
   * Al cerrar, limpia el formulario de Stripe para que no quede montado.
   */
  toggleCart() {
    this.showCart = !this.showCart;
    if (!this.showCart) {
      this.showCardForm = false;
      this.stripeService.unmountCard();
    }
  }

  /*
   * cerrarCart: cierra el carrito y limpia el formulario de Stripe.
   */
  cerrarCart() {
    this.showCart = false;
    this.showCardForm = false;
    this.stripeService.unmountCard();
  }

  /*
   * comprar: inicia el flujo de pago.
   *
   * 1. CartService.comprar() verifica stock y devuelve '__NEED_PAYMENT__'
   * 2. StripeService.createPaymentIntent() crea un PaymentIntent en Stripe
   * 3. Se muestra el formulario de tarjeta para que el usuario pague
   */
  async comprar() {
    const result = await this.cartService.comprar();
    if (result === '__NEED_PAYMENT__') {
      try {
        const items = this.cartService.carrito.map(i => ({
          id: i.producto.id,
          cantidad: i.cantidad,
          precio: i.producto.precio
        }));
        const res = await this.stripeService.createPaymentIntent(items);
        this.clientSecret = res.clientSecret!;
        this.showCardForm = true;
        setTimeout(() => this.stripeService.mountCard('card-element'), 100);
      } catch (e: any) {
        const msg = e?.error?.error || 'Error al iniciar el pago';
        this.toastService.show(msg, 'error');
      }
    } else if (result) {
      this.toastService.show(result, 'error');
    }
  }

  /*
   * onCardSubmit: envía el pago con los datos de la tarjeta introducidos.
   *
   * 1. StripeService.confirmPayment() procesa la tarjeta con Stripe
   * 2. Si el pago es exitoso, finalizarCompra() descuenta stock y registra la compra en BD
   */
  async onCardSubmit() {
    if (this.processingPayment) return;
    this.processingPayment = true;

    const error = await this.stripeService.confirmPayment(this.clientSecret);
    if (error) {
      this.toastService.show(error, 'error');
      this.processingPayment = false;
      return;
    }

    const compraError = await this.cartService.finalizarCompra();
    this.processingPayment = false;
    if (compraError) {
      this.toastService.show(compraError, 'error');
    } else {
      this.toastService.show('Compra realizada con éxito', 'success');
      this.showCart = false;
      this.showCardForm = false;
      this.stripeService.unmountCard();
    }
  }
}
