<?php
/*
 * Enrutador interno de la API REST de FitStock.
 * 
 * Este archivo recibe todas las peticiones que empiezan por /api
 * (derivadas desde router.php) y las distribuye según el recurso
 * solicitado: login, usuarios, materiales, préstamos, etc.
 * 
 * Es el corazón de la API: aquí se implementa todo el CRUD con
 * control de roles y las medidas de seguridad como rate limiting,
 * validación de entrada, y política de contraseñas.
 * 
 * Os explico cada sección para que entendáis cómo funciona cada endpoint.
 */

/*
 * Cargamos las variables del .env antes de leer FRONTEND_URL,
 * para que getenv() encuentre el valor definido en el archivo.
 */
require_once __DIR__ . "/../conexion.php";

/*
 * Origen CORS dinámico:
 * Usamos la variable de entorno FRONTEND_URL para saber desde dónde
 * puede llegar el frontend. En desarrollo es localhost:4200 (Angular),
 * en producción será la URL donde esté desplegado el frontend.
 * 
 * Así no hace falta cambiar el código cuando pase a producción:
 * solo configurar la variable de entorno en el servidor.
 */
$allowedOrigin = getenv('FRONTEND_URL') ?: 'http://localhost:4200';  // Origen CORS dinámico

/*
 * Cabeceras CORS y tipo de contenido:
 * Decimos que devolvemos JSON, permitimos peticiones desde nuestro
 * frontend con cualquier método HTTP, y permitimos credenciales
 * (cookies de sesión) para mantener la autenticación.
 */
header("Content-Type: application/json");                          // Respuestas en JSON
header("Access-Control-Allow-Origin: $allowedOrigin");             // CORS: permitimos nuestro frontend
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");  // Métodos HTTP permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");                  // Permitimos cookies de sesión

/*
 * Preflight CORS: si el navegador manda una petición OPTIONS
 * (que hace automáticamente antes de PUT/DELETE), respondemos
 * 200 y salimos. Las cabeceras CORS ya se enviaron arriba.
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {                    // Preflight CORS (navegador antes de PUT/DELETE)
    http_response_code(200);
    exit;                                                          // Respondemos y salimos
}

/*
 * Configuración segura de la cookie de sesión:
 * Es importante configurar estos parámetros ANTES de session_start().
 * 
 * httponly=true: la cookie de sesión no es accesible desde JavaScript.
 *   Si alguien inyecta un script (XSS), no podrá robar la cookie.
 * 
 * samesite=Lax: la cookie no se envía en peticiones que vienen de
 *   otros sitios web. Esto evita ataques CSRF básicos.
 * 
 * secure=true: la cookie solo se envía por HTTPS. En localhost no
 *   tenemos HTTPS, así que lo marcamos como condicional.
 */
session_set_cookie_params([                                       // Configuración segura de la cookie de sesión
    'httponly' => true,                                            // No accesible desde JavaScript (anti-XSS)
    'samesite' => 'Lax',                                           // No se envía desde otros sitios (anti-CSRF)
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',  // Solo HTTPS si está disponible
]);

/* Iniciamos la sesión para mantener la autenticación entre peticiones */
session_start();                                                   // Iniciamos sesión para mantener autenticación

/*
 * RATE LIMITING - Protección contra fuerza bruta:
 * 
 * Guardamos en archivos temporales (dentro del directorio temporal del
 * sistema) el número de intentos de login por IP. Si alguien supera
 * los 10 intentos en 15 minutos, se bloquea temporalmente.
 * 
 * Esto evita que un atacante pruebe miles de contraseñas con un script
 * automatizado (ataque de fuerza bruta).
 */
define('RATE_LIMIT_DIR', sys_get_temp_dir() . '/fitstock_rate_limit');  // Directorio temporal para archivos de rate limiting
define('RATE_LIMIT_MAX_ATTEMPTS', 10);                                   // Máximo 10 intentos
define('RATE_LIMIT_WINDOW', 900);                                        // Ventana de 15 minutos (900 segundos)

/*
 * checkRateLimit($ip):
 * Verifica cuántos intentos de login ha hecho una IP en los últimos 15 minutos.
 * 
 * Cada IP tiene su propio archivo JSON (identificado por el hash MD5 de la IP)
 * que guarda el número de intentos y cuándo empezó a contar.
 * 
 * Si han pasado más de 15 minutos desde el primer intento, reiniciamos
 * el contador automáticamente (la ventana se desliza).
 * 
 * Usamos LOCK_EX en file_put_contents para evitar condiciones de carrera
 * si dos peticiones de la misma IP llegan al mismo tiempo.
 * 
 * Si se supera el límite, respondemos con HTTP 429 (Too Many Requests)
 * y un mensaje claro para el usuario.
 */
function checkRateLimit($ip) {
    if (!is_dir(RATE_LIMIT_DIR)) {
        @mkdir(RATE_LIMIT_DIR, 0700, true);                          // Creamos el directorio si no existe
    }
    $file = RATE_LIMIT_DIR . '/' . md5($ip) . '.json';              // Archivo por IP (hash MD5)
    $now = time();
    $data = ['attempts' => 0, 'first_attempt' => $now];

    if (is_file($file)) {
        $saved = json_decode(file_get_contents($file), true) ?? $data;
        if ($now - $saved['first_attempt'] > RATE_LIMIT_WINDOW) {   // Si pasaron 15 min, reiniciamos
            $saved = ['attempts' => 0, 'first_attempt' => $now];
        }
        $data = $saved;
    }

    $data['attempts']++;
    file_put_contents($file, json_encode($data), LOCK_EX);          // Guardamos con bloqueo (evita condiciones de carrera)

    if ($data['attempts'] > RATE_LIMIT_MAX_ATTEMPTS) {               // Superó el límite
        jsonResponse(["error" => "Demasiados intentos. Intenta de nuevo en 15 minutos."], 429);  // HTTP 429
    }
}

