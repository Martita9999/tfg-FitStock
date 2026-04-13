<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
</head>
<body>
    <h2>Crear Usuario</h2>
    <form method="post" action="index.php?view=usuarios_guardar">
        <p><label>Nombre:</label><br>
            <input type="text" name="nombre" required></p>

        <p><label>Email:</label><br>
            <input type="email" name="email" required></p>

        <p><label>Contraseña:</label><br>
            <input type="password" name="password" required></p>

        <p><label>Rol:</label><br>
            <select name="rol">
                <option value="cliente">Cliente</option>
                <option value="admin">Administrador</option>
                <option value="entrenador">Entrenador</option>
            </select></p>

        <button type="submit">Guardar</button>
    </form>
   
    <a href="index.php?view=usuarios_listar">Volver al listado</a>
</body>
</html>