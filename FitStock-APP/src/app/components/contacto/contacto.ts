import { Component, inject, Input } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';

/*
 * ContactoComponent: formulario de contacto público.
 * @Input embed: si está en portal, no muestra navegación extra.
 * Envía POST a /api/contacto, backend usa PHPMailer para correo.
 */
@Component({
  selector: 'app-contacto',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './contacto.html',
  styleUrl: './contacto.css',
})
export class ContactoComponent {
  @Input() embed = false;                                                // Si está incrustado en portal
  private http = inject(HttpClient);
  private router = inject(Router);

  email = '';
  mensaje = '';
  error = '';
  success = false;
  sending = false;

  private API_URL = 'https://chomsky.es/API/api';

  irAPortal() {
    this.router.navigate(['/']);
  }

  enviar() {
    this.error = '';
    this.success = false;

    if (!this.email.trim() || !this.mensaje.trim()) {
      this.error = 'Todos los campos son obligatorios';
      return;
    }

    this.sending = true;
    this.http.post<{ success: boolean; message?: string; error?: string }>(
      `${this.API_URL}/contacto`,
      {
        email: this.email.trim(),
        mensaje: this.mensaje.trim(),
      }
    ).subscribe({
      next: (res) => {
        this.sending = false;
        if (res.success) {
          this.success = true;
          this.email = '';
          this.mensaje = '';
        } else {
          this.error = res.error || 'Error al enviar el mensaje';
        }
      },
      error: (err) => {
        this.sending = false;
        this.error = err.error?.error || 'Error de conexión con el servidor';
      }
    });
  }
}
