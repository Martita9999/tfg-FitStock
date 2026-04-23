<?php
session_start();

// 1. Ruta corregida para acceder a la conexión desde la carpeta /api/
require_once "../conexion.php"; 

header('Content-Type: application/json');

// 2. Captura de datos JSON (enviados mediante fetch o axios)
$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['email']) && !empty($data['password'])) {
    
    $email = trim($data['email']);
    $password = $data['password'];

    try {
        // 3. Uso correcto de tu método de conexión
        $conexion = Conexion::conectar(); 

        // 4. Consulta a la tabla 'usuarios'
        $sql = "SELECT id_usuario, nombre, password_hash, rol FROM usuarios WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$email]);
        
        // Usamos FETCH_ASSOC para obtener un array limpio
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 5. Verificación de contraseña usando el hash almacenado
        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            $_SESSION['usuario_id']     = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol']    = $usuario['rol'];

            echo json_encode(["success" => true, "message" => "Login correcto"]);
        } else {
            echo json_encode(["error" => "Email o contraseña incorrectos"]);
        }
    } catch (Exception $e) {
        echo json_encode(["error" => "Error de conexión en el servidor"]);
    }
} else {
    // Esto es lo que ves si accedes directamente desde el navegador
    echo json_encode(["error" => "Todos los campos son obligatorios"]);
}
?>