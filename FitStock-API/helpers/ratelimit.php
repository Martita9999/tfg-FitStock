<?php

/*
 * Rate limiting: protección contra fuerza bruta en login y formulario de contacto.
 *
 * Almacena los intentos en archivos JSON temporales (sistema de archivos, no BD)
 * para no ralentizar las consultas a MySQL. Cada IP tiene su propio archivo.
 *
 * Límites:
 *   - Login:    10 intentos por ventana de 15 minutos (RATE_LIMIT_MAX_ATTEMPTS)
 *   - Contacto:  5 mensajes por ventana de 15 minutos (RATE_LIMIT_CONTACTO_MAX)
 *
 * Cuando se supera el límite, se responde con HTTP 429 (Too Many Requests).
 * Al hacer login exitoso, se llama a clearRateLimit() para resetear el contador.
 */
define('RATE_LIMIT_DIR', sys_get_temp_dir() . '/fitstock_rate_limit');
define('RATE_LIMIT_MAX_ATTEMPTS', 10);
define('RATE_LIMIT_WINDOW', 900);       // 15 minutos en segundos
define('RATE_LIMIT_CONTACTO_MAX', 5);

/*
 * checkRateLimit: verifica el límite de intentos para login.
 * 1. Lee el archivo de la IP (si existe)
 * 2. Si pasaron más de 15 minutos desde el primer intento, resetea
 * 3. Incrementa el contador y guarda
 * 4. Si supera el máximo, responde con 429
 *
 * La IP se hashea con md5 para no almacenarla en texto plano.
 * El bloqueo LOCK_EX evita condiciones de carrera entre peticiones simultáneas.
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
 * clearRateLimit: elimina el archivo de rate limiting al hacer login exitoso.
 * Así el usuario no se encuentra con un límite ya acumulado en el siguiente intento.
 */
function clearRateLimit($ip) {
    $file = RATE_LIMIT_DIR . '/' . md5($ip) . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
}

/*
 * checkRateLimitContacto: igual que checkRateLimit pero para el formulario de contacto.
 * Usa un archivo diferente (prefijo 'contacto_') y un límite más restrictivo (5 mensajes).
 * Esto evita que un bot spammee el formulario de contacto.
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
