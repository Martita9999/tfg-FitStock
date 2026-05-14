import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, tap } from 'rxjs';

export interface Usuario {
  id: number;
  nombre: string;
  email: string;
  rol: string;
  forzar_cambio_password?: number; // 1 = debe cambiar contraseña en el próximo inicio de sesión
}

export interface LoginResponse {
  success: boolean;
  user?: Usuario;
  error?: string;
}

@Injectable({ providedIn: 'root' })
export class UsuarioService {
  private http = inject(HttpClient);
  private API_URL = 'https://chomsky.es/API/api';

  private currentUserSubject = new BehaviorSubject<Usuario | null>(null);
  currentUser$ = this.currentUserSubject.asObservable();

  private httpOptions = { withCredentials: true };

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

  registro(nombre: string, email: string, password: string) {
    return this.http.post<{ success: boolean; error?: string }>(`${this.API_URL}/registro`, { nombre, email, password }, this.httpOptions);
  }

  logout() {
    return this.http.post(`${this.API_URL}/logout`, {}, this.httpOptions).pipe(
      tap(() => {
        this.currentUserSubject.next(null);
        localStorage.removeItem('user');
      })
    );
  }

  getUsuarios() {
    return this.http.get<Usuario[]>(`${this.API_URL}/usuarios`, this.httpOptions);
  }

  createUsuario(data: { nombre: string; email: string; password: string; rol: string }) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/usuarios`, data, this.httpOptions);
  }

  updateUsuario(id: number, data: { nombre: string; email: string; password?: string; rol?: string }) {
    return this.http.put<{ success: boolean }>(`${this.API_URL}/usuarios/${id}`, data, this.httpOptions);
  }

  // Marca al usuario para que deba cambiar su contraseña en el próximo inicio de sesión
  forzarCambioPassword(id_usuario: number) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/usuarios/forzar-cambio`, { id_usuario }, this.httpOptions);
  }

  deleteUsuario(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/usuarios/${id}`, this.httpOptions);
  }

  // Cambia la contraseña del usuario autenticado (requiere contraseña actual y nueva)
  cambiarPassword(old_password: string, new_password: string) {
    return this.http.put<{ success: boolean; error?: string }>(`${this.API_URL}/usuarios/cambiar-password`, { old_password, new_password }, this.httpOptions);
  }

  checkSession() {
    const userStr = localStorage.getItem('user');
    if (userStr) {
      this.currentUserSubject.next(JSON.parse(userStr));
    }
  }
}
