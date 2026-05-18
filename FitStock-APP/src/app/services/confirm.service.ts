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

      const bodyDiv = document.createElement('div');
      bodyDiv.className = 'confirm-body';
      const p = document.createElement('p');
      p.textContent = message;
      bodyDiv.appendChild(p);

      const footerDiv = document.createElement('div');
      footerDiv.className = 'confirm-footer';
      const cancelBtn = document.createElement('button');
      cancelBtn.className = 'btn-confirm-cancel';
      cancelBtn.textContent = 'Cancelar';
      const okBtn = document.createElement('button');
      okBtn.className = 'btn-confirm-ok';
      okBtn.textContent = 'Confirmar';
      footerDiv.appendChild(cancelBtn);
      footerDiv.appendChild(okBtn);

      card.appendChild(bodyDiv);
      card.appendChild(footerDiv);

      overlay.appendChild(card);
      document.body.appendChild(overlay);

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
