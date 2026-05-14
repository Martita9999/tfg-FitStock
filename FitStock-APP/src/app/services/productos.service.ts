import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ProductoStock } from '../interfaces/app.interfaces';

@Injectable({ providedIn: 'root' })
export class ProductosService {
  private http = inject(HttpClient);
  private API_URL = 'https://chomsky.es/API/api';
  private opts = { withCredentials: true };

  getProductos() {
    return this.http.get<ProductoStock[]>(`${this.API_URL}/productos`, this.opts);
  }

  createProducto(data: { nombre: string; descripcion?: string; cantidad: number; stock_minimo: number; precio: number }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/productos`, data, this.opts);
  }

  updateProducto(id: number, data: { nombre?: string; descripcion?: string; cantidad?: number; stock_minimo?: number; precio?: number }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/productos/${id}`, data, this.opts);
  }

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
