<?php
// Cabeceras CORS para permitir peticiones desde el frontend Angular con credenciales
header("Content-Type: application/json");                                          // Tipo de contenido JSON
header("Access-Control-Allow-Origin: http://localhost:4200");                      // Origen permitido (frontend Angular)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");           // Métodos HTTP permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization");               // Cabeceras permitidas
header("Access-Control-Allow-Credentials: true");                                  // Permite cookies de sesión

// Si la petición es OPTIONS (preflight CORS), responder con 200 y salir sin ejecutar nada más
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Inicia la sesión PHP para mantener autenticación entre peticiones
session_start();
// Importa la conexión a la base de datos
require_once __DIR__ . "/../conexion.php";
// Importa todos los modelos
require_once __DIR__ . "/../models/Usuario.php";
require_once __DIR__ . "/../models/Material.php";
require_once __DIR__ . "/../models/Prestamo.php";
require_once __DIR__ . "/../models/Incidencia.php";
require_once __DIR__ . "/../models/Producto.php";

// Variables de ruteo: método HTTP, URI y segmentos de la ruta
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = explode('/', trim($uri, '/'));

$action = $path[0] ?? '';       // Primer segmento (debería ser 'api')
$resource = $path[1] ?? '';     // Segundo segmento (ej: 'login', 'usuarios', 'materiales')

// Función auxiliar: envía respuesta JSON con código HTTP y termina la ejecución
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Función auxiliar: obtiene el cuerpo JSON de la petición o los datos POST
function getJsonInput() {
    return json_decode(file_get_contents("php://input"), true) ?? $_POST;
}

// Enrutador principal: redirige según el primer segmento de la URL
switch ($action) {
    case 'api':                                      // Si la ruta empieza por /api
        handleApi($method, $resource, $path);        // Llama al manejador de la API
        break;
    default:                                         // Si no es una ruta válida
        jsonResponse(["error" => "Endpoint no encontrado"], 404);   // 404 - No encontrado
}

