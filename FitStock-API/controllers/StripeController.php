<?php

/*
 * StripeController: controlador para la integración de pagos con Stripe.
 *
 * Endpoint único:
 *   POST /api/crear-payment-intent -> crea un PaymentIntent y devuelve el client_secret
 *
 * Flujo de pago (siempre con formulario de tarjeta):
 *   1. Frontend -> crear-payment-intent -> backend devuelve clientSecret
 *   2. Frontend muestra formulario de tarjeta, usuario introduce los datos
 *   3. Frontend confirma el pago con Stripe.js
 *   4. Frontend -> finalizarCompra()
 *
 * NOTA: No usamos stripe-php (no hay Composer). Llamamos a la API REST con file_get_contents.
 */
class StripeController {
    public static function handle($method, $path, $data) {
        requireSession();

        if ($method !== 'POST') {
            jsonResponse(["error" => "Método no permitido"], 405);
        }

        $resource = $path[1] ?? '';
        switch ($resource) {
            case 'crear-payment-intent':
                self::crearPaymentIntent($data);
                break;
            default:
                jsonResponse(["error" => "Acción no válida"], 404);
        }
    }

    /*
     * crearPaymentIntent: crea un PaymentIntent en Stripe y devuelve el client_secret.
     *
     * Recibe los items del carrito desde el frontend y recalcula el total en el servidor.
     * Así evitamos que el usuario pueda modificar el total desde el navegador (DevTools).
     *
     * Los items se guardan en $_SESSION['carrito_pendiente'] para que finalizarCompra()
     * pueda usarlos después sin depender del carrito del frontend.
     */
    private static function crearPaymentIntent($data) {
        try {
            $items = $data['items'] ?? [];
            if (empty($items) || !is_array($items)) {
                jsonResponse(["error" => "Carrito vacío"], 400);
            }

            $totalCalculado = 0;
            $itemsLimpios = [];
            foreach ($items as $item) {
                $id = intval($item['id'] ?? 0);
                $cantidad = intval($item['cantidad'] ?? 0);
                $precio = floatval($item['precio'] ?? 0);
                if ($id <= 0 || $cantidad <= 0 || $precio <= 0) {
                    jsonResponse(["error" => "Item inválido en el carrito"], 400);
                }
                $totalCalculado += $precio * $cantidad;
                $itemsLimpios[] = ['id' => $id, 'cantidad' => $cantidad, 'precio' => $precio];
            }

            if ($totalCalculado <= 0) {
                jsonResponse(["error" => "Total inválido"], 400);
            }

            $_SESSION['carrito_pendiente'] = $itemsLimpios;

            $stripeSecretKey = self::getStripeKey();
            $usuarioId = $_SESSION['usuario_id'];

            $postFields = http_build_query([
                'amount' => round($totalCalculado * 100),
                'currency' => 'eur',
                'metadata[id_usuario]' => $usuarioId
            ]);

            $resp = self::stripePost('https://api.stripe.com/v1/payment_intents', $postFields, $stripeSecretKey);
            $intent = json_decode($resp, true);
            jsonResponse(['clientSecret' => $intent['client_secret']]);
        } catch (Exception $e) {
            error_log("StripeController fatal: " . $e->getMessage());
            jsonResponse(["error" => "Error interno: " . $e->getMessage()], 500);
        }
    }

    private static function getStripeKey(): string {
        $key = getenv('STRIPE_SECRET_KEY') ?: '';
        if (!$key || str_starts_with($key, 'sk_test_XXXXXXXXXXXXXXXX')) {
            jsonResponse(["error" => "Stripe no configurado"], 500);
        }
        return $key;
    }

    private static function stripePost(string $url, string $postFields, string $secretKey): string {
        if (!function_exists('curl_init')) {
            jsonResponse(["error" => "cURL no está instalado en el servidor"], 500);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . base64_encode("$secretKey:"),
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $response === '') {
            jsonResponse(["error" => "Error de conexión con Stripe", "debug" => $curlError ?: "sin respuesta"], 500);
        }

        if ($httpCode !== 200) {
            $err = json_decode($response, true);
            $msg = $err['error']['message'] ?? 'Error desconocido';
            error_log("Stripe error: $msg");
            jsonResponse(["error" => "Stripe: $msg"], 500);
        }

        return $response;
    }
}
