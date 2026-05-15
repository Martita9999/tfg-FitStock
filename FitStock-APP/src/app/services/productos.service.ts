import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ProductoStock } from '../interfaces/app.interfaces';

// Servicio para gestionar el CRUD de productos de la tienda/stock.
// Consume los endpoints bajo /api/productos.
@Injectable({ providedIn: 'root' })
export class ProductosService {
  private http = inject(HttpClient);
  private API_URL = 'http://localhost:8000/api';
  private opts = { withCredentials: true };

  // Obtiene todos los productos con su stock actual
  getProductos() {
    return this.http.get<ProductoStock[]>(`${this.API_URL}/productos`, this.opts);
  }

  // Crea un nuevo producto con nombre, descripción, cantidad, stock mínimo y precio
  createProducto(data: { nombre: string; descripcion?: string; cantidad: number; stock_minimo: number; precio: number }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/productos`, data, this.opts);
  }

  // Actualiza los datos de un producto existente por su ID
  updateProducto(id: number, data: { nombre?: string; descripcion?: string; cantidad?: number; stock_minimo?: number; precio?: number }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/productos/${id}`, data, this.opts);
  }

  // Elimina un producto del sistema por su ID
  deleteProducto(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/productos/${id}`, this.opts);
  }

  // Sube una imagen para un producto (multipart/form-data).
  // 'nombre' debe coincidir con el nombre del producto para que getImagenUrl() lo encuentre.
  subirImagen(nombre: string, file: File) {
    const fd = new FormData();
    fd.append('nombre', nombre);      // Nombre del producto (para nombrar el archivo en el servidor)
    fd.append('imagen', file);         // Archivo de imagen seleccionado por el usuario
    return this.http.post<{ success: boolean; imagen: string }>(
      `${this.API_URL}/productos/subir-imagen`, fd, { withCredentials: true }
    );
  }
}