// Función principal que maneja todos los endpoints de la API
function handleApi($method, $resource, $path) {
    $data = getJsonInput();    // Obtiene los datos de la petición
    
    switch ($resource) {
      
        // LOGIN - Inicio de sesión (POST /api/login)
    
        case 'login':
            if ($method === 'POST') {                                  // Solo acepta POST
                $email = trim($data['email'] ?? '');                   // Email del formulario
                $password = $data['password'] ?? '';                   // Contraseña del formulario
                
                if ($email && $password) {                             // Si ambos campos están presentes
                    $usuario = Usuario::obtenerPorEmail($email);       // Busca usuario por email
                    if ($usuario && password_verify($password, $usuario->getPasswordHash())) {   // Verifica contraseña
                        // Guarda datos del usuario en la sesión
                        $_SESSION['usuario_id'] = $usuario->getId();
                        $_SESSION['usuario_nombre'] = $usuario->getNombre();
                        $_SESSION['usuario_rol'] = $usuario->getRol();
                        // Responde con los datos del usuario
                        jsonResponse([
                            "success" => true,
                            "user" => [
                                "id" => $usuario->getId(),
                                "nombre" => $usuario->getNombre(),
                                "email" => $usuario->getEmail(),
                                "rol" => $usuario->getRol()
                            ]
                        ]);
                    }
                }
                jsonResponse(["error" => "Credenciales inválidas"], 401);   // 401 - No autorizado
            }
            break;

        
        // REGISTRO - Crear cuenta nueva (POST /api/registro)
       
        case 'registro':
            if ($method === 'POST') {
                $nombre = trim($data['nombre'] ?? '');       // Nombre del formulario
                $email = trim($data['email'] ?? '');         // Email del formulario
                $password = $data['password'] ?? '';         // Contraseña del formulario
                
                if ($nombre && $email && $password) {        // Si todos los campos están presentes
                    try {
                        Usuario::crear($nombre, $email, $password, 'cliente');   // Crea usuario con rol 'cliente' por defecto
                        jsonResponse(["success" => true, "message" => "Usuario registrado"]);
                    } catch (Exception $e) {
                        jsonResponse(["error" => "El correo ya está registrado"], 400);   // 400 - Email duplicado
                    }
                }
                jsonResponse(["error" => "Datos inválidos"], 400);   // 400 - Datos incompletos
            }
            break;

        
        // LOGOUT - Cerrar sesión (POST /api/logout)
  
        case 'logout':
            session_destroy();                               // Destruye toda la sesión
            jsonResponse(["success" => true]);               // Confirma el cierre de sesión
            break;

        
        // PERFIL - Obtener/actualizar perfil del usuario autenticado
       
        case 'perfil':
            requireSession();                                // Requiere autenticación
            if ($method === 'GET') {                         // GET /api/perfil - Obtener perfil
                $usuario = Usuario::obtenerPorId($_SESSION['usuario_id']);   // Busca usuario por ID de sesión
                jsonResponse([
                    "id" => $usuario->getId(),
                    "nombre" => $usuario->getNombre(),
                    "email" => $usuario->getEmail(),
                    "rol" => $usuario->getRol()
                ]);
            } elseif ($method === 'PUT') {                   // PUT /api/perfil - Actualizar perfil
                $nombre = trim($data['nombre'] ?? '');
                $email = trim($data['email'] ?? '');
                $password = $data['password'] ?? null;       // Contraseña opcional
                
                Usuario::actualizar($_SESSION['usuario_id'], $nombre, $email, $password);   // Actualiza en BD
                $_SESSION['usuario_nombre'] = $nombre;       // Actualiza el nombre en sesión
                jsonResponse(["success" => true]);
            }
            break;

        
        // USUARIOS - CRUD de usuarios (solo admin y entrenador)

        case 'usuarios':
            requireSession();                                // Requiere autenticación
            if ($_SESSION['usuario_rol'] === 'cliente') {    // Los clientes no pueden acceder
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {                         // GET /api/usuarios - Listar todos (admin/entrenador)
                $usuarios = Usuario::obtenerTodos();
                $list = array_map(function($u) {             // Mapea a arrays sin password_hash
                    return [
                        "id" => $u->getId(),
                        "nombre" => $u->getNombre(),
                        "email" => $u->getEmail(),
                        "rol" => $u->getRol()
                    ];
                }, $usuarios);
                jsonResponse($list);
            } elseif ($method === 'POST') {                  // POST /api/usuarios - Crear usuario (solo admin)
                if ($_SESSION['usuario_rol'] !== 'admin') {
                    jsonResponse(["error" => "Acceso denegado"], 403);
                }
                $nombre = trim($data['nombre'] ?? '');
                $email = trim($data['email'] ?? '');
                $password = $data['password'] ?? '';
                $rol = $data['rol'] ?? 'cliente';            // Rol por defecto: cliente
                
                Usuario::crear($nombre, $email, $password, $rol);
                jsonResponse(["success" => true]);
            } elseif ($method === 'DELETE' && isset($path[2])) {   // DELETE /api/usuarios/{id} (solo admin)
                if ($_SESSION['usuario_rol'] !== 'admin') {
                    jsonResponse(["error" => "Acceso denegado"], 403);
                }
                Usuario::eliminar($path[2]);                 // Elimina usuario por ID
                jsonResponse(["success" => true]);
            }
            break;

      
        // MATERIALES - CRUD de equipamiento deportivo
       
        case 'materiales':
            requireSession();                                // Requiere autenticación
            // Los clientes no pueden crear ni eliminar materiales
            if (($method === 'POST' || $method === 'DELETE') && $_SESSION['usuario_rol'] === 'cliente') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {                         // GET /api/materiales - Listar todos
                $tipo = $_GET['tipo'] ?? null;               // Filtro opcional por tipo (?tipo=maquina|prestable)
                $materiales = Material::obtenerTodos($tipo);
                $list = array_map(function($m) {
                    return [
                        "id" => $m->getId(),
                        "nombre" => $m->getNombre(),
                        "descripcion" => $m->getDescripcion(),
                        "ubicacion" => $m->getUbicacion(),
                        "estado" => $m->getEstado(),
                        "tipo" => $m->getTipo(),
                        "qr" => $m->getQrIdentificador(),
                        "ultima_rev" => $m->getUltimaRev()
                    ];
                }, $materiales);
                jsonResponse($list);
            } elseif ($method === 'POST') {                  // POST /api/materiales - Crear material
                $nombre = trim($data['nombre'] ?? '');
                $descripcion = trim($data['descripcion'] ?? '');
                $estado = $data['estado'] ?? 'operativo';    // Estado por defecto
                $tipo = $data['tipo'] ?? 'maquina';          // Tipo por defecto
                $qr = trim($data['qr'] ?? '');
                $ubicacion = trim($data['ubicacion'] ?? '');
                if ($nombre) {
                    Material::crear($nombre, $descripcion, $estado, $tipo, $qr, null, $ubicacion ?: null);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'PUT' && isset($path[2])) {   // PUT /api/materiales/{id} - Actualizar material
                $nombre = trim($data['nombre'] ?? '');
                $descripcion = trim($data['descripcion'] ?? '');
                $estado = $data['estado'] ?? null;
                $ultima_rev = $data['ultima_rev'] ?? null;
                $ubicacion = trim($data['ubicacion'] ?? '');
                if ($nombre && $estado) {
                    Material::actualizar($path[2], $nombre, $descripcion, $estado, $ultima_rev, $ubicacion ?: null);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'DELETE' && isset($path[2])) {   // DELETE /api/materiales/{id}
                Material::eliminar($path[2]);
                jsonResponse(["success" => true]);
            }
            break;

    
        // PRESTAMOS - CRUD de préstamos de material
        
        case 'prestamos':
            requireSession();                                // Requiere autenticación
            // Los clientes no pueden eliminar préstamos (sí pueden crearlos)
            if ($method === 'DELETE' && $_SESSION['usuario_rol'] === 'cliente') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {                         // GET /api/prestamos - Listar todos con JOINs
                $prestamos = Prestamo::obtenerTodos();
                $list = array_map(function($p) {
                    return [
                        "id" => $p->getId(),
                        "id_usuario" => $p->getIdUsuario(),
                        "id_material" => $p->getIdMaterial(),
                        "usuario" => $p->getUsuarioNombre(),
                        "material" => $p->getMaterialNombre(),
                        "fecha" => $p->getFecha(),
                        "devolucion" => $p->getFechaDevolucion()
                    ];
                }, $prestamos);
                jsonResponse($list);
            } elseif ($method === 'POST') {                  // POST /api/prestamos - Crear préstamo
                // Los clientes solo pueden crear préstamos para sí mismos
                if ($_SESSION['usuario_rol'] === 'cliente') {
                    $id_usuario = $_SESSION['usuario_id'];
                } else {
                    $id_usuario = $data['id_usuario'] ?? $_SESSION['usuario_id'];
                }
                $id_material = $data['id_material'] ?? '';
                $fecha_devolucion = $data['fecha_devolucion'] ?? null;          // Fecha devolución opcional
                if ($id_material) {
                    Prestamo::crear($id_usuario, $id_material, $fecha_devolucion);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'PUT' && isset($path[2])) {   // PUT /api/prestamos/{id} - Actualizar préstamo
                $fecha_devolucion = $data['fecha_devolucion'] ?? null;
                if ($fecha_devolucion !== null || array_key_exists('fecha_devolucion', $data)) {
                    Prestamo::actualizar($path[2], $fecha_devolucion);
                    jsonResponse(["success" => true, "message" => "Préstamo actualizado"]);
                } else {
                    Prestamo::devolver($path[2]);
                    jsonResponse(["success" => true, "message" => "Préstamo devuelto"]);
                }
            } elseif ($method === 'DELETE' && isset($path[2])) {   // DELETE /api/prestamos/{id}
                Prestamo::eliminar($path[2]);
                jsonResponse(["success" => true]);
            }
            break;
            
        // PRODUCTOS - CRUD de productos en stock
      
        case 'productos':
            requireSession();                                // Requiere autenticación
            // Los clientes no pueden crear ni eliminar productos
            if (($method === 'POST' || $method === 'DELETE') && $_SESSION['usuario_rol'] === 'cliente') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {                         // GET /api/productos - Listar todos
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
            } elseif ($method === 'POST') {                  // POST /api/productos - Crear producto
                $nombre = trim($data['nombre'] ?? '');
                $descripcion = trim($data['descripcion'] ?? '');
                $cantidad = intval($data['cantidad'] ?? 0);
                $stock_minimo = intval($data['stock_minimo'] ?? 0);
                $precio = floatval($data['precio'] ?? 0);
                if ($nombre) {
                    Producto::crear($nombre, $descripcion ?: null, $cantidad, $stock_minimo, $precio);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'PUT' && isset($path[2])) {   // PUT /api/productos/{id} - Actualizar producto
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
            } elseif ($method === 'DELETE' && isset($path[2])) {   // DELETE /api/productos/{id}
                Producto::eliminar($path[2]);
                jsonResponse(["success" => true]);
            }
            break;

        
        // INCIDENCIAS - CRUD de incidencias en materiales
       
        case 'incidencias':
            requireSession();                                // Requiere autenticación
            // Los clientes no pueden eliminar incidencias (sí pueden crearlas)
            if ($method === 'DELETE' && $_SESSION['usuario_rol'] === 'cliente') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {                         // GET /api/incidencias - Listar todas
                $incidencias = Incidencia::obtenerTodos();
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
                        "nombre_material" => $inc->getNombreMaterial()
                    ];
                }, $incidencias);
                jsonResponse($list);
            } elseif ($method === 'POST') {                  // POST /api/incidencias - Crear incidencia
                $id_material = $data['id_material'] ?? '';
                $descripcion = trim($data['descripcion'] ?? '');
                $prioridad = $data['prioridad'] ?? 'media';  // Prioridad por defecto
                if ($id_material && $descripcion) {
                    // Asigna automáticamente el usuario de la sesión como reportador
                    Incidencia::crear($id_material, $_SESSION['usuario_id'], $descripcion, $prioridad);
                    // Cambia el estado del material a 'averiado' al reportar una incidencia
                    $conexion = Conexion::conectar();
                    $stmt = $conexion->prepare("UPDATE material SET estado = 'averiado' WHERE id_material = ?");
                    $stmt->execute([$id_material]);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'PUT' && isset($path[2])) {   // PUT /api/incidencias/{id} - Actualizar prioridad/estado
                $prioridad = $data['prioridad'] ?? null;
                $estado = $data['estado'] ?? null;
                Incidencia::actualizar($path[2], $prioridad, $estado);
                // Cambia el estado de la máquina según el estado de la incidencia
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
            } elseif ($method === 'DELETE' && isset($path[2])) {   // DELETE /api/incidencias/{id}
                Incidencia::eliminar($path[2]);
                jsonResponse(["success" => true]);
            }
            break;

        // DEFAULT - no encontrado
       
        default:
            jsonResponse(["error" => "Recurso no encontrado"], 404);
    }
}

// Función de seguridad: verifica que el usuario esté autenticado, si no devuelve 401
function requireSession() {
    if (!isset($_SESSION['usuario_id'])) {
        jsonResponse(["error" => "No autenticado"], 401);
    }
}
