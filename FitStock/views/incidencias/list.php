<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Incidencias</title>
</head>
<body>
    <h2>Incidencias</h2>

    <a href="index.php?view=incidencias_crear">Añadir Incidencia</a>
    <table border="1">
            <tr>
                <th>ID</th><th>Material</th><th>Usuario</th><th>Descripción</th><th>Prioridad</th><th>Estado</th><th>Acciones</th>
            </tr>
        
            <?php foreach ($incidencias as $incidencia): ?>
                <tr>
                    <td><?= $incidencia->getId(); ?></td>
                    <td><?= $incidencia->getIdMaterial(); ?></td>
                    <td><?= $incidencia->getIdUser(); ?></td>
                    <td><?= $incidencia->getDescripcion(); ?></td>
                    <td><?= $incidencia->getPrioridad(); ?></td>
                    <td><?= $incidencia->getEstado(); ?></td>
                    <td><a href="index.php?view=incidencias_eliminar&id=<?= $incidencia->getId(); ?>" 
                           onclick="return confirm('¿Desea eliminarla?')">
                           Eliminar</a></td>
                </tr>
            <?php endforeach; ?>
    </table>
    <a href="index.php">Volver al inicio</a>
</body>
</html>