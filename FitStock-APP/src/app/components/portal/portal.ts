import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { ContactoComponent } from '../contacto/contacto';

/*
 * PortalComponent: landing page pública del gimnasio.
 * Muestra info general e incluye el formulario de contacto.
 */
@Component({
  selector: 'app-portal',
  standalone: true,
  imports: [ContactoComponent],
  templateUrl: './portal.html',
  styleUrl: './portal.css',
})
export class PortalComponent {
  constructor(private router: Router) {}

  irALogin() {
    this.router.navigate(['/login']);                                    // Navega al login
  }
}
