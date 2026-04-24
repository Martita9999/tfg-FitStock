<?php
session_start();

// 1. Ruta corregida (asumiendo que este archivo está en /api/ y conexion.php en la raíz)
require_once __DIR__ . "/../conexion.php"; 

header('Content-Type: application/json');

// 2. Captura de datos JSON
$data = json_decode(file_get_contents("php://input"), true);

// Soporte también para formularios tradicionales por si acaso
$email = $data['email'] ?? ($_POST['email'] ?? null);
$password = $data['password'] ?? ($_POST['password'] ?? null);

if (!empty($email) && !empty($password)) {
    
    $email = trim($email);

    try {
        // 3. Uso de la Clase con C mayúscula
        $conexion = Conexion::conectar(); 

        // 4. Consulta a la tabla 'usuarios'
        $sql = "SELECT id_usuario, nombre, password_hash, rol FROM usuarios WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$email]);
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 5. Verificación de contraseña
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            $_SESSION['usuario_id']     = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol']    = $usuario['rol'];

            echo json_encode([
                "success" => true, 
                "message" => "Login correcto",
                "rol" => $usuario['rol']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Email o contraseña incorrectos"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error de conexión en el servidor"]);
    }
} else {
    echo json_encode(["error" => "Todos los campos son obligatorios"]);
}