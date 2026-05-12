import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Incidencia } from '../interfaces/app.interfaces';

// Servicio para gestionar incidencias reportadas en materiales
@Injectable({ providedIn: 'root' })
export class IncidenciasService {
  private http = inject(HttpClient);                    // Cliente HTTP de Angular
  private API_URL = 'http://localhost:8000/api';        // URL base de la API
  private opts = { withCredentials: true };             // Opciones con credenciales

  // Obtiene todas las incidencias
  getIncidencias() {
    return this.http.get<Incidencia[]>(`${this.API_URL}/incidencias`, this.opts);
  }

  // Crea una nueva incidencia
  createIncidencia(data: { id_material: number; descripcion: string; prioridad: string }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/incidencias`, data, this.opts);
  }

  // Actualiza una incidencia (descripción, prioridad y/o estado)
  updateIncidencia(id: number, data: { descripcion?: string; prioridad?: string; estado?: string }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/incidencias/${id}`, data, this.opts);
  }

  // Elimina una incidencia por su ID
  deleteIncidencia(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/incidencias/${id}`, this.opts);
  }
}
