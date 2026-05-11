import { Injectable } from '@angular/core';
import { ProductoStock, Prestamo, Incidencia } from '../interfaces/app.interfaces';
import { Observable, of } from 'rxjs';

// Servicio de datos mock para desarrollo/pruebas sin conexión a la API real
@Injectable({
  providedIn: 'root'
})
export class MockDataService {

  // Datos mock de productos
  private productosMock: ProductoStock[] = [
    { id: 1, nombre: 'Banda elástica', cantidad: 8, stock_minimo: 2, precio: 5 },
    { id: 2, nombre: 'Esterilla yoga', cantidad: 12, stock_minimo: 3, precio: 15 },
  ];

  // Datos mock de préstamos
  private prestamosMock: Prestamo[] = [
    { id: 101, usuario: 'Juan Pérez', material: 'Banda elástica', fecha: '2026-05-01', devolucion: null },
  ];

  // Datos mock de incidencias
  private incidenciasMock: Incidencia[] = [
    { id: 201, descripcion: 'Ruido extraño en el motor', prioridad: 'media', estado: 'Abierta' },
  ];

  // Devuelve productos mock como Observable
  getProductos(): Observable<ProductoStock[]> {
    return of(this.productosMock);
  }

  // Devuelve préstamos mock como Observable
  getPrestamos(): Observable<Prestamo[]> {
    return of(this.prestamosMock);
  }

  // Devuelve incidencias mock como Observable
  getIncidencias(): Observable<Incidencia[]> {
    return of(this.incidenciasMock);
  }
}
