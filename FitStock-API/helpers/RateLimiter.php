<?php
/*
 * Rate Limiter - Protección contra fuerza bruta y spam.
 *
 * Guarda en archivos temporales del sistema el número de intentos
 * por IP. Si se supera el límite en una ventana de tiempo, se
 * bloquea temporalmente la IP.
 *
 * Hay dos sistemas independientes:
 * - Login: máximo 10 intentos en 15 minutos
 * - Contacto: máximo 5 envíos en 15 minutos 
 */

define('RATE_LIMIT_DIR', sys_get_temp_dir() . '/fitstock_rate_limit');
define('RATE_LIMIT_MAX_ATTEMPTS', 10);
define('RATE_LIMIT_WINDOW', 900);

/*
 * checkRateLimit($ip):
 * Verifica los intentos de login de una IP.
 * Cada IP tiene su archivo propio (identificado por hash MD5) que
 * guarda el contador y la marca de tiempo. Si se superan 10 intentos
 * en 15 minutos, responde con HTTP 429 (Too Many Requests).
 * Usa LOCK_EX para evitar condiciones de carrera entre peticiones
 * simultáneas de la misma IP.
 */
function checkRateLimit($ip) {
    if (!is_dir(RATE_LIMIT_DIR)) {
        @mkdir(RATE_LIMIT_DIR, 0700, true);
    }
    $file = RATE_LIMIT_DIR . '/' . md5($ip) . '.json';
    $now = time();
    $data = ['attempts' => 0, 'first_attempt' => $now];

    if (is_file($file)) {
        $saved = json_decode(file_get_contents($file), true) ?? $data;
        if ($now - $saved['first_attempt'] > RATE_LIMIT_WINDOW) {
            $saved = ['attempts' => 0, 'first_attempt' => $now];
        }
        $data = $saved;
    }

    $data['attempts']++;
    file_put_contents($file, json_encode($data), LOCK_EX);

    if ($data['attempts'] > RATE_LIMIT_MAX_ATTEMPTS) {
        jsonResponse(["error" => "Demasiados intentos. Intenta de nuevo en 15 minutos."], 429);
    }
}

/*
 * clearRateLimit($ip):
 * Cuando el inicio de sesión es exitoso, elimina el archivo de rate
 * limiting para esa IP. Así el contador se reinicia y el usuario
 * no se bloquea injustamente tras un login correcto.
 */
function clearRateLimit($ip) {
    $file = RATE_LIMIT_DIR . '/' . md5($ip) . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
}

define('RATE_LIMIT_CONTACTO_MAX', 5);

/*
 * checkRateLimitContacto($ip):
 * Rate limiting específico para el formulario de contacto.
 * Es más restrictivo (5 intentos por ventana) para evitar que
 * el formulario se use para enviar spam masivo.
 * Los archivos tienen prefijo 'contacto_' para no mezclar
 * los intentos con los de login.
 */
function checkRateLimitContacto($ip) {
    if (!is_dir(RATE_LIMIT_DIR)) {
        @mkdir(RATE_LIMIT_DIR, 0700, true);
    }
    $file = RATE_LIMIT_DIR . '/contacto_' . md5($ip) . '.json';
    $now = time();
    $data = ['attempts' => 0, 'first_attempt' => $now];

    if (is_file($file)) {
        $saved = json_decode(file_get_contents($file), true) ?? $data;
        if ($now - $saved['first_attempt'] > RATE_LIMIT_WINDOW) {
            $saved = ['attempts' => 0, 'first_attempt' => $now];
        }
        $data = $saved;
    }

    $data['attempts']++;
    file_put_contents($file, json_encode($data), LOCK_EX);

    if ($data['attempts'] > RATE_LIMIT_CONTACTO_MAX) {
        jsonResponse(["error" => "Has superado el límite de mensajes. Intenta de nuevo en 15 minutos."], 429);
    }
}
