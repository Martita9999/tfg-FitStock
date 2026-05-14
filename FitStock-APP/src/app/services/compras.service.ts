import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Compra } from '../interfaces/app.interfaces';

// Servicio para gestionar compras de productos realizadas por usuarios
@Injectable({ providedIn: 'root' })
export class ComprasService {
  private http = inject(HttpClient);                    // Cliente HTTP de Angular
  private API_URL = 'https://chomsky.es/API/api';        // URL base de la API
  private opts = { withCredentials: true };             // Opciones con credenciales

  // Obtiene todas las compras, opcionalmente filtradas por ID de usuario
  getCompras(id_usuario?: number) {
    let url = `${this.API_URL}/compras`;
    if (id_usuario) {
      url += `?id_usuario=${id_usuario}`;               // Filtra por usuario si se especifica
    }
    return this.http.get<Compra[]>(url, this.opts);
  }

  // Registra una nueva compra de producto (descontando stock automáticamente)
  createCompra(data: { id_producto: number; cantidad: number; precio_unitario: number }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/compras`, data, this.opts);
  }
}
