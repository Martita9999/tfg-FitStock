<?php

/*
 * jsonResponse: envía una respuesta JSON al cliente y termina la ejecución.
 * Parámetros:
 *   $data  -> array asociativo que se convertirá a JSON
 *   $code  -> código HTTP (200 por defecto)
 *
 * Todas las respuestas de la API pasan por aquí, lo que centraliza
 * el formateo y evita tener http_response_code + echo + exit en cada controlador.
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/*
 * getJsonInput: obtiene los datos del cuerpo de la petición.
 * Prioriza JSON (application/json), pero si la petición llega como
 * multipart/form-data (ej: subida de imágenes), usa $_POST como fallback.
 *
 * PHP lee php://input automáticamente; si no hay JSON válido,
 * json_decode devuelve null y ?? $_POST toma el relevo.
 */
function getJsonInput() {
    return json_decode(file_get_contents("php://input"), true) ?? $_POST;
}
