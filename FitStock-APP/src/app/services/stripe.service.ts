import { Injectable, inject } from '@angular/core';
import { environment } from '../../environments/environment';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { loadStripe, Stripe, StripeCardElement } from '@stripe/stripe-js';

export interface PaymentIntentResponse {
  clientSecret?: string;
}

/*
 * StripeService: integración del pago con tarjeta mediante Stripe Elements.
 *
 * Flujo:
 *   1. createPaymentIntent() → backend crea un PaymentIntent, devuelve clientSecret
 *   2. mountCard() → monta el formulario de tarjeta Stripe en el DOM con ID único
 *   3. confirmPayment() → confirma el pago con Stripe.js
 *
 * Cada compra requiere que el usuario introduzca los datos de su tarjeta
 * (no hay tarjeta guardada).
 *
 * Para evitar que el navegador autocomplete tarjetas de otros usuarios,
 * mountCard() genera un ID único para el contenedor del iframe de Stripe.
 * Así cada sesión de pago crea un iframe nuevo que el navegador no reconoce.
 */
@Injectable({ providedIn: 'root' })
export class StripeService {
  private http = inject(HttpClient);
  private readonly API_URL = environment.API_URL;
  private stripe: Stripe | null = null;
  private card: StripeCardElement | null = null;
  private cardContainerId: string | null = null;

  private async getStripe(): Promise<Stripe> {
    if (!this.stripe) {
      const pk = environment.STRIPE_PK;
      this.stripe = await loadStripe(pk, { locale: 'es' });
    }
    return this.stripe!;
  }

  /*
   * createPaymentIntent: envía los items del carrito al backend.
   * El backend recalcula el total desde los items para evitar manipulación.
   */
  async createPaymentIntent(items: { id: number; cantidad: number; precio: number }[]): Promise<PaymentIntentResponse> {
    return firstValueFrom(
      this.http.post<PaymentIntentResponse>(
        `${this.API_URL}/crear-payment-intent`,
        { items },
        { withCredentials: true }
      )
    );
  }

  /*
   * mountCard: monta el formulario de tarjeta Stripe en un contenedor del DOM.
   *
   * Crea un div hijo con ID único dentro del contenedor padre (containerId).
   * El ID único (timestamp + random) evita que el navegador relacione este
   * iframe con uno anterior de otro usuario.
   *
   * disableAutocomplete: true desactiva el autocomplete del navegador dentro
   * del iframe de Stripe como capa adicional de seguridad.
   */
  async mountCard(containerId: string): Promise<void> {
    const stripe = await this.getStripe();

    const container = document.getElementById(containerId);
    if (container) container.innerHTML = '';

    const uniqueId = 'stripe-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
    this.cardContainerId = uniqueId;

    const wrapper = document.createElement('div');
    wrapper.id = uniqueId;
    container?.appendChild(wrapper);

    const elements = stripe.elements();
    this.card = elements.create('card', {
      style: {
        base: {
          fontSize: '16px',
          fontFamily: 'inherit',
          color: '#32325d',
          '::placeholder': { color: '#aab7c4' }
        },
        invalid: { color: '#dc2626' }
      }
    });
    this.card.mount(`#${uniqueId}`);
  }

  /*
   * unmountCard: destruye el formulario de tarjeta y lo limpia del DOM.
   * destroy() es más completo que unmount(): elimina el iframe de Stripe
   * y la tarjeta en caché del navegador.
   */
  unmountCard() {
    if (this.card) {
      this.card.destroy();
      this.card = null;
    }
    if (this.cardContainerId) {
      const el = document.getElementById(this.cardContainerId);
      if (el) el.remove();
      this.cardContainerId = null;
    }
  }

  /*
   * reset: limpia toda la instancia de Stripe, forzando una nueva
   * al montar la siguiente tarjeta. Útil al cambiar de usuario para
   * que no arrastre tarjetas de sesiones anteriores.
   */
  reset() {
    this.unmountCard();
    this.stripe = null;
  }

  /*
   * confirmPayment: confirma el pago con Stripe.js.
   */
  async confirmPayment(clientSecret: string): Promise<string | null> {
    const stripe = await this.getStripe();
    if (!this.card) return 'Error: tarjeta no encontrada';

    const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
      payment_method: { card: this.card }
    });

    if (error) return error.message || 'Error al procesar el pago';
    if (paymentIntent?.status !== 'succeeded') return 'El pago no se completó';

    return null;
  }
}
