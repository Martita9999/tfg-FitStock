import { Component } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-portal',
  standalone: true,
  templateUrl: './portal.html',
  styleUrl: './portal.css',
})
// Componente de la página de aterrizaje (landing page) del portal público
export class PortalComponent {
  constructor(private router: Router) {}

  // Navega a la pantalla de inicio de sesión
  irALogin() {
    this.router.navigate(['/login']);
  }
}
