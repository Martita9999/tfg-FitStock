import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Compra } from '../interfaces/app.interfaces';

/*
 * ComprasService: servicio para gestionar compras de productos.
 * Clientes solo ven sus propias compras; admin/entrenadores ven todas.
 * Al crear compra, backend descuenta stock automáticamente.
 */
@Injectable({ providedIn: 'root' })
export class ComprasService {
  private http = inject(HttpClient);
  private API_URL = 'http://localhost:8000/api';
  private opts = { withCredentials: true };

  /* getCompras(id_usuario?): filtra por usuario si se pasa. Cliente filtra por sesión automáticamente. */
  getCompras(id_usuario?: number) {
    let url = `${this.API_URL}/compras`;
    if (id_usuario) {
      url += `?id_usuario=${id_usuario}`;                                   // Filtro opcional por usuario
    }
    return this.http.get<Compra[]>(url, this.opts);
  }

  /* createCompra: registra compra, backend descuenta stock y calcula total */
  createCompra(data: { id_producto: number; cantidad: number; precio_unitario: number }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/compras`, data, this.opts);
  }
}
