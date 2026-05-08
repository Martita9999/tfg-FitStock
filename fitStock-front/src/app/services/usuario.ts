import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, tap } from 'rxjs';

// Interfaz para los datos básicos de un usuario
export interface Usuario {
  id: number;         // ID único del usuario
  nombre: string;     // Nombre completo
  email: string;      // Correo electrónico
  rol: string;        // Rol: 'admin', 'entrenador' o 'cliente'
}

// Interfaz para la respuesta del login
export interface LoginResponse {
  success: boolean;   // Indica si el login fue exitoso
  user?: Usuario;     // Datos del usuario (si success = true)
  error?: string;     // Mensaje de error (si success = false)
}

// Servicio principal de autenticación y gestión de usuarios
@Injectable({ providedIn: 'root' })
export class UsuarioService {
  private http = inject(HttpClient);                    // Cliente HTTP de Angular
  private API_URL = 'http://localhost:8000/api';        // URL base de la API

  // BehaviorSubject que almacena el usuario actual y emite cambios a los suscriptores
  private currentUserSubject = new BehaviorSubject<Usuario | null>(null);
  currentUser$ = this.currentUserSubject.asObservable();  // Observable público

  // Opciones HTTP: incluye credenciales (cookies de sesión)
  private httpOptions = { withCredentials: true };

  // Inicia sesión con email y contraseña
  login(email: string, password: string) {
    return this.http.post<LoginResponse>(`${this.API_URL}/login`, { email, password }, this.httpOptions).pipe(
      tap(res => {                                       // Efecto secundario al recibir respuesta
        if (res.success && res.user) {                   // Si el login es exitoso
          this.currentUserSubject.next(res.user);         // Actualiza el usuario actual
          localStorage.setItem('user', JSON.stringify(res.user));  // Persiste en localStorage
        }
      })
    );
  }

  // Registra un nuevo usuario (siempre con rol 'cliente')
  registro(nombre: string, email: string, password: string) {
    return this.http.post<{ success: boolean; error?: string }>(`${this.API_URL}/registro`, { nombre, email, password }, this.httpOptions);
  }

  // Cierra la sesión del usuario actual
  logout() {
    return this.http.post(`${this.API_URL}/logout`, {}, this.httpOptions).pipe(
      tap(() => {                                         // Efecto secundario
        this.currentUserSubject.next(null);                // Limpia el usuario actual
        localStorage.removeItem('user');                   // Elimina del localStorage
      })
    );
  }

  // Obtiene el perfil del usuario autenticado
  getPerfil() {
    return this.http.get<Usuario>(`${this.API_URL}/perfil`, this.httpOptions);
  }

  // Actualiza los datos del perfil (nombre, email, contraseña opcional)
  updatePerfil(data: { nombre: string; email: string; password?: string }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/perfil`, data, this.httpOptions);
  }

  // Obtiene la lista de todos los usuarios
  getUsuarios() {
    return this.http.get<Usuario[]>(`${this.API_URL}/usuarios`, this.httpOptions);
  }

  // Crea un nuevo usuario (solo admin)
  createUsuario(data: { nombre: string; email: string; password: string; rol: string }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/usuarios`, data, this.httpOptions);
  }

  // Elimina un usuario por su ID (solo admin)
  deleteUsuario(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/usuarios/${id}`, this.httpOptions);
  }

  // Restaura la sesión desde localStorage (al recargar la página)
  checkSession() {
    const userStr = localStorage.getItem('user');          // Obtiene usuario guardado
    if (userStr) {
      this.currentUserSubject.next(JSON.parse(userStr));   // Restaura el usuario en el BehaviorSubject
    }
  }
}
