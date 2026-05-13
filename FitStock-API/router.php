<?php
/* 
 * Router principal de FitStock.
 * Se encarga de:
 *   1. Configurar cabeceras CORS para el frontend Angular.
 *   2. Servir archivos estáticos (CSS, JS, imágenes) si existen en el documento raíz.
 *   3. Redirigir peticiones /api al endpoint interno de la API.
 *   4. Como fallback, mostrar la documentación HTML del proyecto.
 */
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Servir archivos estáticos (CSS, JS, imágenes) si existen fisicamente en el disco.
// Si el archivo existe, PHP devuelve false para que el servidor web lo sirva directamente.
$filePath = __DIR__ . $uri;
if ($uri !== '/' && is_file($filePath)) {
    return false;
}

// Ruteo de la API:
// Si la URI comienza con "/api", se delega el manejo a api/index.php.
// Este archivo contiene el enrutador interno que resuelve cada endpoint.
if (preg_match('#^/api#', $uri)) {
    require __DIR__ . '/api/index.php';
    return true;
}

// Ruta por defecto (fallback):
// Si no es un archivo estático ni una ruta /api, se muestra la documentación HTML.
// Esto permite acceder a la documentación simplemente abriendo la raíz del proyecto.
http_response_code(200);
header("Content-Type: text/html; charset=utf-8");
require __DIR__ . '/docs/docs.php';
return true;
