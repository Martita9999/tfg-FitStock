<?php
// Cabeceras CORS para permitir peticiones desde el frontend Angular
header("Access-Control-Allow-Origin: http://localhost:4200");          // Origen permitido (frontend)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS"); // Métodos HTTP permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization");    // Cabeceras permitidas
header("Access-Control-Allow-Credentials: true");                       // Permite el envío de cookies de sesión

// Si la petición es OPTIONS (preflight CORS), responder con 200 y salir
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Si la URI empieza por /api, delegar al router de la API
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);   // Obtiene la ruta de la URI
if (preg_match('#^/api#', $uri)) {                          // Comprueba si la ruta comienza con /api
    require __DIR__ . '/api/index.php';                    // Carga el manejador de la API
    return true;                                            // Indica que la petición fue manejada
}

// Si no es una ruta /api, responder con un mensaje informativo
http_response_code(200);                                                     // Código 200 OK
header("Content-Type: application/json");                                    // Tipo de contenido JSON
echo json_encode(["message" => "FitStock API - usa http://localhost:4200 para el frontend"]);  // Mensaje de bienvenida
return true;                                                                 // Finaliza la ejecución del router
