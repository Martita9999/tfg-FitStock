// src/app/services/mock-data.service.ts
import { Injectable } from '@angular/core';
import { Producto, Prestamo, Incidencia } from '../interfaces/app.interfaces';
import { Observable, of } from 'rxjs'; // Importante para simular asincronía

@Injectable({
  providedIn: 'root'
})
export class MockDataService {

  // 👇 AQUÍ ESTÁN TUS DATOS HARDCODEADOS
  private productosMock: Producto[] = [
    { id: 1, nombre: 'Banda elástica', categoria: 'Accesorios', resistencia: 'media', stockDisponible: 8 },
    { id: 2, nombre: 'Banda elástica', categoria: 'Accesorios', resistencia: 'alta', stockDisponible: 5 },
    { id: 3, nombre: 'Esterilla yoga', categoria: 'Yoga & Pilates', tamano: 'Estándar', stockDisponible: 12 },
    { id: 4, nombre: 'Pelota pilates', categoria: 'Yoga & Pilates', tamano: '65cm', stockDisponible: 6 },
  ];

  private prestamosMock: Prestamo[] = [
    { id: 101, productoId: 1, productoNombre: 'Banda elástica - media', usuarioNombre: 'Juan Pérez', usuarioTelefono: '612345678', cantidad: 1, diasPrestamo: 3, estado: 'Pendiente' },
  ];

  private incidenciasMock: Incidencia[] = [
    { id: 201, area: 'Zona de Cardio', maquinaEquipo: 'Cinta #3', titulo: 'Ruido extraño en el motor', descripcion: 'Hace un chirrido al subir la velocidad.', urgencia: 'Moderada', usuarioNombre: 'Ana García', estado: 'Abierta' },
  ];

  constructor() { }

  // Métodos que simulan peticiones HTTP a la API (devuelven Observables)
  getProductos(): Observable<Producto[]> {
    return of(this.productosMock); // 'of' convierte un array en un Observable
  }

  getPrestamos(): Observable<Prestamo[]> {
    return of(this.prestamosMock);
  }

  getIncidencias(): Observable<Incidencia[]> {
    return of(this.incidenciasMock);
  }
}