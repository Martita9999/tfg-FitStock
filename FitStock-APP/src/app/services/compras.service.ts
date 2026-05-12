import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Compra } from '../interfaces/app.interfaces';

@Injectable({ providedIn: 'root' })
export class ComprasService {
  private http = inject(HttpClient);
  private API_URL = 'http://localhost:8000/api';
  private opts = { withCredentials: true };

  getCompras(id_usuario?: number) {
    let url = `${this.API_URL}/compras`;
    if (id_usuario) {
      url += `?id_usuario=${id_usuario}`;
    }
    return this.http.get<Compra[]>(url, this.opts);
  }

  createCompra(data: { id_producto: number; cantidad: number; precio_unitario: number }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/compras`, data, this.opts);
  }
}
