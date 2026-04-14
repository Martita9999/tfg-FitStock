<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FitStock - Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

    <h2 class="mb-4">Usuarios</h2>

    <div class="mb-3">
        <a href="index.php?c=usuario&a=crear" class="btn btn-primary">Añadir Usuario</a>
    </div>

    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($usuarios)): ?>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= $u->getId(); ?></td>
                    <td><?= $u->getNombre(); ?></td>
                    <td><?= $u->getEmail(); ?></td>
                    <td><?= $u->getRol(); ?></td>
                    <td>
                        <a href="index.php?c=usuario&a=eliminar&id=<?= $u->getId(); ?>" 
                           class="text-danger" 
                           onclick="return confirm('¿Eliminar?')">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="mt-3">
        <a href="index.php" class="btn btn-secondary text-white">Volver al inicio</a>
    </div>

</body>
</html>