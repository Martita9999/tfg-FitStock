// Importa el decorador Component desde Angular Core para definir metadatos del componente
import { Component } from '@angular/core';
// Importa el router para poder navegar programáticamente entre rutas
import { Router } from '@angular/router';
import { ContactoComponent } from '../contacto/contacto';

// Decorador que asigna metadatos al componente: selector, plantilla y estilos
@Component({
  // Selector CSS que identifica este componente en el HTML (<app-portal>)
  selector: 'app-portal',
  // Indica que es un componente standalone (no necesita NgModule)
  standalone: true,
  imports: [ContactoComponent],
  // Ruta relativa al archivo HTML de la plantilla
  templateUrl: './portal.html',
  // Ruta relativa al archivo CSS de estilos
  styleUrl: './portal.css',
})
// Componente de la página de aterrizaje (landing page) del portal público
export class PortalComponent {
  // Inyecta el servicio Router en el constructor para navegación programática
  constructor(private router: Router) {}

  // Navega a la pantalla de inicio de sesión al hacer clic en "Acceder al Panel"
  irALogin() {
    // Redirige a la ruta /login definida en app.routes.ts
    this.router.navigate(['/login']);
  }
}
