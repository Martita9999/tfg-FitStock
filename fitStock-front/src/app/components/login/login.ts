import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { UsuarioService } from '../../services/usuario';

// Componente de inicio de sesión
@Component({
  selector: 'app-login',
  imports: [FormsModule],
  // Plantilla HTML inline para el formulario de login
  template: `
    <div class="login-wrapper">
      <div class="login-card">
        <div class="login-header">
          <div class="logo-icon">F</div>
          <h2>FitStock</h2>
          <p>Inicia sesión para continuar</p>
        </div>
        <div class="login-body">
          @if (error) {
            <div class="error-msg">{{ error }}</div>
          }
          <form (ngSubmit)="login()">
            <div class="field">
              <label>Email</label>
              <input type="email" [(ngModel)]="email" name="email" required placeholder="tu@email.com">
            </div>
            <div class="field">
              <label>Contraseña</label>
              <input type="password" [(ngModel)]="password" name="password" required placeholder="••••••">
            </div>
            <button type="submit" class="btn-primary">Iniciar Sesión</button>
          </form>
          <div class="register-link">
            ¿No tienes cuenta? <a (click)="goToRegistro()">Regístrate</a>
          </div>
        </div>
      </div>
    </div>
  `,
  // Estilos del componente
  styles: [`
    .login-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--blue-dark) 0%, #0f172a 100%); padding: 20px; }
    .login-card { background: var(--card); border-radius: 20px; width: 100%; max-width: 420px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
    .login-header { background: linear-gradient(135deg, var(--blue-dark), var(--blue)); padding: 40px 30px 30px; text-align: center; color: white; }
    .logo-icon { width: 60px; height: 60px; background: var(--orange); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; margin: 0 auto 15px; }
    .login-header h2 { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
    .login-header p { font-size: 14px; opacity: 0.8; }
    .login-body { padding: 30px; }
    .error-msg { background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; border: 1px solid #fecaca; }
    .field { margin-bottom: 20px; }
    .field label { display: block; font-size: 14px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; }
    .field input { width: 100%; padding: 12px 16px; border: 2px solid var(--border); border-radius: 10px; font-size: 15px; transition: border-color 0.2s; outline: none; font-family: inherit; }
    .field input:focus { border-color: var(--orange); }
    .btn-primary { width: 100%; padding: 14px; background: linear-gradient(135deg, var(--orange), var(--orange-dark)); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.15s, box-shadow 0.15s; font-family: inherit; }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(249, 115, 22, 0.4); }
    .btn-primary:active { transform: translateY(0); }
    .register-link { text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary); }
    a { color: var(--orange); cursor: pointer; font-weight: 600; text-decoration: none; }
    a:hover { text-decoration: underline; }
  `]
})
export class LoginComponent {
  private usuarioService = inject(UsuarioService);    // Servicio de autenticación
  private router = inject(Router);                    // Router para navegación

  email = '';       // Email del formulario (two-way binding)
  password = '';    // Contraseña del formulario (two-way binding)
  error = '';       // Mensaje de error a mostrar

  // Intenta iniciar sesión con los datos del formulario
  login() {
    this.error = '';
    this.usuarioService.login(this.email, this.password).subscribe({
      next: (res) => {
        if (res.success) {
          this.router.navigate(['/admin/inventario']);   // Redirige al panel admin
        } else {
          this.error = res.error || 'Error desconocido';
        }
      },
      error: () => {
        this.error = 'Error de conexión';
      }
    });
  }

  // Navega a la página de registro
  goToRegistro() {
    this.router.navigate(['/registro']);
  }
}
