import { Component, inject, Input } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';

@Component({
  selector: 'app-contacto',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './contacto.html',
  styleUrl: './contacto.css',
})
// Componente del formulario de contacto que envía consultas al backend
export class ContactoComponent {
  @Input() embed = false;                 // Si es true, se incrusta en otra página (portal) sin navegación externa
  private http = inject(HttpClient);      // Cliente HTTP para peticiones al backend
  private router = inject(Router);        // Router para navegación programática

  email = '';             // Email del remitente (two-way binding)
  mensaje = '';           // Texto del mensaje (two-way binding)
  error = '';             // Mensaje de error a mostrar
  success = false;        // Indica si el envío fue exitoso
  sending = false;        // Indica si la petición está en curso (para deshabilitar botón)

  private API_URL = 'http://localhost:8000/api';    // URL base de la API REST

  // Navega a la página de aterrizaje (solo visible cuando no está embebido)
  irAPortal() {
    this.router.navigate(['/']);
  }

  // Valida los campos y envía el formulario de contacto al backend via POST.
  // En caso de éxito limpia el formulario; en caso de error muestra el mensaje
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
