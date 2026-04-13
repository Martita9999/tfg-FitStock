<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Incidencia</title>
</head>
<body>
    <h2>Crear Incidencia</h2>
    
    <form method="post" action="index.php?view=incidencias_guardar">
        <label>Material Afectado:</label><br>
        <select name="id_material" required>
            <option value="">Selecciona el material</option>
            <?php foreach ($materiales as $mat): ?>
                <option value="<?= $mat->getId(); ?>">
                    <?= $mat->getNombre(); ?> (ID: <?= $mat->getId(); ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label>ID del Usuario:</label><br>
        <input type="number" name="id_user_rep" required placeholder="ID de usuario">

        <label>Descripción del problema:</label><br>
        <textarea name="descripcion" rows="4" cols="40" required placeholder="Lo que ocurre..."></textarea>

        <label>Importancia:</label><br>
        <select name="importancia">
            <option value="baja">Baja</option>
            <option value="media" selected>Media</option>
            <option value="alta">Alta</option>
        </select>

        <label>Estado de incidencia:</label><br>
        <select name="estado_inc">
            <option value="abierta">Abierta</option>
            <option value="en_proceso">En Proceso</option>
            <option value="resuelta">Resuelta</option>
        </select>

        <button type="submit">Guardar Incidencia</button>
    </form>

    <a href="index.php?view=incidencias_listar">Volver al listado</a>
</body>
</html>