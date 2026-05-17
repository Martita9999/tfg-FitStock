import { Injectable } from '@angular/core';

export interface Toast {
  message: string;
  type: 'success' | 'error' | 'info';
}

/*
 * ToastService: servicio global de notificaciones toast.
 * Crea elementos DOM directamente sin librerías externas.
 * Busca contenedor con id="toast-container" o lo crea en el body.
 * Los toasts se autodestruyen tras 4 segundos.
 */
@Injectable({ providedIn: 'root' })
export class ToastService {
  private container: HTMLElement | null = null;

  /* getContainer: obtiene o crea el contenedor de toasts en el DOM */
  private getContainer(): HTMLElement {
    if (!this.container) {
      this.container = document.getElementById('toast-container');           // Buscamos contenedor existente
      if (!this.container) {
        this.container = document.createElement('div');                      // Creamos uno nuevo
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);                           // Lo añadimos al body
      }
    }
    return this.container;
  }

  /* show: crea toast con icono, lo muestra y programa su auto-eliminación */
  show(message: string, type: Toast['type'] = 'info', duration = 4000) {
    const el = document.createElement('div');                                // Creamos elemento toast
    el.className = `toast toast-${type}`;
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    el.innerHTML = `<span>${icons[type]}</span><span>${message}</span>`;
    this.getContainer().appendChild(el);                                     // Lo añadimos al contenedor
    setTimeout(() => el.remove(), duration);                                 // Auto-eliminación tras duration ms
  }

  success(message: string) { this.show(message, 'success'); }
  error(message: string) { this.show(message, 'error'); }
  info(message: string) { this.show(message, 'info'); }
}
