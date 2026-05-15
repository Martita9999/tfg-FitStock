<?php
/* 
 * Router principal de FitStock.
 * Se encarga de:
 *   1. Configurar cabeceras CORS con origen dinámico para el frontend Angular.
 *   2. Añadir cabeceras de seguridad HTTP (CSP, HSTS, XFO, etc.).
 *   3. Servir archivos estáticos (CSS, JS, imágenes) si existen en el documento raíz.
 *   4. Redirigir peticiones /api al endpoint interno de la API.
 *   5. Como fallback, mostrar la documentación HTML del proyecto.
 *
 * Archivo modificado: router.php
 * Líneas modificadas: 12-25 (CORS dinámico + cabeceras de seguridad)
 * Cambios:
 *   - Origen CORS fijo        →  Origen dinámico basado en variable
 *   - Sin cabeceras seguridad  →  CSP, HSTS, XFO, X-Content-Type-Options
 */

// ─────────────────────────────────────────────────────────────
// MEJORA: Origen CORS dinámico
// Permite cambiar el frontend permitido sin editar el código.
// Por defecto usa http://localhost:4200 (Angular en desarrollo).
// Para producción, definir FRONTEND_URL en el entorno o .htaccess.
// ─────────────────────────────────────────────────────────────
$allowedOrigin = getenv('FRONTEND_URL') ?: 'http://localhost:4200';

// ─────────────────────────────────────────────────────────────
// MEJORA: Cabeceras CORS dinámicas
// ─────────────────────────────────────────────────────────────
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// ─────────────────────────────────────────────────────────────
// MEJORA: Cabeceras de seguridad HTTP
// ─────────────────────────────────────────────────────────────
// Content-Security-Policy: Restringe qué recursos puede cargar el navegador
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'");
// X-Content-Type-Options: Evita que el navegador interprete MIMEs distintos al declarado
header("X-Content-Type-Options: nosniff");
// X-Frame-Options: Evita que la página se cargue en iframes (clickjacking)
header("X-Frame-Options: DENY");
// Referrer-Policy: Controla cuánta información se envía en la cabecera Referer
header("Referrer-Policy: strict-origin-when-cross-origin");
// Strict-Transport-Security: Obliga a usar HTTPS (solo cuando se sirva con HTTPS)
// 'max-age=31536000' = 1 año. 'includeSubDomains' aplica a todos los subdominios.
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

// ─────────────────────────────────────────────────────────────
// Preflight CORS: Si es OPTIONS, responder 200 y salir
// ─────────────────────────────────────────────────────────────
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
