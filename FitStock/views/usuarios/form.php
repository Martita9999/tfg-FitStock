<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
</head>
<body>
    <h2>Crear Usuario</h2>
    <form method="post" action="index.php?c=Usuario&a=guardar">
        <p>Nombre:<br><input type="text" name="nombre" required></p>
        <p>Email:<br><input type="email" name="email" required></p>
        <p>Contraseña:<br><input type="password" name="password" required></p>
        <p>Rol:<br>
            <select name="rol">
                <option value="cliente">Cliente</option>
                <option value="admin">Administrador</option>
                <option value="entrenador">Entrenador</option>
            </select>
        </p>
        <button type="submit">Guardar Usuario</button>
    </form>
    <br>
    <a href="index.php?c=Usuario&a=listar">Volver al listado</a>
</body>
</html>