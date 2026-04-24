import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class UsuarioService {
  private API_URL = 'http://localhost/proyecto-fitStock/index.php'; // Ajusta según tu servidor local

  constructor(private http: HttpClient) {}

  getUsuarios() {
    return this.http.get(`${this.API_URL}/usuarios`); 
  }
}