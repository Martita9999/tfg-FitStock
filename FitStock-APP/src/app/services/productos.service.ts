import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ProductoStock } from '../interfaces/app.interfaces';

/*
 * ProductosService: servicio CRUD para productos en stock.
 * Incluye subida de imágenes con FormData. El nombre del producto
 * se usa como nombre de archivo de la imagen en el servidor.
 */
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

  /* updateProducto: si solo se pasa cantidad sin nombre, backend interpreta como actualización de stock */
  updateProducto(id: number, data: { nombre?: string; descripcion?: string; cantidad?: number; stock_minimo?: number; precio?: number }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/productos/${id}`, data, this.opts);
  }

  deleteProducto(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/productos/${id}`, this.opts);
  }

  /* subirImagen: envía imagen como multipart/form-data. Backend valida MIME real con finfo. */
  subirImagen(nombre: string, file: File) {
    const fd = new FormData();                                               // FormData para subida de archivos
    fd.append('nombre', nombre);
    fd.append('imagen', file);
    return this.http.post<{ success: boolean; imagen: string }>(
      `${this.API_URL}/productos/subir-imagen`, fd, { withCredentials: true }
    );
  }
}
