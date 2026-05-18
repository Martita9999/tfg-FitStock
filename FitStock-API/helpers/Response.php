<?php
/*
 * jsonResponse($data, $code):
 * Envía una respuesta JSON con el código HTTP indicado.
 * Todas las respuestas de la API pasan por aquí, lo que asegura
 * que siempre devolvemos JSON con http_response_code + exit.
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
/*
 * getJsonInput():
 * Lee el cuerpo de la petición HTTP (JSON) y lo convierte en array.
 * Si no se puede parsear como JSON, cae en $_POST como fallback
 * por si alguien envía form-data tradicional.
 */
function getJsonInput() {
    return json_decode(file_get_contents("php://input"), true) ?? $_POST;
}
