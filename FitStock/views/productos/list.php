<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Productos</title>
</head>
<body>
    <h2>Productos</h2>

    <a href="index.php?view=productos_crear">Añadir Producto</a>
    <table border="1">
            <tr>
                <th>ID</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Acciones</th>
            </tr>
        
            <?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?= $producto->getId(); ?></td>
                    <td><?= $producto->getNombre(); ?></td>
                    <td><?= $producto->getCategoria(); ?></td>
                    <td><?= $producto->getPrecio(); ?>€</td>
                    <td><?= $producto->getStock(); ?> unidades</td>
                    <td>
                        <a href="index.php?view=productos_eliminar&id=<?= $producto->getId(); ?>" 
                           onclick="return confirm('¿Desea eliminar el producto?')">
                           Eliminar</a></td>
                </tr>
            <?php endforeach; ?>
    </table>
    
    <a href="index.php">Volver al inicio</a>
</body>
</html>