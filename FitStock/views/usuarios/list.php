<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Usuarios</title>
</head>
<body>
    <h2>Usuarios</h2>

    <a href="index.php?view=usuarios_crear">Añadir Usuario</a>
    <table border="1">
            <tr>
                <th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Acciones</th>
            </tr>
        
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?= $usuario->getId(); ?></td>
                    <td><?= $usuario->getNombre(); ?></td>
                    <td><?= $usuario->getEmail(); ?></td>
                    <td><?= $usuario->getRol(); ?></td>
                    <td>
                        <a href="index.php?view=usuarios_eliminar&id=<?= $usuario->getId(); ?>" 
                           onclick="return confirm('¿Desea eliminar el usuario?')">
                           Eliminar</a></td>
                </tr>
            <?php endforeach; ?>
    </table>
 
    <a href="index.php">Volver al inicio</a>
</body>
</html>