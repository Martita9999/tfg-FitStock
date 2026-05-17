import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Prestamo } from '../interfaces/app.interfaces';

@Injectable({ providedIn: 'root' })
export class PrestamosService {
  private http = inject(HttpClient);
  private API_URL = 'http://localhost:8000/api';
  private opts = { withCredentials: true };

  getPrestamos(id_usuario?: number) {
    let url = `${this.API_URL}/prestamos`;
    if (id_usuario) url += `?id_usuario=${id_usuario}`;
    return this.http.get<Prestamo[]>(url, this.opts);
  }

  getPrestamosPorEstado(estado: string) {
    return this.http.get<Prestamo[]>(`${this.API_URL}/prestamos?estado=${estado}`, this.opts);
  }

  createPrestamo(data: { id_usuario?: number; id_material: number; fecha_devolucion?: string | null; estado?: string }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/prestamos`, data, this.opts);
  }

  updatePrestamo(id: number, data: { fecha_devolucion?: string | null }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/prestamos/${id}`, data, this.opts);
  }

  devolverPrestamo(id: number) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/prestamos/${id}`, {}, this.opts);
  }

  aprobarPrestamo(id: number) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/prestamos/${id}/aprobar`, {}, this.opts);
  }

  confirmarDevolucion(id: number) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/prestamos/${id}/confirmar-devolucion`, {}, this.opts);
  }

  deletePrestamo(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/prestamos/${id}`, this.opts);
  }
}
