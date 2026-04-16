<?php
    session_start();
    require_once "config/database.php";

    $error = "";
    $success = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if (isset($_POST['nombre'], $_POST['email'], $_POST['password'])) {
            
            $nombre = trim($_POST['nombre']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];

           
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $conexion = obtenerConexion();

                $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, 'cliente')";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([$nombre, $email, $password_hash]);

                $success = "Usuario registrado correctamente";

            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // '23000' registrar un email que ya existe en la tabla usuarios
                    $error = "El correo ya está registrado";
                } else {
                    $error = "Error al registrar el usuario";
                }
            }
        } else {
            $error = "Todos los campos son obligatorios";
        }
    }
?>

<main>
     <h2>Registro</h2>
    <link rel="stylesheet" href="public/style.css">

    <?php 
        if ($error) {
            echo "<p style='color:red;'>" . htmlspecialchars($error) . "</p>";
        }
        if ($success) {
            echo "<p style='color:green;'>" . htmlspecialchars($success) . "</p>";
        }
    ?>

    <form method="post" action="" class="formulario">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Registrarse</button><br><br>
    </form>

    <p><a href="login.php"> Ya tengo cuenta</a></p>
</main>