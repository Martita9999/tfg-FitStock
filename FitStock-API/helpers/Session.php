<?php
/*
 * initSession():
 * Configura los parámetros de la cookie de sesión ANTES de iniciarla.
 * - httponly: la cookie no es accesible desde JavaScript (anti-XSS)
 * - samesite=Lax: no se envía desde otros sitios (anti-CSRF)
 * - secure: solo se envía por HTTPS (cuando esté disponible)
 *
 * Después llama a session_start() para iniciar la sesión.
 */
function initSession() {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    ]);
    session_start();
}
/*
 * requireSession():
 * Verifica que el usuario tenga una sesión activa.
 * Si $_SESSION['usuario_id'] no existe, responde con 401
 * (No autenticado). Se llama al inicio de cualquier endpoint protegido.
 */
function requireSession() {
    if (!isset($_SESSION['usuario_id'])) {
        jsonResponse(["error" => "No autenticado"], 401);
    }
}
