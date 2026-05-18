import { Injectable, inject } from '@angular/core';
import { environment } from '../../environments/environment';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, tap } from 'rxjs';

export interface Usuario {
  id: number;
  nombre: string;
  email: string;
  rol: string;
  forzar_cambio_password?: number;
}

export interface LoginResponse {
  success: boolean;
  user?: Usuario;
  error?: string;
}

/*
 * UsuarioService: servicio central de autenticación y CRUD de usuarios.
 * Usa un BehaviorSubject para mantener el estado del usuario logueado.
 * Los componentes se suscriben a currentUser$ para reaccionar a cambios.
 * Persiste el usuario en localStorage para que sobreviva a recargas.
 */
@Injectable({ providedIn: 'root' })
export class UsuarioService {
  private http = inject(HttpClient);
  private readonly API_URL = environment.API_URL;

  currentUserSubject = new BehaviorSubject<Usuario | null>(null);  // Estado del usuario en memoria
  currentUser$ = this.currentUserSubject.asObservable();                    // Observable público para componentes

  private httpOptions = { withCredentials: true };                          // Envía cookies de sesión

  /* login: autentica al usuario. pipe(tap) guarda en memoria y localStorage sin modificar la respuesta */
  login(email: string, password: string) {
    return this.http.post<LoginResponse>(`${this.API_URL}/login`, { email, password }, this.httpOptions).pipe(
      tap(res => {
        if (res.success && res.user) {
          this.currentUserSubject.next(res.user);                            // Guardamos en memoria
          localStorage.setItem('user', JSON.stringify(res.user));            // Persistimos en localStorage
        }
      })
    );
  }

  /* registro: crea cuenta nueva con rol 'cliente' */
  registro(nombre: string, email: string, password: string) {
    return this.http.post<{ success: boolean; error?: string }>(`${this.API_URL}/registro`, { nombre, email, password }, this.httpOptions);
  }

  /* logout: destruye sesión en backend, limpia memoria y localStorage */
  logout() {
    return this.http.post(`${this.API_URL}/logout`, {}, this.httpOptions).pipe(
      tap(() => {
        this.currentUserSubject.next(null);                                  // Limpiamos memoria
        localStorage.removeItem('user');                                     // Borramos localStorage
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

  /* forzarCambioPassword: marca usuario para que cambie su contraseña en el próximo login */
  forzarCambioPassword(id_usuario: number) {
    return this.http.post<{ success: boolean }>(`${this.API_URL}/usuarios/forzar-cambio`, { id_usuario }, this.httpOptions);
  }

  deleteUsuario(id: number) {
    return this.http.delete<{ success: boolean }>(`${this.API_URL}/usuarios/${id}`, this.httpOptions);
  }

  /* cambiarPassword: requiere contraseña actual para verificar identidad */
  cambiarPassword(old_password: string, new_password: string) {
    return this.http.put<{ success: boolean; error?: string }>(`${this.API_URL}/usuarios/cambiar-password`, { old_password, new_password }, this.httpOptions);
  }

  /* checkSession: restaura sesión desde localStorage al recargar la página */
  checkSession() {
    const userStr = localStorage.getItem('user');
    if (userStr) {
      try {
        this.currentUserSubject.next(JSON.parse(userStr));
      } catch {
        localStorage.removeItem('user');
      }
    }
  }
}
