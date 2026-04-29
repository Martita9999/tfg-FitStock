import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';

// Servicio para gestionar las peticiones HTTP relacionadas con usuarios
@Injectable({ providedIn: 'root' })
export class UsuarioService {
  // URL base de la API backend
  private API_URL = 'http://localhost/proyecto-fitStock/index.php'; // Ajusta según tu servidor local

  constructor(private http: HttpClient) {}

  // Solicita la lista de usuarios al backend
  getUsuarios() {
    return this.http.get(`${this.API_URL}/usuarios`); 
  }
}