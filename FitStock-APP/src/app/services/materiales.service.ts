import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Material } from '../interfaces/app.interfaces';

// Servicio para gestionar materiales/equipamiento deportivo
@Injectable({ providedIn: 'root' })
export class MaterialesService {
  private http = inject(HttpClient);                    // Cliente HTTP de Angular
  private API_URL = 'https://chomsky.es/API/api';        // URL base de la API
  private opts = { withCredentials: true };             // Opciones con credenciales

  // Obtiene todos los materiales (opcionalmente filtrados por tipo)
  getMateriales(tipo?: string) {
    const url = tipo ? `${this.API_URL}/materiales?tipo=${tipo}` : `${this.API_URL}/materiales`;
    return this.http.get<Material[]>(url, this.opts);
  }

  // Crea un nuevo material
  createMaterial(data: { nombre: string; descripcion?: string; estado?: string; id_tag_material?: string; ubicacion?: string; tipo?: string }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/materiales`, data, this.opts);
  }

  // Actualiza un material
  updateMaterial(id: number, data: { nombre?: string; descripcion?: string; estado?: string; ultima_rev?: string | null; ubicacion?: string; id_tag_material?: string }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/materiales/${id}`, data, this.opts);
  }

  // Elimina un material por su ID
  deleteMaterial(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/materiales/${id}`, this.opts);
  }
}
