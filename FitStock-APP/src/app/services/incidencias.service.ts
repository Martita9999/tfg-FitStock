import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Incidencia } from '../interfaces/app.interfaces';

/*
 * IncidenciasService: servicio CRUD para incidencias reportadas sobre materiales.
 * 
 * Al crear una incidencia, el backend marca automáticamente el material como 'averiado'.
 * Al resolverla, el material vuelve a estado 'operativo'.
 */
@Injectable({ providedIn: 'root' })
export class IncidenciasService {
  private http = inject(HttpClient);
  private API_URL = 'http://localhost:8000/api';
  private opts = { withCredentials: true };

  getIncidencias(id_usuario?: number) {
    let url = `${this.API_URL}/incidencias`;
    if (id_usuario) url += `?id_usuario=${id_usuario}`;
    return this.http.get<Incidencia[]>(url, this.opts);
  }

  /*
   * createIncidencia: requiere id_material, descripcion y prioridad.
   * El backend asigna el usuario de la sesión como reportador.
   */
  createIncidencia(data: { id_material: number; descripcion: string; prioridad: string }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/incidencias`, data, this.opts);
  }

  /*
   * updateIncidencia: actualiza descripción, prioridad y/o estado de una incidencia.
   * Cambiar a 'resuelta' pone el material como 'operativo'.
   * Cambiar a 'en_proceso' pone el material como 'en_reparacion'.
   */
  updateIncidencia(id: number, data: { descripcion?: string; prioridad?: string; estado?: string }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/incidencias/${id}`, data, this.opts);
  }

  deleteIncidencia(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/incidencias/${id}`, this.opts);
  }
}
