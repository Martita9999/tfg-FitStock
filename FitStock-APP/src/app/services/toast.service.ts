import { Injectable } from '@angular/core';

export interface Toast {
  message: string;
  type: 'success' | 'error' | 'info';
}

// Servicio global para mostrar notificaciones tipo toast
// (mensajes emergentes autodescartables) en la interfaz
@Injectable({ providedIn: 'root' })
export class ToastService {
  private container: HTMLElement | null = null;

  private getContainer(): HTMLElement {
    if (!this.container) {
      this.container = document.getElementById('toast-container');
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
      }
    }
    return this.container;
  }

  // Muestra un toast con el mensaje, tipo y duración indicados.
  // Crea el elemento DOM, lo añade al contenedor y lo elimina
  // automáticamente tras `duration` milisegundos (auto-dismiss).
  show(message: string, type: Toast['type'] = 'info', duration = 4000) {
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    el.innerHTML = `<span>${icons[type]}</span><span>${message}</span>`;
    this.getContainer().appendChild(el);
    setTimeout(() => el.remove(), duration);
  }

  // Atajo para mostrar un toast de tipo éxito
  success(message: string) { this.show(message, 'success'); }
  // Atajo para mostrar un toast de tipo error
  error(message: string) { this.show(message, 'error'); }
  // Atajo para mostrar un toast de tipo informativo
  info(message: string) { this.show(message, 'info'); }
}