/*
 * clearRateLimit($ip):
 * Cuando el usuario inicia sesión correctamente, eliminamos su archivo
 * de rate limiting. Así el contador se reinicia y no se bloquea
 * injustamente después de un login exitoso.
 */
function clearRateLimit($ip) {
    $file = RATE_LIMIT_DIR . '/' . md5($ip) . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
}

/*
 * Rate limiting específico para el formulario de contacto:
 * Es más restrictivo (5 intentos por ventana) porque el formulario
 * de contacto podría usarse para enviar spam.
 * 
 * Usamos un archivo independiente con prefijo 'contacto_' para no
 * mezclar los intentos de contacto con los de login.
 */
define('RATE_LIMIT_CONTACTO_MAX', 5);

function checkRateLimitContacto($ip) {
    if (!is_dir(RATE_LIMIT_DIR)) {
        @mkdir(RATE_LIMIT_DIR, 0700, true);
    }
    $file = RATE_LIMIT_DIR . '/contacto_' . md5($ip) . '.json';
    $now = time();
    $data = ['attempts' => 0, 'first_attempt' => $now];

    if (is_file($file)) {
        $saved = json_decode(file_get_contents($file), true) ?? $data;
        if ($now - $saved['first_attempt'] > RATE_LIMIT_WINDOW) {
            $saved = ['attempts' => 0, 'first_attempt' => $now];
        }
        $data = $saved;
    }

    $data['attempts']++;
    file_put_contents($file, json_encode($data), LOCK_EX);

    if ($data['attempts'] > RATE_LIMIT_CONTACTO_MAX) {
        jsonResponse(["error" => "Has superado el límite de mensajes. Intenta de nuevo en 15 minutos."], 429);
    }
}

/*
 * Importamos la conexión a la base de datos y todos los modelos.
 * Cada modelo encapsula las consultas SQL de su recurso correspondiente
 * (usuarios, materiales, préstamos, incidencias, productos, compras).
 * 
 * También importamos PHPMailer para el envío de correos desde
 * el formulario de contacto.
 */
require_once __DIR__ . "/../models/Usuario.php";
require_once __DIR__ . "/../models/Material.php";
require_once __DIR__ . "/../models/Prestamo.php";
require_once __DIR__ . "/../models/Incidencia.php";
require_once __DIR__ . "/../models/Producto.php";
require_once __DIR__ . "/../models/Compra.php";

require_once __DIR__ . "/../vendor/PHPMailer/src/Exception.php";
require_once __DIR__ . "/../vendor/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/../vendor/PHPMailer/src/SMTP.php";

/*
 * Variables de ruteo: descomponemos la URL en partes para saber
 * qué recurso nos pide el frontend y con qué método HTTP.
 * 
 * $method   = verbo HTTP (GET, POST, PUT, DELETE)
 * $uri      = ruta completa, ej: /api/usuarios/5
 * $path     = array de segmentos: ['api', 'usuarios', '5']
 * $action   = primer segmento (siempre 'api' aquí)
 * $resource = segundo segmento: login, usuarios, materiales...
 * 
 * Los segmentos adicionales ($path[2] y superiores) se usan para
 * IDs específicos o sub-rutas como cambiar-password.
 */
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = explode('/', trim($uri, '/'));

$action = $path[0] ?? '';
$resource = $path[1] ?? '';

/*
 * jsonResponse($data, $code):
 * Función helper que todos los endpoints usan para responder.
 * Centraliza el envío de respuestas JSON con su código HTTP,
 * para que todas tengan el mismo formato y no tengamos que
 * repetir http_response_code + echo json_encode + exit en cada sitio.
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/*
 * getJsonInput():
 * Lee los datos que envía el frontend en el cuerpo de la petición.
 * Esperamos JSON (application/json), pero si no puede parsearlo,
 * cae en $_POST como fallback (por si alguien envía form-data).
 * 
 * php://input es un stream de solo lectura que contiene el cuerpo
 * de la petición HTTP. file_get_contents lo lee como string.
 */
function getJsonInput() {
    return json_decode(file_get_contents("php://input"), true) ?? $_POST;
}

/*
 * Enrutador principal: evaluamos el primer segmento de la URL.
 * Si es 'api', llamamos a handleApi() que procesa el recurso.
 * Si no, respondemos 404 porque no hay nada aquí.
 */
switch ($action) {
    case 'api':
        handleApi($method, $resource, $path);
        break;
    default:
        jsonResponse(["error" => "Endpoint no encontrado"], 404);
}

/*
 * handleApi($method, $resource, $path):
 * 
 * Esta es la función principal que maneja TODOS los endpoints de la API.
 * 
 * Recibe el método HTTP, el recurso solicitado y los segmentos de la URL,
 * y según el recurso aplica la lógica CRUD correspondiente con control
 * de acceso basado en roles (admin, entrenador, cliente).
 * 
 * Es un switch grande pero cada case es autocontenido y fácil de seguir.
 */
