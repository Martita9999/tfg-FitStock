import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Material } from '../interfaces/app.interfaces';

/*
 * MaterialesService: servicio CRUD para equipamiento deportivo.
 * tipo puede ser 'maquina' o 'prestable'. Backend filtra con ?tipo=.
 */
@Injectable({ providedIn: 'root' })
export class MaterialesService {
  private http = inject(HttpClient);
  private API_URL = 'https://chomsky.es/API/api';
  private opts = { withCredentials: true };

  /* getMateriales(tipo?): filtra por tipo o devuelve todos */
  getMateriales(tipo?: string) {
    const url = tipo ? `${this.API_URL}/materiales?tipo=${tipo}` : `${this.API_URL}/materiales`;  // Filtro opcional
    return this.http.get<Material[]>(url, this.opts);
  }

  /* createMaterial: estado por defecto 'operativo', tipo por defecto 'maquina' */
  createMaterial(data: { nombre: string; descripcion?: string; estado?: string; id_tag_material?: string; ubicacion?: string; tipo?: string }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/materiales`, data, this.opts);
  }

  /* updateMaterial: ultima_rev registra la fecha de la última revisión de mantenimiento */
  updateMaterial(id: number, data: { nombre?: string; descripcion?: string; estado?: string; ultima_rev?: string | null; ubicacion?: string; id_tag_material?: string }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/materiales/${id}`, data, this.opts);
  }

  deleteMaterial(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/materiales/${id}`, this.opts);
  }
}
