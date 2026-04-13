<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Préstamos</title>
</head>
<body>
    <h2>Préstamos</h2>

    <a href="index.php?view=prestamos_crear">Añadir Préstamo</a>
    <table border="1">
            <tr>
                <th>ID</th><th>Usuario</th><th>Material</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Acciones</th>
            </tr>
        
            <?php foreach ($prestamos as $prestamo): ?>
                <tr>
                    <td><?= $prestamo->getId(); ?></td>
                    <td><?= $prestamo->getIdUsuario(); ?></td>
                    <td><?= $prestamo->getIdMaterial(); ?></td>
                    <td><?= $prestamo->getFechaPrestamo(); ?></td>
                    <td><?= $prestamo->getFechaDevolucion() ? $prestamo->getFechaDevolucion() : 'Pendiente'; ?></td>
                    <td>
                        <a href="index.php?view=prestamos_eliminar&id=<?= $prestamo->getId(); ?>" 
                           onclick="return confirm('¿Desea eliminar el registro?')">
                           Eliminar</a></td>
                </tr>
            <?php endforeach; ?>
    </table>
    
    <a href="index.php">Volver al inicio</a>
</body>
</html>