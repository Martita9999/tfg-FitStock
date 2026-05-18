<?php

require_once __DIR__ . '/conexion.php';

header("Access-Control-Allow-Origin: https://chomsky.es");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

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

$normalizedUri = preg_replace('#^/API#i', '', $uri) ?: '/';
$filePath = __DIR__ . $normalizedUri;
if ($uri !== '/' && is_file($filePath)) {
    return false;
}

if (preg_match('#/api(/|$)#i', $uri)) {
    require __DIR__ . '/api/index.php';
    return true;
}

$angularIndex = __DIR__ . '/index.html';
if (is_file($angularIndex)) {
    http_response_code(200);
    header("Content-Type: text/html; charset=utf-8");
    require $angularIndex;
    return true;
}

http_response_code(200);
header("Content-Type: text/html; charset=utf-8");
if (is_file(__DIR__ . '/docs/docs.php')) {
    require __DIR__ . '/docs/docs.php';
} else {
    echo "API FitStock activa.";
}

return true;
