import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface ResumenData {
  incidencias: { abierta: number; en_proceso: number; resuelta: number };
  stock_bajo: { id: number; nombre: string; cantidad: number; stock_minimo: number }[];
  maquinas: { por_estado: Record<string, number>; total: number };
  gastos: {
    total: number;
    por_usuario: { id: number; nombre: string; email: string; total: number }[];
  };
}

/*
 * ResumenService: datos agregados para el dashboard de administración.
 * Un solo GET que devuelve incidencias, stock bajo, estado máquinas y gastos.
 */
@Injectable({ providedIn: 'root' })
export class ResumenService {
  private http = inject(HttpClient);
  private API_URL = 'http://localhost:8000/api';
  private httpOptions = { withCredentials: true };

  obtenerResumen() {
    return this.http.get<ResumenData>(`${this.API_URL}/resumen`, this.httpOptions);  // GET con datos agregados
  }
}
