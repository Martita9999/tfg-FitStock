<?php

require_once __DIR__ . "/../conexion.php";
require_once __DIR__ . "/../helpers/json.php";
require_once __DIR__ . "/../helpers/auth.php";
require_once __DIR__ . "/../helpers/ratelimit.php";

$allowedOrigin = getenv('FRONTEND_URL') ?: 'http://localhost:4200';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
]);

session_start();

require_once __DIR__ . "/../models/Usuario.php";
require_once __DIR__ . "/../models/Material.php";
require_once __DIR__ . "/../models/Prestamo.php";
require_once __DIR__ . "/../models/Incidencia.php";
require_once __DIR__ . "/../models/Producto.php";
require_once __DIR__ . "/../models/Compra.php";
require_once __DIR__ . "/../vendor/PHPMailer/src/Exception.php";
require_once __DIR__ . "/../vendor/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/../vendor/PHPMailer/src/SMTP.php";

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = explode('/', trim($uri, '/'));
$action = $path[0] ?? '';
$resource = $path[1] ?? '';

/*
 * Mapa de rutas: cada recurso de la URL se asocia a un controlador.
 * StripeController maneja crear-payment-intent (crea PaymentIntent).
 */
$routes = [
    'login'                  => 'AuthController',
    'registro'               => 'AuthController',
    'logout'                 => 'AuthController',
    'usuarios'               => 'UsuarioController',
    'materiales'             => 'MaterialController',
    'prestamos'              => 'PrestamoController',
    'productos'              => 'ProductoController',
    'compras'                => 'CompraController',
    'incidencias'            => 'IncidenciaController',
    'resumen'                => 'ResumenController',
    'contacto'               => 'ContactoController',
    'crear-payment-intent'   => 'StripeController',    // Crea PaymentIntent y devuelve clientSecret
];

switch ($action) {
    case 'api':
        if (!isset($routes[$resource])) {
            jsonResponse(["error" => "Recurso no encontrado"], 404);
        }
        $controllerName = $routes[$resource];
        require_once __DIR__ . "/../controllers/{$controllerName}.php";
        $controllerName::handle($method, $path, getJsonInput());
        break;
    default:
        jsonResponse(["error" => "Endpoint no encontrado"], 404);
}
