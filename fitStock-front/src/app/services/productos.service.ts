import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ProductoStock } from '../interfaces/app.interfaces';

// Servicio para gestionar productos en stock del gimnasio
@Injectable({ providedIn: 'root' })
export class ProductosService {
  private http = inject(HttpClient);                    // Cliente HTTP de Angular
  private API_URL = 'http://localhost:8000/api';        // URL base de la API
  private opts = { withCredentials: true };             // Opciones con credenciales

  // Obtiene todos los productos en stock
  getProductos() {
    return this.http.get<ProductoStock[]>(`${this.API_URL}/productos`, this.opts);
  }

  // Crea un nuevo producto
  createProducto(data: { nombre: string; cantidad: number; stock_minimo: number; precio: number }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/productos`, data, this.opts);
  }

  // Actualiza la cantidad de un producto
  updateProducto(id: number, data: { cantidad: number }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/productos/${id}`, data, this.opts);
  }

  // Elimina un producto por su ID
  deleteProducto(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/productos/${id}`, this.opts);
  }
}
