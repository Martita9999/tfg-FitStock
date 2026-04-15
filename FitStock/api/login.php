<?php
    session_start();
    require_once "config/database.php";
   

    header('Content-Type: application/json');

    $data = json_decode(file_get_contents("php://input"), true);

    if (!empty($data['email']) && !empty($data['password'])) {
        
        $email = trim($data['email']);
        $password = $data['password'];

        $conexion = obtenerConexion();   

        $sql = "SELECT id_usuario, nombre, password_hash, rol FROM usuarios WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            $_SESSION['usuario_id']    = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol']    = $usuario['rol'];

            echo json_encode(["success" => true, "message" => "Login correcto"]);
        } else {
            echo json_encode(["error" => "Email o contraseña incorrectos"]);
        }
    } else {
        echo json_encode(["error" => "Todos los campos son obligatorios"]);
    }
?>