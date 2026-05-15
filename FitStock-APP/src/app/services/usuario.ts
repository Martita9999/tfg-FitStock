import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, tap } from 'rxjs';

// Representa un usuario del sistema
export interface Usuario {
  id: number;
  nombre: string;
  email: string;
  rol: string;
  forzar_cambio_password?: number; // 1 = debe cambiar contraseña en el próximo inicio de sesión
}

// Respuesta estándar del endpoint de login
export interface LoginResponse {
  success: boolean;
  user?: Usuario;
  error?: string;
}

// Servicio para gestionar autenticación y CRUD de usuarios.
// Consume los endpoints /api/login, /api/registro, /api/logout y /api/usuarios.
@Injectable({ providedIn: 'root' })
export class UsuarioService {
  private http = inject(HttpClient);
  private API_URL = 'http://localhost:8000/api';

  // BehaviorSubject que mantiene el usuario actual en memoria
  private currentUserSubject = new BehaviorSubject<Usuario | null>(null);
  // Observable al que se suscriben los componentes para conocer el usuario logueado
  currentUser$ = this.currentUserSubject.asObservable();

  private httpOptions = { withCredentials: true };

  // Autentica al usuario con email y contraseña.
  // Si el login es exitoso, almacena el usuario en el BehaviorSubject y en localStorage.
  login(email: string, password: string) {
    return this.http.post<LoginResponse>(`${this.API_URL}/login`, { email, password }, this.httpOptions).pipe(
      tap(res => {
        if (res.success && res.user) {
          this.currentUserSubject.next(res.user);
          localStorage.setItem('user', JSON.stringify(res.user));
        }
      })
    );
  }

  // Registra un nuevo usuario en el sistema
  registro(nombre: string, email: string, password: string) {
    return this.http.post<{ success: boolean; error?: string }>(`${this.API_URL}/registro`, { nombre, email, password }, this.httpOptions);
  }

  // Cierra la sesión del usuario actual.
  // Limpia el BehaviorSubject y elimina los datos de localStorage.
  logout() {
    return this.http.post(`${this.API_URL}/logout`, {}, this.httpOptions).pipe(
      tap(() => {
        this.currentUserSubject.next(null);
        localStorage.removeItem('user');
      })
    );
  }

  // Obtiene la lista completa de usuarios del sistema
  getUsuarios() {
    return this.http.get<Usuario[]>(`${this.API_URL}/usuarios`, this.httpOptions);
  }

  // Crea un nuevo usuario con nombre, email, password y rol
  createUsuario(data: { nombre: string; email: string; password: string; rol: string }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/usuarios`, data, this.httpOptions);
  }

  // Actualiza los datos de un usuario existente por su ID
  updateUsuario(id: number, data: { nombre: string; email: string; password?: string; rol?: string }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/usuarios/${id}`, data, this.httpOptions);
  }

  // Marca al usuario para que deba cambiar su contraseña en el próximo inicio de sesión
  forzarCambioPassword(id_usuario: number) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/usuarios/forzar-cambio`, { id_usuario }, this.httpOptions);
  }

  // Elimina un usuario del sistema por su ID
  deleteUsuario(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/usuarios/${id}`, this.httpOptions);
  }

  // Cambia la contraseña del usuario autenticado (requiere contraseña actual y nueva)
  cambiarPassword(old_password: string, new_password: string) {
    return this.http.put<{ success: boolean; error?: string }>(`${this.API_URL}/usuarios/cambiar-password`, { old_password, new_password }, this.httpOptions);
  }

  // Restaura la sesión desde localStorage al recargar la aplicación
  checkSession() {
    const userStr = localStorage.getItem('user');
    if (userStr) {
      this.currentUserSubject.next(JSON.parse(userStr));
    }
  }
}
