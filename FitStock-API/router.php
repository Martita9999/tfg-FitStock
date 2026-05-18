<?php
/*
 * Router principal de FitStock.
 * Este archivo es el punto de entrada de toda la aplicación.
 * Se encarga de manejar CORS, cabeceras de seguridad, servir
 * archivos estáticos, redirigir peticiones /api a la API REST,
 * y mostrar la documentación como fallback.
 * 
 * Lo explico paso a paso para que veáis cómo funciona la entrada
 * de la aplicación y por qué es importante cada pieza.
 */

/*
 * Variable $allowedOrigin: guarda el origen que puede hacer peticiones
 * a nuestra API. Por defecto usa localhost:4200 (Angular en desarrollo),
 * pero si definimos la variable de entorno FRONTEND_URL, usamos esa.
 * 
 * Esto es clave para producción: así el frontend desplegado en Vercel
 * o Netlify puede comunicarse con el backend sin problemas de CORS,
 * y además no hace falta tocar el código cuando cambiamos de dominio.
 */

/*
 * Cargamos las variables del .env antes de leer FRONTEND_URL,
 * para que getenv() encuentre el valor definido en el archivo.
 */
require_once __DIR__ . '/conexion.php';

$allowedOrigin = getenv('FRONTEND_URL') ?: 'http://localhost:4200';

/*
 * Configuración de CORS (Cross-Origin Resource Sharing):
 * Le decimos al navegador que permita peticiones desde nuestro frontend
 * Angular, con cualquier método HTTP que necesitemos (GET, POST, PUT, DELETE),
 * y las cabeceras personalizadas como Authorization.
 * 
 * Access-Control-Allow-Credentials: true es importante porque usamos
 * sesiones PHP con cookies, y sin esto el navegador no las enviaría.
 */
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
/*
 * Cabeceras de seguridad HTTP:
 * Son medidas defensivas para proteger a los usuarios que visitan la app.
 * 
 * Content-Security-Policy (CSP): le dice al navegador qué recursos puede
 * cargar. 'self' significa solo del mismo origen. Así evitamos que alguien
 * inyecte scripts maliciosos de otros sitios (ataque XSS).
 * 
 * X-Content-Type-Options: evita que el navegador "adivine" el tipo MIME.
 * Si subimos un archivo .txt con contenido HTML, no lo interpretará como HTML.
 * 
 * X-Frame-Options: impide que nuestra web se cargue dentro de un iframe
 * en otro dominio. Esto evita ataques de clickjacking.
 * 
 * Referrer-Policy: controla qué información mandamos en la cabecera Referer
 * cuando el usuario hace clic en un enlace.
 * 
 * Strict-Transport-Security (HSTS): solo se activa si la conexión ya es HTTPS.
 * Obliga al navegador a usar siempre HTTPS durante 1 año.
 */
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

/*
 * Preflight CORS (peticiones OPTIONS):
 * Cuando el frontend hace una petición con métodos "no simples" (PUT, DELETE)
 * o con cabeceras personalizadas, el navegador primero manda una petición
 * OPTIONS para preguntar si el servidor acepta ese tipo de petición.
 * 
 * Aquí respondemos 200 y salimos sin ejecutar más código, porque solo
 * necesitamos que el navegador vea las cabeceras CORS que ya configuramos.
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/* Obtenemos la ruta de la URL sin los parámetros de query string */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/*
 * Servir archivos estáticos:
 * Si la ruta no es la raíz y el archivo existe físicamente en el disco
 * (por ejemplo, /docs/docs.css), devolvemos false para que PHP deje
 * que el servidor web (Apache/Nginx) sirva el archivo directamente.
 * 
 * Esto es más eficiente que leer y servir el archivo con PHP,
 * porque el servidor web está optimizado para servir estáticos.
 */
$filePath = __DIR__ . $uri;
if ($uri !== '/' && is_file($filePath)) {
    return false;
}

/*
 * Ruteo de la API:
 * Si la URI empieza por "/api", delegamos la petición al enrutador
 * interno api/index.php, que se encarga de cada endpoint (login,
 * usuarios, materiales, etc.).
 * 
 * Usamos require (no require_once) porque cada petición solo pasa
 * por aquí una vez y es ligeramente más rápido.
 */
if (preg_match('#^/api#', $uri)) {
    require __DIR__ . '/api/index.php';
    return true;
}

/*
 * Ruta por defecto (fallback):
 * Si no es un archivo estático ni una ruta /api, mostramos la
 * documentación HTML de la API. Así, al abrir la raíz del proyecto
 * en el navegador, vemos la documentación sin necesidad de más pasos.
 */
http_response_code(200);
header("Content-Type: text/html; charset=utf-8");
require __DIR__ . '/docs/docs.php';
return true;
