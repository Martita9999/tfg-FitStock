import { Injectable } from '@angular/core';

/*
 * ConfirmService: servicio global de diálogos de confirmación.
 * Reemplaza los confirm() nativos del navegador por un modal propio.
 *
 * Crea elementos DOM directamente (mismo patrón que ToastService).
 * Devuelve una Promise<boolean> que se resuelve al pulsar Confirmar o Cancelar.
 *
 * Uso:
 *   const ok = await this.confirmService.confirm('¿Borrar este producto?');
 *   if (!ok) return;
 */
@Injectable({ providedIn: 'root' })
export class ConfirmService {
  /*
   * confirm: muestra un modal de confirmación y devuelve true/false.
   * Crea el overlay y la card con el mensaje y dos botones.
   * Al pulsar un botón, elimina el modal del DOM y resuelve la promesa.
   */
  confirm(message: string): Promise<boolean> {
    return new Promise(resolve => {
      const overlay = document.createElement('div');
      overlay.className = 'confirm-overlay';

      const card = document.createElement('div');
      card.className = 'confirm-card';

      card.innerHTML = `
        <div class="confirm-body">
          <p>${message}</p>
        </div>
        <div class="confirm-footer">
          <button class="btn-confirm-cancel">Cancelar</button>
          <button class="btn-confirm-ok">Confirmar</button>
        </div>
      `;

      overlay.appendChild(card);
      document.body.appendChild(overlay);

      const cancelBtn = card.querySelector('.btn-confirm-cancel') as HTMLButtonElement;
      const okBtn = card.querySelector('.btn-confirm-ok') as HTMLButtonElement;

      const cleanup = (result: boolean) => {
        overlay.remove();
        resolve(result);
      };

      cancelBtn.addEventListener('click', () => cleanup(false));
      okBtn.addEventListener('click', () => cleanup(true));
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) cleanup(false);
      });
    });
  }
}
