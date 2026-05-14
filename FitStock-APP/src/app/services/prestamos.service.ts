import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Prestamo } from '../interfaces/app.interfaces';

// Servicio para gestionar préstamos de material a usuarios
@Injectable({ providedIn: 'root' })
export class PrestamosService {
  private http = inject(HttpClient);                    // Cliente HTTP de Angular
  private API_URL = 'https://chomsky.es/API/api';        // URL base de la API
  private opts = { withCredentials: true };             // Opciones con credenciales

  // Obtiene todos los préstamos
  getPrestamos() {
    return this.http.get<Prestamo[]>(`${this.API_URL}/prestamos`, this.opts);
  }

  // Crea un nuevo préstamo
  createPrestamo(data: { id_usuario?: number; id_material: number; fecha_devolucion?: string | null }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/prestamos`, data, this.opts);
  }

  // Actualiza un préstamo (fecha de devolución)
  updatePrestamo(id: number, data: { fecha_devolucion?: string | null }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/prestamos/${id}`, data, this.opts);
  }

  // Marca un préstamo como devuelto (fecha_devolucion = fecha actual)
  devolverPrestamo(id: number) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/prestamos/${id}`, {}, this.opts);
  }

  // Elimina un préstamo por su ID
  deletePrestamo(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/prestamos/${id}`, this.opts);
  }
}
