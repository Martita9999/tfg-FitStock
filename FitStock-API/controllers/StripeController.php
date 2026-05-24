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
        $items = $data['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            jsonResponse(["error" => "Carrito vacío"], 400);
        }

        /*
         * Recalcular total desde los items recibidos.
         * Cada item debe tener id, cantidad y precio.
         * Si algún precio es inválido, rechazamos la petición.
         */
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

        /*
         * Guardar los items en sesión para que finalizarCompra() los use
         * en lugar del carrito del frontend (el backend es fuente de verdad).
         */
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
    }

    private static function getStripeKey(): string {
        $key = getenv('STRIPE_SECRET_KEY') ?: '';
        if (!$key || str_starts_with($key, 'sk_test_XXXXXXXXXXXXXXXX')) {
            jsonResponse(["error" => "Stripe no configurado"], 500);
        }
        return $key;
    }

    private static function stripePost(string $url, string $postFields, string $secretKey): string {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Basic " . base64_encode("$secretKey:") . "\r\n" .
                            "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postFields,
                'ignore_errors' => true
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false || !$http_response_header) {
            jsonResponse(["error" => "Error al comunicar con Stripe"], 500);
        }

        preg_match('#HTTP/\d\.\d (\d+)#', $http_response_header[0], $m);
        $httpCode = intval($m[1] ?? 500);

        if ($httpCode !== 200) {
            $err = json_decode($response, true);
            $msg = $err['error']['message'] ?? 'Error desconocido';
            $code = $err['error']['code'] ?? '';
            error_log("Stripe error [$code]: $msg");
            jsonResponse(["error" => "Error al procesar el pago"], 500);
        }

        return $response;
    }
}
