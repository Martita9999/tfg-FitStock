<?php
/*
 * Enrutador interno de la API REST de FitStock.
 * Recibe todas las peticiones cuyo path comienza con /api (derivadas desde router.php)
 * y las distribuye según el recurso solicitado: login, usuarios, materiales, etc.
 * Cada recurso implementa las operaciones CRUD correspondientes con control de roles.
 */
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
require_once __DIR__ . "/../models/Compra.php";

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
            if ($method === 'POST') {
                $email = trim($data['email'] ?? '');
                $password = $data['password'] ?? '';
                
                if ($email && $password) {
                    $usuario = Usuario::obtenerPorEmail($email);
                    if ($usuario && password_verify($password, $usuario->getPasswordHash())) {
                        $_SESSION['usuario_id'] = $usuario->getId();
                        $_SESSION['usuario_nombre'] = $usuario->getNombre();
                        $_SESSION['usuario_rol'] = $usuario->getRol();
                        jsonResponse([
                            "success" => true,
                            "user" => [
                                "id" => $usuario->getId(),
                                "nombre" => $usuario->getNombre(),
                                "email" => $usuario->getEmail(),
                                "rol" => $usuario->getRol(),
                                // Indica si el usuario debe cambiar su contraseña (1 = sí, 0 = no).
                                // El frontend usa este campo para redirigir al formulario de cambio de contraseña tras el login.
                                "forzar_cambio_password" => intval($usuario->getForzarCambioPassword())
                            ]
                        ]);
                    }
                }
                jsonResponse(["error" => "Credenciales inválidas"], 401);
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




        
        // USUARIOS - CRUD de usuarios (solo admin y entrenador)

        case 'usuarios':
            requireSession();                                // Requiere autenticación

            // Permitir a cualquier usuario autenticado cambiar su propia contraseña.
            // Al completar el cambio exitosamente, se resetea forzar_cambio_password a 0
            // para que el usuario no tenga que cambiar la contraseña de nuevo en el próximo login.
            if ($method === 'PUT' && isset($path[2]) && $path[2] === 'cambiar-password') {
                $old_password = $data['old_password'] ?? '';
                $new_password = $data['new_password'] ?? '';
                if ($old_password && $new_password) {
                    $usuario = Usuario::obtenerPorId($_SESSION['usuario_id']);
                    if ($usuario && password_verify($old_password, $usuario->getPasswordHash())) {
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $conexion = Conexion::conectar();
                        // Actualiza la contraseña y limpia la marca de cambio forzado simultáneamente
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

            if ($_SESSION['usuario_rol'] === 'cliente') {    // Los clientes no pueden acceder
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            if ($method === 'GET') {                         // GET /api/usuarios - Listar todos (admin/entrenador)
                $usuarios = Usuario::obtenerTodos();
                $list = array_map(function($u) {
                    return [
                        "id" => $u->getId(),
                        "nombre" => $u->getNombre(),
                        "email" => $u->getEmail(),
                        "rol" => $u->getRol(),
                        // Incluye el estado de cambio forzado de contraseña para que el administrador
                        // pueda ver qué usuarios tienen pendiente el cambio de contraseña.
                        "forzar_cambio_password" => intval($u->getForzarCambioPassword())
                    ];
                }, $usuarios);
                jsonResponse($list);
            // POST /api/usuarios/forzar-cambio - Marca a un usuario para que deba cambiar su contraseña
            // en el próximo inicio de sesión. Acción restringida a administradores y entrenadores.
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
            } elseif ($method === 'POST') {                  // POST /api/usuarios - Crear usuario (solo admin)
                if ($_SESSION['usuario_rol'] !== 'admin') {
                    jsonResponse(["error" => "Acceso denegado"], 403);
                }
                $nombre = trim($data['nombre'] ?? '');
                $email = trim($data['email'] ?? '');
                $password = $data['password'] ?? '';
                $rol = $data['rol'] ?? 'cliente';
                
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
                if ($nombre && $email) {
                    // Entrenador no puede editar admins ni asignar rol admin
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
                        "id_tag_material" => $m->getIdTagMaterial(),
                        "ultima_rev" => $m->getUltimaRev()
                    ];
                }, $materiales);
                jsonResponse($list);
            } elseif ($method === 'POST') {                  // POST /api/materiales - Crear material
                $nombre = trim($data['nombre'] ?? '');
                $descripcion = trim($data['descripcion'] ?? '');
                $estado = $data['estado'] ?? 'operativo';    // Estado por defecto
                $tipo = $data['tipo'] ?? 'maquina';          // Tipo por defecto
                $id_tag_material = trim($data['id_tag_material'] ?? '');
                $ubicacion = trim($data['ubicacion'] ?? '');
                if ($nombre) {
                    Material::crear($nombre, $descripcion, $estado, $tipo, $id_tag_material, null, $ubicacion ?: null);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
            } elseif ($method === 'PUT' && isset($path[2])) {   // PUT /api/materiales/{id} - Actualizar material
                $nombre = trim($data['nombre'] ?? '');
                $descripcion = trim($data['descripcion'] ?? '');
                $estado = $data['estado'] ?? null;
                $ultima_rev = $data['ultima_rev'] ?? null;
                $ubicacion = trim($data['ubicacion'] ?? '');
                $id_tag_material = trim($data['id_tag_material'] ?? '');
                if ($nombre && $estado) {
                    Material::actualizar($path[2], $nombre, $descripcion, $estado, $ultima_rev, $ubicacion ?: null, $id_tag_material ?: null);
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
            } elseif ($method === 'POST' && isset($path[2]) && $path[2] === 'subir-imagen') {
                // POST /api/productos/subir-imagen - Subir imagen de un producto
                $nombre = trim($data['nombre'] ?? '');             // Nombre del producto para nombrar el archivo
                if (!$nombre) {
                    jsonResponse(["error" => "Nombre del producto requerido"], 400);
                }
                // Verifica que se haya recibido un archivo sin errores
                if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                    jsonResponse(["error" => "No se recibió ninguna imagen"], 400);
                }
                $imagen = $_FILES['imagen'];
                // Solo permite formatos de imagen comunes
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($imagen['type'], $allowedTypes)) {
                    jsonResponse(["error" => "Formato no válido. Usa JPG, PNG, GIF o WebP"], 400);
                }
                // Ruta absoluta hasta la carpeta pública del frontend donde se almacenan las imágenes
                $uploadDir = __DIR__ . '/../../FitStock-APP/public/images/productos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);                 // Crea la carpeta si no existe
                }
                $filename = $nombre . '.jpg';                      // Siempre .jpg para que coincida con getImagenUrl() en el frontend
                if (move_uploaded_file($imagen['tmp_name'], $uploadDir . $filename)) {
                    jsonResponse(["success" => true, "imagen" => $filename]);
                } else {
                    jsonResponse(["error" => "Error al guardar la imagen"], 500);
                }
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



        
        // COMPRAS - Registro de compras de productos por usuarios
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
                    Compra::crear($_SESSION['usuario_id'], $id_producto, $cantidad, $precio_unitario);
                    jsonResponse(["success" => true]);
                }
                jsonResponse(["error" => "Datos inválidos"], 400);
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
                        "nombre_material" => $inc->getNombreMaterial(),
                        "id_tag_material" => $inc->getIdTagMaterial(),
                        "ubicacion" => $inc->getUbicacion()
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
            } elseif ($method === 'PUT' && isset($path[2])) {   // PUT /api/incidencias/{id} - Actualizar descripción/prioridad/estado
                $descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : null;
                $prioridad = $data['prioridad'] ?? null;
                $estado = $data['estado'] ?? null;
                Incidencia::actualizar($path[2], $prioridad, $estado, $descripcion);
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

        
        // RESUMEN - Dashboard de administración (GET /api/resumen)
       
        case 'resumen':
            requireSession();
            if ($method === 'GET') {
                $conexion = Conexion::conectar();

                // Conteo de incidencias por estado
                $stmt = $conexion->query("SELECT estado_inc, COUNT(*) as total FROM incidencias GROUP BY estado_inc");
                $incidencias = ['abierta' => 0, 'en_proceso' => 0, 'resuelta' => 0];
                while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $incidencias[$fila['estado_inc']] = intval($fila['total']);
                }

                // Productos con stock por debajo del mínimo
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

                // Conteo de máquinas por estado
                $stmt = $conexion->query("SELECT estado, COUNT(*) as total FROM material WHERE tipo = 'maquina' GROUP BY estado");
                $maquinas = [];
                while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $maquinas[$fila['estado']] = intval($fila['total']);
                }
                $total_maquinas = array_sum($maquinas);

                // Gasto total de todos los usuarios
                $stmt = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM compras");
                $total_gastado = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

                // Desglose por usuario
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
