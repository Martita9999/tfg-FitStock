import { Injectable, inject } from '@angular/core';
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
  private API_URL = 'http://localhost:8000/api';
  private stripe: Stripe | null = null;
  private card: StripeCardElement | null = null;
  private cardContainerId: string | null = null;

  private async getStripe(): Promise<Stripe> {
    if (!this.stripe) {
      const pk = 'pk_test_51TaCTzF4GQq4XwDtl0QnKPpdZNtseCiiBDxTKhuyoR1XeFmj7Xdq1xYik5YN06nPvlOuwcHV5fLxWdEvksQvKcTr00igoSUaad';
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
    // @ts-ignore
    this.card = elements.create('card', {
      disableAutocomplete: true,
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
   * unmountCard: desmonta el formulario de tarjeta del DOM.
   * Elimina tanto el elemento Stripe como el contenedor único creado.
   */
  unmountCard() {
    if (this.card) {
      this.card.unmount();
      this.card = null;
    }
    if (this.cardContainerId) {
      const el = document.getElementById(this.cardContainerId);
      if (el) el.remove();
      this.cardContainerId = null;
    }
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
