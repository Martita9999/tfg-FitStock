<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Material</title>
</head>
<body>
    <h2>Material Deportivo</h2>

    <a href="index.php?view=material_crear">Añadir Material</a>
    <table border="1">
            <tr>
                <th>ID</th><th>Nombre del Equipo</th><th>Estado</th><th>Código QR</th><th>Última Revisión</th><th>Acciones</th>
            </tr>
       
            <?php foreach ($materiales as $material): ?>
                <tr>
                    <td><?= $item->getId(); ?></td>
                    <td><?= $item->getNombre(); ?></td>
                    <td><?= $item->estaOperativo() ? "  Operativo" : " " . $item->getEstado(); ?></td>
                    <td><code><?= $item->getQr(); ?></code></td>
                    <td><?= $item->getUltimaRev() ?? 'No revisado'; ?></td>
                    <td><a href="index.php?view=material_eliminar&id=<?= $item->getId(); ?>" 
                           onclick="return confirm('¿Eliminar el material?')">
                           Eliminar</a></td>
                </tr>
            <?php endforeach; ?>
    </table>
    <a href="index.php">Volver al Dashboard</a>
</body>
</html>