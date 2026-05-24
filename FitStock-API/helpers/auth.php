<?php

/*
 * requireSession: verifica que el usuario tenga una sesión activa.
 * Se llama al inicio de cada controlador (excepto login/registro).
 * Si no hay sesión, responde con 401 y termina la ejecución.
 *
 * $_SESSION['usuario_id'] se establece en AuthController al hacer login
 * y se destruye en logout. La sesión se mantiene mediante cookies HTTP.
 */
function requireSession() {
    if (!isset($_SESSION['usuario_id'])) {
        jsonResponse(["error" => "No autenticado"], 401);
    }
}
