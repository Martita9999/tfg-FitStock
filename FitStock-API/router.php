<?php
/* 
 * Router principal de FitStock - Versión PRODUCCIÓN (Arsys)
 * Se encarga de:
 *   1. Configurar cabeceras CORS para el dominio real chomsky.es.
 *   2. Servir archivos estáticos si existen (Angular + API).
 *   3. Redirigir peticiones /api al endpoint interno.
 *   4. Fallback al index.html de Angular para que funcione el routing SPA.
 */

// 1. CAMBIO IMPORTANTE: Permitir acceso desde tu dominio real
header("Access-Control-Allow-Origin: https://chomsky.es");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

/*
 * Cabeceras de seguridad HTTP:
 * Son medidas defensivas para proteger a los usuarios que visitan la app.
 */
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Servir archivos estáticos (CSS, JS, imágenes, etc.)
$filePath = __DIR__ . $uri;
if ($uri !== '/' && is_file($filePath)) {
    return false;
}

// Redirigir peticiones /api al backend (case-insensitive, funciona desde subcarpeta)
if (preg_match('#/api(/|$)#i', $uri)) {
    require __DIR__ . '/api/index.php';
    return true;
}

// Fallback: servir index.html de Angular para que funcione el routing SPA
$angularIndex = __DIR__ . '/index.html';
if (is_file($angularIndex)) {
    http_response_code(200);
    header("Content-Type: text/html; charset=utf-8");
    require $angularIndex;
    return true;
}

// Último recurso: documentación
http_response_code(200);
header("Content-Type: text/html; charset=utf-8");
if (is_file(__DIR__ . '/docs/docs.php')) {
    require __DIR__ . '/docs/docs.php';
} else {
    echo "API FitStock activa. Carpeta de documentación no encontrada.";
}

return true;