function handleApi($method, $resource, $path) {
    $data = getJsonInput();

    switch ($resource) {

        /*
         * LOGIN (POST /api/login):
         * 
         * 1. Validamos que el email tenga formato correcto antes de
         *    consultar la base de datos (FILTER_VALIDATE_EMAIL).
         * 2. Aplicamos rate limiting para evitar fuerza bruta.
         * 3. Buscamos al usuario por email y verificamos la contraseña
         *    con password_verify() contra el hash bcrypt almacenado.
         * 4. Si coincide: regeneramos el ID de sesión (para evitar
         *    session fixation), guardamos los datos en $_SESSION,
         *    limpiamos el rate limit, y devolvemos los datos del usuario
         *    incluyendo forzar_cambio_password.
         * 5. Si no coincide: devolvemos 401 (Credenciales inválidas).
         */
        case 'login':
            if ($method === 'POST') {
                $email = trim($data['email'] ?? '');
                $password = $data['password'] ?? '';

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    jsonResponse(["error" => "Email inválido"], 400);
                }

                checkRateLimit($_SERVER['REMOTE_ADDR']);

                if ($email && $password) {
                    $usuario = Usuario::obtenerPorEmail($email);
                    if ($usuario && password_verify($password, $usuario->getPasswordHash())) {
                        session_regenerate_id(true);

                        $_SESSION['usuario_id'] = $usuario->getId();
                        $_SESSION['usuario_nombre'] = $usuario->getNombre();
                        $_SESSION['usuario_rol'] = $usuario->getRol();

                        clearRateLimit($_SERVER['REMOTE_ADDR']);

                        jsonResponse([
                            "success" => true,
                            "user" => [
                                "id" => $usuario->getId(),
                                "nombre" => $usuario->getNombre(),
                                "email" => $usuario->getEmail(),
                                "rol" => $usuario->getRol(),
                                "forzar_cambio_password" => intval($usuario->getForzarCambioPassword())
                            ]
                        ]);
                    }
                }
                jsonResponse(["error" => "Credenciales inválidas"], 401);
            }
            break;

        /*
         * REGISTRO (POST /api/registro):
         * 
         * Crea un nuevo usuario con rol 'cliente' por defecto.
         * 
         * Validaciones:
         * - Contraseña mínima de 8 caracteres
         * - Email válido
         * - Nombre, email y password no vacíos
         * 
         * Si el email ya existe en la BD (violación de UNIQUE),
         * la excepción se captura y devolvemos un mensaje claro.
         */
        case 'registro':
            if ($method === 'POST') {
                $nombre = trim($data['nombre'] ?? '');
                $email = trim($data['email'] ?? '');
                $password = $data['password'] ?? '';

                if (strlen($password) < 8) {
                    jsonResponse(["error" => "La contraseña debe tener al menos 8 caracteres"], 400);
                }

                if ($nombre && $email && $password && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    try {
                        Usuario::crear($nombre, $email, $password, 'cliente');
                        jsonResponse(["success" => true, "message" => "Usuario registrado"]);
                    } catch (Exception $e) {
                        jsonResponse(["error" => "El correo ya está registrado"], 400);
                    }
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            }
            break;

        /*
         * LOGOUT (POST /api/logout):
         * 
         * Cerramos la sesión del usuario:
         * 1. Eliminamos la cookie de sesión del navegador
         *    (poniendo una fecha pasada para que expire).
         * 2. Vaciamos $_SESSION.
         * 3. Destruimos la sesión en el servidor con session_destroy().
         */
        case 'logout':
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            $_SESSION = [];
            session_destroy();
            jsonResponse(["success" => true]);
            break;




        /*
         * USUARIOS - CRUD de usuarios:
         * 
         * Este es el endpoint más complejo porque maneja varias
         * sub-rutas y roles. Os explico cada caso:
         * 
         * - PUT /api/usuarios/cambiar-password: cualquier usuario
         *   autenticado puede cambiar su propia contraseña. Necesita
         *   la contraseña actual (old_password) para verificarla.
         * 
         * - GET /api/usuarios: lista todos los usuarios (admin/entrenador).
         *   Los clientes no pueden acceder.
         * 
         * - POST /api/usuarios/forzar-cambio: marca a un usuario
         *   para que deba cambiar la contraseña en el próximo login
         *   (admin/entrenador).
         * 
         * - POST /api/usuarios: crea un nuevo usuario (solo admin).
         * 
         * - PUT /api/usuarios/{id}: actualiza nombre, email,
         *   contraseña y/o rol de un usuario (admin/entrenador).
         *   Los entrenadores no pueden editar admins ni asignar rol admin.
         * 
         * - DELETE /api/usuarios/{id}: elimina un usuario (solo admin).
         */
        case 'usuarios':
            requireSession();

            if ($method === 'PUT' && isset($path[2]) && $path[2] === 'cambiar-password') {
                $old_password = $data['old_password'] ?? '';
                $new_password = $data['new_password'] ?? '';

                if (strlen($new_password) < 8) {
                    jsonResponse(["error" => "La nueva contraseña debe tener al menos 8 caracteres"], 400);
                }

                if ($old_password && $new_password) {
                    $usuario = Usuario::obtenerPorId($_SESSION['usuario_id']);
                    if ($usuario && password_verify($old_password, $usuario->getPasswordHash())) {
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $conexion = Conexion::conectar();
                        $stmt = $conexion->prepare("UPDATE usuarios SET password_hash = ?, forzar_cambio_password = 0 WHERE id_usuario = ?");
                        $stmt->execute([$password_hash, $_SESSION['usuario_id']]);
                        jsonResponse(["success" => true]);
                    } else {
                        jsonResponse(["error" => "La contraseña actual no es correcta"], 400);
                    }
                } else {
                    jsonResponse(["error" => "Contraseña inválida"], 400);
                }
                break;
            }

            if ($_SESSION['usuario_rol'] === 'cliente') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {
                $usuarios = Usuario::obtenerTodos();
                $list = array_map(function($u) {
                    return [
                        "id" => $u->getId(),
                        "nombre" => $u->getNombre(),
                        "email" => $u->getEmail(),
                        "rol" => $u->getRol(),
                        "forzar_cambio_password" => intval($u->getForzarCambioPassword())
                    ];
                }, $usuarios);
                jsonResponse($list);
            } elseif ($method === 'POST' && isset($path[2]) && $path[2] === 'forzar-cambio') {
                if ($_SESSION['usuario_rol'] !== 'admin' && $_SESSION['usuario_rol'] !== 'entrenador') {
                    jsonResponse(["error" => "Acceso denegado"], 403);
                }
                $id_usuario = $data['id_usuario'] ?? null;
                if ($id_usuario) {
                    Usuario::forzarCambioPassword($id_usuario);
                    jsonResponse(["success" => true]);
                } else {
                    jsonResponse(["error" => "ID de usuario requerido"], 400);
                }
            } elseif ($method === 'POST') {
                if ($_SESSION['usuario_rol'] !== 'admin') {
                    jsonResponse(["error" => "Acceso denegado"], 403);
                }
                $nombre = trim($data['nombre'] ?? '');
                $email = trim($data['email'] ?? '');
                $password = $data['password'] ?? '';
                $rol = $data['rol'] ?? 'cliente';

                if (!in_array($rol, ['admin', 'entrenador', 'cliente'])) {
                    jsonResponse(["error" => "Rol inválido"], 400);
                }

                if (strlen($password) < 8) {
                    jsonResponse(["error" => "La contraseña debe tener al menos 8 caracteres"], 400);
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    jsonResponse(["error" => "Email inválido"], 400);
                }

                Usuario::crear($nombre, $email, $password, $rol);
                jsonResponse(["success" => true]);
            } elseif ($method === 'PUT' && isset($path[2])) {
                if ($_SESSION['usuario_rol'] !== 'admin' && $_SESSION['usuario_rol'] !== 'entrenador') {
                    jsonResponse(["error" => "Acceso denegado"], 403);
                }
                $nombre = trim($data['nombre'] ?? '');
                $email = trim($data['email'] ?? '');
                $password = $data['password'] ?? null;
                $rol = $data['rol'] ?? null;
                if ($rol !== null && !in_array($rol, ['admin', 'entrenador', 'cliente'])) {
                    jsonResponse(["error" => "Rol inválido"], 400);
                }
                if ($nombre && $email) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        jsonResponse(["error" => "Email inválido"], 400);
                    }
                    if ($password !== null && strlen($password) < 8) {
                        jsonResponse(["error" => "La contraseña debe tener al menos 8 caracteres"], 400);
                    }
                    if ($_SESSION['usuario_rol'] === 'entrenador') {
                        $target = Usuario::obtenerPorId($path[2]);
                        if ($target && $target->getRol() === 'admin') {
                            jsonResponse(["error" => "No puedes editar un administrador"], 403);
                        }
                        if ($rol === 'admin') {
                            jsonResponse(["error" => "No puedes asignar el rol admin"], 403);
                        }
                    }
                    Usuario::actualizarAdmin($path[2], $nombre, $email, $password, $rol);
                    jsonResponse(["success" => true]);
                } else {
                    jsonResponse(["error" => "Datos inválidos"], 400);
                }
            } elseif ($method === 'DELETE' && isset($path[2])) {
                if ($_SESSION['usuario_rol'] !== 'admin') {
                    jsonResponse(["error" => "Acceso denegado"], 403);
                }
                Usuario::eliminar($path[2]);
                jsonResponse(["success" => true]);
            }
            break;


        /*
         * MATERIALES - CRUD de equipamiento deportivo:
         * 
         * GET /api/materiales (opcional: ?tipo=maquina|prestable)
         * POST /api/materiales
         * PUT /api/materiales/{id}
         * DELETE /api/materiales/{id}
         * 
         * Los clientes pueden ver materiales pero no crear ni eliminar.
         * El filtro por tipo (?tipo=maquina) se usa en el frontend para
         * separar máquinas de material prestable en vistas diferentes.
         */
        case 'materiales':
            requireSession();
            if (($method === 'POST' || $method === 'DELETE') && $_SESSION['usuario_rol'] === 'cliente') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {
                $tipo = $_GET['tipo'] ?? null;
                $materiales = Material::obtenerTodos($tipo);
                $list = array_map(function($m) {
                    return [
                        "id" => $m->getId(),
                        "nombre" => $m->getNombre(),
                        "descripcion" => $m->getDescripcion(),
                        "ubicacion" => $m->getUbicacion(),
                        "estado" => $m->getEstado(),
                        "tipo" => $m->getTipo(),
                        "id_tag_material" => $m->getIdTagMaterial(),
                        "ultima_rev" => $m->getUltimaRev()
                    ];
                }, $materiales);
                jsonResponse($list);
            } elseif ($method === 'POST') {
                $nombre = trim($data['nombre'] ?? '');
                $descripcion = trim($data['descripcion'] ?? '');
                $estado = $data['estado'] ?? 'operativo';
                $tipo = $data['tipo'] ?? 'maquina';
                $id_tag_material = trim($data['id_tag_material'] ?? '');
                $ubicacion = trim($data['ubicacion'] ?? '');
                if (!in_array($estado, ['operativo','averiado','mantenimiento','en_proceso','saliendo','en_reparacion','baja'])) {
                    jsonResponse(["error" => "Estado inválido"], 400);
                }
                if (!in_array($tipo, ['maquina', 'prestable'])) {
                    jsonResponse(["error" => "Tipo inválido"], 400);
                }
                if ($nombre) {
                    Material::crear($nombre, $descripcion, $estado, $tipo, $id_tag_material, null, $ubicacion ?: null);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'PUT' && isset($path[2])) {
                $nombre = trim($data['nombre'] ?? '');
                $descripcion = trim($data['descripcion'] ?? '');
                $estado = $data['estado'] ?? null;
                $ultima_rev = $data['ultima_rev'] ?? null;
                $ubicacion = trim($data['ubicacion'] ?? '');
                $id_tag_material = trim($data['id_tag_material'] ?? '');
                if ($estado !== null && $nombre && $estado && !in_array($estado, ['operativo','averiado','mantenimiento','en_proceso','saliendo','en_reparacion','baja'])) {
                    jsonResponse(["error" => "Estado inválido"], 400);
                }
                if ($nombre && $estado) {
                    Material::actualizar($path[2], $nombre, $descripcion, $estado, $ultima_rev, $ubicacion ?: null, $id_tag_material ?: null);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'DELETE' && isset($path[2])) {
                Material::eliminar($path[2]);
                jsonResponse(["success" => true]);
            }
            break;

        /*
         * PRESTAMOS - Gestión de préstamos de material:
         * 
         * GET /api/prestamos - lista todos con JOINs para obtener
         *   el nombre del usuario y del material.
         * POST /api/prestamos - crea un préstamo. Los clientes solo
         *   pueden crear préstamos para sí mismos (usamos el ID de sesión).
         *   Admin/entrenador pueden asignar a cualquier usuario.
         * PUT /api/prestamos/{id} - actualiza la fecha de devolución
         *   o marca como devuelto (si no se envía fecha_devolucion).
         * DELETE /api/prestamos/{id} - elimina (solo admin/entrenador).
         */
        case 'prestamos':
            requireSession();
            if ($method === 'DELETE' && $_SESSION['usuario_rol'] === 'cliente') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {
                $estadoFiltro = $_GET['estado'] ?? null;
                if ($estadoFiltro) {
                    $prestamos = Prestamo::obtenerPendientes($estadoFiltro);
                } elseif ($_SESSION['usuario_rol'] === 'cliente') {
                    $prestamos = Prestamo::obtenerTodos($_SESSION['usuario_id']);
                } else {
                    $id_usuario = $_GET['id_usuario'] ?? null;
                    $prestamos = Prestamo::obtenerTodos($id_usuario);
                }
                $list = array_map(function($p) {
                    return [
                        "id" => $p->getId(),
                        "id_usuario" => $p->getIdUsuario(),
                        "id_material" => $p->getIdMaterial(),
                        "usuario" => $p->getUsuarioNombre(),
                        "material" => $p->getMaterialNombre(),
                        "fecha" => $p->getFecha(),
                        "devolucion" => $p->getFechaDevolucion(),
                        "estado" => $p->getEstado()
                    ];
                }, $prestamos);
                jsonResponse($list);
            } elseif ($method === 'POST') {
                if ($_SESSION['usuario_rol'] === 'cliente') {
                    $id_usuario = $_SESSION['usuario_id'];
                    $estado = 'pendiente';
                } else {
                    $id_usuario = $data['id_usuario'] ?? $_SESSION['usuario_id'];
                    $estado = $data['estado'] ?? 'pendiente';
                }
                $id_material = $data['id_material'] ?? '';
                $fecha_devolucion = $data['fecha_devolucion'] ?? null;
                if ($id_material) {
                    if (!Material::obtenerPorId($id_material)) {
                        jsonResponse(["error" => "El material no existe"], 400);
                    }
                    Prestamo::crear($id_usuario, $id_material, $fecha_devolucion, $estado);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'PUT' && isset($path[2]) && isset($path[3])) {
                if ($path[3] === 'aprobar') {
                    Prestamo::aprobar($path[2]);
                    jsonResponse(["success" => true, "message" => "Préstamo aprobado"]);
                } elseif ($path[3] === 'confirmar-devolucion') {
                    Prestamo::confirmarDevolucion($path[2]);
                    jsonResponse(["success" => true, "message" => "Devolución confirmada"]);
                } else {
                    jsonResponse(["error" => "Acción no reconocida"], 400);
                }
            } elseif ($method === 'PUT' && isset($path[2])) {
                $fecha_devolucion = $data['fecha_devolucion'] ?? null;
                if ($fecha_devolucion !== null || array_key_exists('fecha_devolucion', $data)) {
                    Prestamo::actualizar($path[2], $fecha_devolucion);
                    jsonResponse(["success" => true, "message" => "Préstamo actualizado"]);
                } else {
                    Prestamo::devolver($path[2]);
                    jsonResponse(["success" => true, "message" => "Préstamo marcado para devolución"]);
                }
            } elseif ($method === 'DELETE' && isset($path[2])) {
                Prestamo::eliminar($path[2]);
                jsonResponse(["success" => true]);
            }
            break;

        /*
         * PRODUCTOS - CRUD de productos en stock:
         * 
         * GET /api/productos - lista todos con cantidad, stock mínimo y precio
         * POST /api/productos/subir-imagen - sube imagen (validando tipo MIME real con finfo)
         * POST /api/productos - crea producto
         * PUT /api/productos/{id} - actualiza datos o solo stock
         * DELETE /api/productos/{id} - elimina producto
         * 
         * Los clientes pueden ver productos pero no crear/eliminar.
         * 
         * Para la subida de imágenes:
         * - Validamos el tipo MIME real con finfo (lee los bytes del archivo)
         *   en lugar de confiar en $_FILES['type'] que el navegador envía
         *   y puede ser falseado por un atacante.
         * - Guardamos en public/images/productos/ con permisos 0755.
         * - El nombre siempre es .jpg para que coincida con getImagenUrl()
         *   del frontend.
         */
        case 'productos':
            requireSession();
            if (($method === 'POST' || $method === 'DELETE') && $_SESSION['usuario_rol'] === 'cliente') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {
                $productos = Producto::obtenerTodos();
                $list = array_map(function($p) {
                    return [
                        "id" => $p->getId(),
                        "nombre" => $p->getNombre(),
                        "descripcion" => $p->getDescripcion(),
                        "cantidad" => intval($p->getCantidadActual()),
                        "stock_minimo" => intval($p->getStockMinimo()),
                        "precio" => floatval($p->getPrecio())
                    ];
                }, $productos);
                jsonResponse($list);
            } elseif ($method === 'POST' && isset($path[2]) && $path[2] === 'subir-imagen') {
                $nombre = trim($data['nombre'] ?? '');
                if (!$nombre) {
                    jsonResponse(["error" => "Nombre del producto requerido"], 400);
                }
                if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                    jsonResponse(["error" => "No se recibió ninguna imagen"], 400);
                }
                $imagen = $_FILES['imagen'];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeReal = finfo_file($finfo, $imagen['tmp_name']);
                finfo_close($finfo);

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($mimeReal, $allowedTypes)) {
                    jsonResponse(["error" => "Formato no válido. Usa JPG, PNG, GIF o WebP"], 400);
                }

                $uploadDir = __DIR__ . '/../../FitStock-APP/public/images/productos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = $nombre . '.jpg';
                if (move_uploaded_file($imagen['tmp_name'], $uploadDir . $filename)) {
                    jsonResponse(["success" => true, "imagen" => $filename]);
                } else {
                    jsonResponse(["error" => "Error al guardar la imagen"], 500);
                }
            } elseif ($method === 'POST') {
                $nombre = trim($data['nombre'] ?? '');
                $descripcion = trim($data['descripcion'] ?? '');
                $cantidad = intval($data['cantidad'] ?? 0);
                $stock_minimo = intval($data['stock_minimo'] ?? 0);
                $precio = floatval($data['precio'] ?? 0);
                if ($nombre) {
                    if ($cantidad < 0) {
                        jsonResponse(["error" => "La cantidad no puede ser negativa"], 400);
                    }
                    if ($precio <= 0) {
                        jsonResponse(["error" => "El precio debe ser mayor que 0"], 400);
                    }
                    Producto::crear($nombre, $descripcion ?: null, $cantidad, $stock_minimo, $precio);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'PUT' && isset($path[2])) {
                $nombre = trim($data['nombre'] ?? '');
                $descripcion = trim($data['descripcion'] ?? '');
                $cantidad = intval($data['cantidad'] ?? -1);
                $stock_minimo = intval($data['stock_minimo'] ?? -1);
                $precio = floatval($data['precio'] ?? -1);
                if ($nombre && $cantidad >= 0 && $stock_minimo >= 0 && $precio >= 0) {
                    Producto::actualizar($path[2], $nombre, $descripcion ?: null, $cantidad, $stock_minimo, $precio);
                    jsonResponse(["success" => true]);
                } elseif ($cantidad >= 0 && !isset($data['nombre'])) {
                    Producto::actualizarStock($path[2], $cantidad);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'DELETE' && isset($path[2])) {
                Producto::eliminar($path[2]);
                jsonResponse(["success" => true]);
            }
            break;

        /*
         * COMPRAS - Registro de compras de productos:
         * 
         * GET /api/compras:
         *   - Clientes: solo ven sus propias compras (filtradas por ID de sesión)
         *   - Admin/entrenador: ven todas, pueden filtrar por ?id_usuario=
         * 
         * POST /api/compras:
         *   - Crea una compra para el usuario autenticado
         *   - Necesita id_producto, cantidad y precio_unitario
         *   - El frontend calcula el total (cantidad * precio_unitario)
         */
        case 'compras':
            requireSession();
            if ($method === 'GET') {
                if ($_SESSION['usuario_rol'] === 'cliente') {
                    $compras = Compra::obtenerTodos($_SESSION['usuario_id']);
                } else {
                    $id_usuario = $_GET['id_usuario'] ?? null;
                    $compras = Compra::obtenerTodos($id_usuario);
                }
                $list = array_map(function($c) {
                    return [
                        "id" => $c->getId(),
                        "id_usuario" => $c->getIdUsuario(),
                        "id_producto" => $c->getIdProducto(),
                        "nombre_producto" => $c->getNombreProducto(),
                        "cantidad" => intval($c->getCantidad()),
                        "precio_unitario" => floatval($c->getPrecioUnitario()),
                        "total" => floatval($c->getTotal()),
                        "fecha_compra" => $c->getFechaCompra()
                    ];
                }, $compras);
                jsonResponse($list);
            } elseif ($method === 'POST') {
                $id_producto = $data['id_producto'] ?? '';
                $cantidad = intval($data['cantidad'] ?? 1);
                $precio_unitario = floatval($data['precio_unitario'] ?? 0);
                if ($id_producto && $cantidad > 0 && $precio_unitario > 0) {
                    $conexion = Conexion::conectar();
                    $stmt = $conexion->prepare("SELECT id_producto FROM productos_stock WHERE id_producto = ?");
                    $stmt->execute([$id_producto]);
                    if (!$stmt->fetch()) {
                        jsonResponse(["error" => "El producto no existe"], 400);
                    }
                    Compra::crear($_SESSION['usuario_id'], $id_producto, $cantidad, $precio_unitario);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            }
            break;

        /*
         * INCIDENCIAS - Gestión de averías y problemas:
         * 
         * GET /api/incidencias - lista todas con JOINs para obtener
         *   nombre del material, tag y ubicación.
         * POST /api/incidencias - crea una incidencia y automáticamente
         *   marca el material como 'averiado'. El usuario de la sesión
         *   se asigna como reportador.
         * PUT /api/incidencias/{id} - actualiza estado/prioridad/descripción.
         *   Si se resuelve, el material vuelve a 'operativo'.
         *   Si se marca como 'en_proceso', el material pasa a 'en_reparacion'.
         * DELETE /api/incidencias/{id} - elimina (solo admin/entrenador).
         */
        case 'incidencias':
            requireSession();
            if ($method === 'DELETE' && $_SESSION['usuario_rol'] === 'cliente') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {
                if ($_SESSION['usuario_rol'] === 'cliente') {
                    $incidencias = Incidencia::obtenerTodos($_SESSION['usuario_id']);
                } else {
                    $id_usuario = $_GET['id_usuario'] ?? null;
                    $incidencias = Incidencia::obtenerTodos($id_usuario);
                }
                $list = array_map(function($inc) {
                    return [
                        "id" => $inc->getId(),
                        "id_material" => $inc->getIdMaterial(),
                        "id_user_rep" => $inc->getIdUser(),
                        "descripcion" => $inc->getDescripcion(),
                        "prioridad" => $inc->getPrioridad(),
                        "estado" => $inc->getEstado(),
                        "created_at" => $inc->getCreatedAt(),
                        "fecha_resolucion" => $inc->getFechaResolucion(),
                        "nombre_material" => $inc->getNombreMaterial(),
                        "id_tag_material" => $inc->getIdTagMaterial(),
                        "ubicacion" => $inc->getUbicacion()
                    ];
                }, $incidencias);
                jsonResponse($list);
            } elseif ($method === 'POST') {
                $id_material = $data['id_material'] ?? '';
                $descripcion = trim($data['descripcion'] ?? '');
                $prioridad = $data['prioridad'] ?? 'media';
                if (!in_array($prioridad, ['baja', 'media', 'alta'])) {
                    jsonResponse(["error" => "Prioridad inválida"], 400);
                }
                if ($id_material && $descripcion) {
                    if (!Material::obtenerPorId($id_material)) {
                        jsonResponse(["error" => "El material no existe"], 400);
                    }
                    Incidencia::crear($id_material, $_SESSION['usuario_id'], $descripcion, $prioridad);
                    $conexion = Conexion::conectar();
                    $stmt = $conexion->prepare("UPDATE material SET estado = 'averiado' WHERE id_material = ?");
                    $stmt->execute([$id_material]);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'PUT' && isset($path[2])) {
                $descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : null;
                $prioridad = $data['prioridad'] ?? null;
                $estado = $data['estado'] ?? null;
                Incidencia::actualizar($path[2], $prioridad, $estado, $descripcion);
                $inc = Incidencia::obtenerPorId($path[2]);
                if ($inc && $inc->getIdMaterial()) {
                    $conexion = Conexion::conectar();
                    if ($estado === 'resuelta') {
                        $stmt = $conexion->prepare("UPDATE material SET estado = 'operativo' WHERE id_material = ?");
                        $stmt->execute([$inc->getIdMaterial()]);
                    } elseif ($estado === 'en_proceso') {
                        $stmt = $conexion->prepare("UPDATE material SET estado = 'en_reparacion' WHERE id_material = ?");
                        $stmt->execute([$inc->getIdMaterial()]);
                    }
                }
                jsonResponse(["success" => true]);
            } elseif ($method === 'DELETE' && isset($path[2])) {
                Incidencia::eliminar($path[2]);
                jsonResponse(["success" => true]);
            }
            break;

        /*
         * RESUMEN - Dashboard de administración (GET /api/resumen):
         * 
         * Devuelve datos agregados para el panel principal:
         * - Conteo de incidencias por estado (abierta, en_proceso, resuelta)
         * - Productos con stock por debajo del mínimo (alertas de reposición)
         * - Máquinas agrupadas por estado (operativo, averiado, en_reparacion)
         * - Gasto total de todos los usuarios y desglose por usuario
         * 
         * Todo se obtiene con consultas SQL directas desde aquí porque
         * son consultas de agregación que no encajan en un modelo CRUD.
         */
        case 'resumen':
            requireSession();
            if ($method === 'GET') {
                $conexion = Conexion::conectar();

                $stmt = $conexion->query("SELECT estado_inc, COUNT(*) as total FROM incidencias GROUP BY estado_inc");
                $incidencias = ['abierta' => 0, 'en_proceso' => 0, 'resuelta' => 0];
                while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $incidencias[$fila['estado_inc']] = intval($fila['total']);
                }

                $stmt = $conexion->query("SELECT id_producto, nombre_prod, cant_actual, stock_minimo FROM productos_stock WHERE cant_actual < stock_minimo ORDER BY cant_actual ASC");
                $stock_bajo = [];
                while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $stock_bajo[] = [
                        "id" => intval($fila['id_producto']),
                        "nombre" => $fila['nombre_prod'],
                        "cantidad" => intval($fila['cant_actual']),
                        "stock_minimo" => intval($fila['stock_minimo'])
                    ];
                }

                $stmt = $conexion->query("SELECT estado, COUNT(*) as total FROM material WHERE tipo = 'maquina' GROUP BY estado");
                $maquinas = [];
                while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $maquinas[$fila['estado']] = intval($fila['total']);
                }
                $total_maquinas = array_sum($maquinas);

                $stmt = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM compras");
                $total_gastado = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

                $stmt = $conexion->query(
                    "SELECT u.id_usuario, u.nombre, u.email, COALESCE(SUM(c.total), 0) as total
                     FROM usuarios u
                     LEFT JOIN compras c ON u.id_usuario = c.id_usuario
                     GROUP BY u.id_usuario
                     ORDER BY total DESC"
                );
                $gastos_por_usuario = [];
                while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $gastos_por_usuario[] = [
                        "id" => intval($fila['id_usuario']),
                        "nombre" => $fila['nombre'],
                        "email" => $fila['email'],
                        "total" => floatval($fila['total'])
                    ];
                }

                jsonResponse([
                    "incidencias" => $incidencias,
                    "stock_bajo" => $stock_bajo,
                    "maquinas" => [
                        "por_estado" => $maquinas,
                        "total" => $total_maquinas
                    ],
                    "gastos" => [
                        "total" => $total_gastado,
                        "por_usuario" => $gastos_por_usuario
                    ]
                ]);
            }
            break;

        /*
         * CONTACTO (POST /api/contacto):
         * 
         * Formulario de contacto público que envía un correo usando PHPMailer.
         * 
         * Tiene su propio rate limiting (5 envíos por IP cada 15 minutos)
         * para evitar spam.
         * 
         * La contraseña de Gmail se obtiene de la variable de entorno
         * MAIL_PASSWORD por seguridad, nunca hardcodeada.
         * 
         * El proceso:
         * 1. Validar email y mensaje
         * 2. Configurar PHPMailer con SMTP de Gmail
         * 3. Enviar el correo a infofitstock@gmail.com
         * 4. Responder éxito o error (con logging interno)
         */
        case 'contacto':
            if ($method === 'POST') {
                checkRateLimitContacto($_SERVER['REMOTE_ADDR']);

                $email = trim($data['email'] ?? '');
                $mensaje = trim($data['mensaje'] ?? '');

                if (!$email || !$mensaje) {
                    jsonResponse(["error" => "Todos los campos son obligatorios"], 400);
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    jsonResponse(["error" => "Email inválido"], 400);
                }

                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'infofitstock@gmail.com';
                    $mail->Password   = getenv('MAIL_PASSWORD') ?: '';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom('infofitstock@gmail.com', 'FitStock Contacto');
                    $mail->addAddress('infofitstock@gmail.com');
                    $mail->addReplyTo($email);

                    $mail->Subject = 'Nuevo contacto desde FitStock';
                    $mail->Body    = "Email de contacto: $email\n\nMensaje:\n$mensaje";
                    $mail->AltBody = strip_tags($mail->Body);

                    $mail->send();
                    jsonResponse(["success" => true, "message" => "Mensaje enviado correctamente"]);
                } catch (PHPMailer\PHPMailer\Exception $e) {
                    error_log("Error PHPMailer en contacto: " . $e->getMessage());
                    jsonResponse(["error" => "Error al enviar el mensaje. Inténtalo de nuevo más tarde."], 500);
                } catch (Exception $e) {
                    error_log("Error interno en contacto: " . $e->getMessage());
                    jsonResponse(["error" => "Error interno del servidor"], 500);
                }
            }
            break;

        /*
         * DEFAULT: Si el recurso solicitado no coincide con ningún
         * case de los anteriores, respondemos 404 (Not Found).
         */
        default:
            jsonResponse(["error" => "Recurso no encontrado"], 404);
    }
}

/*
 * requireSession():
 * 
 * Función helper que verifica si el usuario tiene una sesión activa.
 * Si $_SESSION['usuario_id'] no existe, significa que el usuario no
 * ha iniciado sesión, y respondemos con HTTP 401 (No autenticado).
 * 
 * Se llama al inicio de cualquier endpoint protegido.
 */
function requireSession() {
    if (!isset($_SESSION['usuario_id'])) {
        jsonResponse(["error" => "No autenticado"], 401);
    }
}
