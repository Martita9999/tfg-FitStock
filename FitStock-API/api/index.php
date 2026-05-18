<?php
/*
 * Enrutador interno de la API REST de FitStock.
 *
 * Este archivo recibe todas las peticiones que empiezan por /api
 * (derivadas desde router.php) y las distribuye al controlador
 * correspondiente según el recurso solicitado: login, usuarios,
 * materiales, préstamos, etc.
 *
 * Es un enrutador ligero: configura CORS y sesión, carga las
 * dependencias, parsea la URL y delega en el controlador adecuado.
 * Cada controlador se encarga de su propia lógica CRUD y control
 * de acceso basado en roles.
 */

require_once __DIR__ . "/../conexion.php";

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

require_once __DIR__ . "/../helpers/Session.php";
initSession();

require_once __DIR__ . "/../helpers/Response.php";
require_once __DIR__ . "/../helpers/RateLimiter.php";

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
$resource = $path[1] ?? '';

$controllerMap = [
    'login'       => 'AuthController',
    'registro'    => 'AuthController',
    'logout'      => 'AuthController',
    'usuarios'    => 'UsuarioController',
    'materiales'  => 'MaterialController',
    'prestamos'   => 'PrestamoController',
    'productos'   => 'ProductoController',
    'compras'     => 'CompraController',
    'incidencias' => 'IncidenciaController',
    'resumen'     => 'ResumenController',
    'contacto'    => 'ContactoController',
];

if (isset($controllerMap[$resource])) {
    require_once __DIR__ . "/../controllers/{$controllerMap[$resource]}.php";
    $class = $controllerMap[$resource];
    $controller = new $class();
    $controller->handle($method, $path);
} else {
    jsonResponse(["error" => "Recurso no encontrado"], 404);
}
