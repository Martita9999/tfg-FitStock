<?php
// FitStock/register.php
session_start();

/**
 * 1. Corregimos la ruta de la conexión. 
 * Usamos conexion.php que está en tu raíz.
 */
require_once "conexion.php"; 

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!empty($_POST['nombre']) && !empty($_POST['email']) && !empty($_POST['password'])) {
        
        $nombre = trim($_POST['nombre']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Ciframos la contraseña para seguridad
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            /**
             * 2. Usamos tu método estático Conexion::conectar().
             */
            $conexion = Conexion::conectar();

            // 3. Verificamos que la tabla sea 'usuarios'.
            $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, 'cliente')";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$nombre, $email, $password_hash]);

            $success = "Usuario registrado correctamente. ¡Ya puedes iniciar sesión!";

        } catch (PDOException $e) {
            // Código 23000 suele ser por email duplicado (Unique Key)
            if ($e->getCode() == 23000) { 
                $error = "El correo ya está registrado";
            } else {
                $error = "Error en el registro: " . $e->getMessage();
            }
        }
    } else {
        $error = "Todos los campos son obligatorios";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - FitStock</title>
    <link rel="stylesheet" href="public/style.css">
</head>
<body>
<main class="contenedor-registro">
    <h2>Crear Cuenta</h2>

    <?php if ($error): ?>
        <p class="alerta-error" style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="alerta-exito" style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="post" action="" class="formulario">
        <label>Nombre Completo:</label><br>
        <input type="text" name="nombre" required placeholder="Ej: Juan Pérez"><br><br>

        <label>Correo Electrónico:</label><br>
        <input type="email" name="email" required placeholder="email@ejemplo.com"><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="password" required placeholder="Mínimo 6 caracteres"><br><br>

        <button type="submit" class="btn-principal">Registrarse</button>
    </form>

    <p><a href="index.php?c=usuario&a=login">¿Ya tienes cuenta? Inicia sesión aquí</a></p>
</main>
</body>
</html>