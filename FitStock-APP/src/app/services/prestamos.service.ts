import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Prestamo } from '../interfaces/app.interfaces';

/* PrestamosService: servicio HTTP para operaciones CRUD de préstamos.
 * Cada método se mapea a un endpoint REST en api/index.php (case 'prestamos').
 * El backend controla los roles (cliente vs admin/entrenador) y el ciclo de estados. */
@Injectable({ providedIn: 'root' })
export class PrestamosService {
  private http = inject(HttpClient);
  private API_URL = 'https://chomsky.es/API/api';
  private opts = { withCredentials: true };                              // Sesión PHP

  /* getPrestamos: GET /api/prestamos. Filtra por id_usuario si se pasa. */
  getPrestamos(id_usuario?: number) {
    let url = `${this.API_URL}/prestamos`;
    if (id_usuario) url += `?id_usuario=${id_usuario}`;
    return this.http.get<Prestamo[]>(url, this.opts);
  }

  /* getPrestamosPorEstado: GET /api/prestamos?estado=... (pendiente, activo, etc.) */
  getPrestamosPorEstado(estado: string) {
    return this.http.get<Prestamo[]>(`${this.API_URL}/prestamos?estado=${estado}`, this.opts);
  }

  /* createPrestamo: POST /api/prestamos. Crea nuevo préstamo con estado según el rol. */
  createPrestamo(data: { id_usuario?: number; id_material: number; fecha_devolucion?: string | null; estado?: string }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/prestamos`, data, this.opts);
  }

  /* updatePrestamo: PUT /api/prestamos/{id}. Actualiza fecha_devolucion. */
  updatePrestamo(id: number, data: { fecha_devolucion?: string | null }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/prestamos/${id}`, data, this.opts);
  }

  /* devolverPrestamo: PUT /api/prestamos/{id} sin fecha -> cliente marca para devolución.
   * El backend cambia estado a 'pendiente_devolucion'. */
  devolverPrestamo(id: number) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/prestamos/${id}`, {}, this.opts);
  }

  /* aprobarPrestamo: PUT /api/prestamos/{id}/aprobar. Admin pasa estado a 'activo'. */
  aprobarPrestamo(id: number) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/prestamos/${id}/aprobar`, {}, this.opts);
  }

  /* confirmarDevolucion: PUT /api/prestamos/{id}/confirmar-devolucion.
   * Admin confirma que el material ha sido devuelto -> estado 'devuelto'. */
  confirmarDevolucion(id: number) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/prestamos/${id}/confirmar-devolucion`, {}, this.opts);
  }

  /* deletePrestamo: DELETE /api/prestamos/{id}. Solo admin/entrenador. */
  deletePrestamo(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/prestamos/${id}`, this.opts);
  }
}
