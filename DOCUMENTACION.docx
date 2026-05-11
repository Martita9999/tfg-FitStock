# Documentación Técnica — FitStock

## Historial de Cambios, Errores y Soluciones

---

# 1. Simplificación de Estados de Material

## Problema original
El sistema tenía 6 estados para materiales/máquinas: `operativo`, `en_proceso`, `saliendo`, `averiado`, `en_reparacion`, `baja`. Esto generaba confusión porque:
- Coexistían estados similares (`en_proceso` vs `en_reparacion`)
- `saliendo` y `baja` apenas se usaban
- Las incidencias usaban otra nomenclatura (`abierta`, `en_proceso`, `resuelta`)

## Solución
Reducir a 3 estados universales:
| Estado | Significado |
|---|---|
| `operativo` | Funcionando correctamente |
| `averiado` | Con avería reportada |
| `en_reparacion` | En proceso de reparación |

### Archivos modificados
- `FitStock-API/config/database.sql` — actualizado el ENUM
- `FitStock-APP/src/app/components/material-list/material-list.ts` — `estadosDisponibles` simplificado
- `FitStock-APP/src/app/components/material-list/material-list.html` — classes de badges
- `FitStock-APP/src/app/components/material-list/material-list.css` — estilos de badges

### SQL ejecutado
```sql
ALTER TABLE material MODIFY COLUMN estado ENUM('operativo','averiado','en_reparacion') NOT NULL DEFAULT 'operativo';
```

---

# 2. Añadir fecha_resolucion a Incidencias

## Problema
Las incidencias resueltas no tenían fecha de resolución, imposibilitando ver cuándo se solucionó cada una.

## Solución
Añadir columna `fecha_resolucion` a la tabla `incidencias` y actualizarla automáticamente al marcar como "Resuelta".

### Archivos modificados
- `FitStock-API/models/Incidencia.php`:
  - Añadida propiedad `$fecha_resolucion`
  - Modificado constructor
  - `actualizar()`: `fecha_resolucion = IF(? = 'resuelta', NOW(), NULL)`
- `FitStock-API/api/index.php`: incluido en respuesta JSON
- `FitStock-APP/src/app/interfaces/app.interfaces.ts`: añadido `fecha_resolucion`

### SQL ejecutado
```sql
ALTER TABLE incidencias ADD COLUMN fecha_resolucion DATETIME DEFAULT NULL AFTER created_at;
```

### Error detectado
Al marcar "Resuelta", la fecha se guardaba correctamente pero al listar incidencias, la API devolvía error si la columna no existía en BD.

---

# 3. Error: PHP Undefined Array Key al leer columnas inexistentes

## Problema
El modelo `Incidencia.php` original accedía a `$fila['fecha_resolucion']` sin verificar si la columna existía. Si la migración SQL no se había ejecutado, PHP lanzaba un warning que rompía la respuesta JSON (500 Internal Server Error). Como el frontend de Angular no manejaba el error, la lista de incidencias aparecía vacía.

## Solución
Implementar detección dinámica de columnas:

### Versión 1 (descartada): `safeQuery()` con `str_replace`
```php
private static function safeQuery($conexion, $sql, $params = []) {
    try {
        // intenta con fecha_resolucion
    } catch (Exception $e) {
        // fallback reemplazando fecha_resolucion por NULL
        $fallback = str_replace(
            ['fecha_resolucion, ', ', fecha_resolucion', 'fecha_resolucion'],
            ['NULL as fecha_resolucion, ', '', 'NULL as fecha_resolucion'],
            $sql
        );
    }
}
```
**Problema:** El `str_replace` era frágil y no manejaba `created_at`.

### Versión 2 (definitiva): `columnasExistentes()`
```php
private static function columnasExistentes($conexion) {
    $existentes = [];
    foreach (['created_at', 'fecha_resolucion'] as $col) {
        try {
            $conexion->query("SELECT $col FROM incidencias LIMIT 0");
            $existentes[] = "i.$col";
        } catch (Exception $e) {}
    }
    return $existentes;
}
```
Las consultas SELECT se construyen dinámicamente incluyendo solo las columnas que existen en la BD.

### Archivo final
`FitStock-API/models/Incidencia.php`

---

# 4. Error: Incidencias con JOIN a material mostraban datos incorrectos

## Problema
Al añadir la columna "Máquina" en la lista de incidencias, la celda mostraba `inc.descripcion` (la descripción de la incidencia) en lugar del nombre del material.

## Causa
Error en la edición del HTML: se añadió la columna "Máquina" en el `<th>` pero el `<td>` correspondiente seguía mostrando `inc.descripcion` en lugar de `inc.nombre_material`.

## Solución
Corregir el mapeo en el HTML de incidencia-list:
```html
<td><strong>{{ inc.nombre_material || '—' }}</strong></td>
```

## Archivo
`FitStock-APP/src/app/components/incidencia-list/incidencia-list.html`

---

# 5. Error: Parámetros incorrectos en POST /api/materiales

## Problema
El endpoint `POST /api/materiales` llamaba a `Material::crear()` con solo 4 argumentos:
```php
Material::crear($nombre, $descripcion, $estado, $qr);
```
Pero el método esperaba 6:
```php
public static function crear($nombre_equipo, $descripcion, $estado, $tipo, $qr_identificador, $ultima_rev = null)
```
El cuarto argumento `$qr` se asignaba al parámetro `$tipo`, causando que:
- El tipo del material se guardara incorrectamente (como un código QR en lugar de 'maquina'/'prestable')
- La creación de materiales nuevos fallaba silenciosamente

## Solución
Añadir el parámetro `$tipo` y pasar todos los argumentos correctamente:
```php
$tipo = $data['tipo'] ?? 'maquina';
$qr = trim($data['qr'] ?? '');
Material::crear($nombre, $descripcion, $estado, $tipo, $qr);
```

## Archivo
`FitStock-API/api/index.php`

---

# 6. Error: El DELETE de incidencias modificaba el estado del material

## Problema
Al borrar una incidencia, el código original hacía:
```php
// Si no quedan incidencias abiertas para ese material, vuelve a operativo
if ($stmt->fetchColumn() == 0) {
    $stmt2 = $conexion->prepare("UPDATE material SET estado = 'operativo' WHERE id_material = ?");
    $stmt2->execute([$idMaterial]);
}
```
Esto causaba que al eliminar la última incidencia de una máquina averiada, ésta volvía a "operativo" automáticamente, sin importar su estado real.

## Solución
Eliminar toda la lógica que modifica el material al borrar una incidencia. Ahora DELETE solo borra el registro de incidencia:
```php
Incidencia::eliminar($path[2]);
jsonResponse(["success" => true]);
```

## Archivo
`FitStock-API/api/index.php`

---

# 7. Error: Máquinas no aparecían tras migración

## Problema
Después de ejecutar migraciones, las máquinas con estados antiguos (`en_proceso`, `saliendo`, `baja`) desaparecían porque MySQL convertía esos valores a cadena vacía (`''`) al cambiar el ENUM.

## Síntoma
- La API devolvía las máquinas con `estado: ""` (vacío)
- El frontend las filtraba correctamente (`m.estado !== 'operativo'`) pero no tenían badge visible
- El usuario pensaba que "no salían"

## Solución
1. Proporcionar script SQL para resetear las máquinas (`reset_maquinas.sql`)
2. El usuario ejecutó `TRUNCATE material` y re-insertó los datos de ejemplo

## Archivo
`FitStock-API/config/reset_maquinas.sql`

---

# 8. Auto-generación de etiquetas QR

## Función implementada
Al crear una máquina sin especificar QR, el sistema genera automáticamente un identificador único basado en el nombre:

### Algoritmo (`Material.php`)
```php
private static function generarQr($nombre, $conexion) {
    // Extrae prefijo: primeras 3 letras de cada palabra significativa
    // "Cinta de correr" → "CIN"
    // "Pulsador pecho" → "PUL"
    
    // Busca el último número usado para ese prefijo
    // "CIN-010" → siguiente: "CIN-011"
}
```

## Archivo
`FitStock-API/models/Material.php`

---

# 9. Añadir campo Ubicación a Máquinas

## Funcionalidad
Nuevo campo opcional `ubicacion` en la tabla `material` para indicar dónde está situada cada máquina en el gimnasio.

### Cambios realizados

**Base de datos:**
```sql
ALTER TABLE material ADD COLUMN ubicacion VARCHAR(255) DEFAULT NULL AFTER descripcion;
```

**Backend:**
- `FitStock-API/models/Material.php`: nueva propiedad, getter, modificado constructor y métodos `crear`/`actualizar`
- `FitStock-API/api/index.php`: incluido en respuesta GET y en parámetros POST/PUT

**Frontend:**
- `FitStock-APP/src/app/interfaces/app.interfaces.ts`: añadido `ubicacion?: string`
- `FitStock-APP/src/app/services/materiales.service.ts`: tipos actualizados
- `FitStock-APP/src/app/components/material-list/material-list.ts`: `editData` y `newMaterial` con `ubicacion`
- `FitStock-APP/src/app/components/material-list/material-list.html`: columna "Ubicación" en tabla, campos en modales crear/editar

---

# 10. Mejoras en la interfaz de Incidencias

## Cambios visuales

### Columnas
- Eliminada columna "ID" (innecesaria para el usuario)
- Añadida columna "Máquina" con el nombre del material asociado
- Añadidas columnas "Inicio" (fecha creación) y "Fin" (fecha resolución)

### Secciones
- **Incidencias Activas**: estados `abierta` (Averiado, rojo) y `en_proceso` (En Reparación, naranja), con botones ✏️ 🗑️
- **Incidencias Resueltas**: añadidos botones ✏️ para poder reactivarlas

### Modal de crear
- Cambiado label "Material" → "Máquina"
- Select muestra nombre + QR: `Cinta de correr (CIN-001)`

## Archivos
- `FitStock-APP/src/app/components/incidencia-list/incidencia-list.html`
- `FitStock-APP/src/app/components/incidencia-list/incidencia-list.ts`
- `FitStock-APP/src/app/components/incidencia-list/incidencia-list.css`

---

# 11. Mejoras en la interfaz de Máquinas

## Cambios visuales

### Tablas
- Eliminada columna "ID"
- Añadida columna "Ubicación"
- QR se muestra como tag junto al nombre: `Cinta de correr [CIN-001]`

### Secciones
- **Incidencias Activas** (máquinas no operativas): muestra incidencia activa en tiempo real
- **Máquinas Operativas**: listado simple

### Modal de editar
- Añadido campo "Ubicación"
- Cambiado "Descripción" → "Descripción / Notas"
- Header muestra nombre y QR: `Cinta de correr (CIN-001)`

## Archivos
- `FitStock-APP/src/app/components/material-list/material-list.html`
- `FitStock-APP/src/app/components/material-list/material-list.ts`
- `FitStock-APP/src/app/components/material-list/material-list.css`

---

# 12. Gestión de errores comunes

## Error 1: `HttpErrorResponse` en consola
**Causa:** La API devuelve 500 porque falta la columna `fecha_resolucion` en la BD.
**Solución:** Ejecutar `ALTER TABLE incidencias ADD COLUMN fecha_resolucion ...`

## Error 2: "No sale nada" en incidencias
**Causa:** El PHP crashea al acceder a `$fila['fecha_resolucion']` si la columna no existe.
**Solución:** Implementar `columnasExistentes()` para detectar columnas dinámicamente.

## Error 3: Al editar estado sale "Error al actualizar"
**Causa:** `actualizar()` intenta hacer `SET fecha_resolucion = IF(...)` pero la columna no existe.
**Solución:** Añadir try-catch alrededor de la columna `fecha_resolucion` en `actualizar()`.

## Error 4: FOREIGN KEY al hacer TRUNCATE
**Causa:** La tabla `material` tiene FK desde `prestamos` e `incidencias`.
**Solución:** Usar `SET FOREIGN_KEY_CHECKS = 0` antes del TRUNCATE.

## Error 5: Las máquinas no aparecen tras migración
**Causa:** ENUM cambiado, valores antiguos convertidos a cadena vacía.
**Solución:** TRUNCATE y re-insertar datos de ejemplo.

---

# Resumen de archivos modificados

## Backend (PHP)

| Archivo | Cambios |
|---|---|
| `FitStock-API/models/Incidencia.php` | Añadidos `created_at`, `fecha_resolucion`, `nombre_material`; detección dinámica de columnas; JOIN con material |
| `FitStock-API/models/Material.php` | Añadido `ubicacion`; auto-generación de QR |
| `FitStock-API/api/index.php` | Corregido POST materiales; añadido `nombre_material` y `ubicacion` a respuestas; simplificado DELETE incidencias |

## Frontend (Angular)

| Archivo | Cambios |
|---|---|
| `src/app/interfaces/app.interfaces.ts` | Añadidos `ubicacion` (Material), `created_at`/`nombre_material` (Incidencia) |
| `src/app/services/*.service.ts` | Tipos actualizados con nuevos campos |
| `src/app/components/material-list/*` | 3 estados, ubicación, QR tag, sin ID, modales actualizados |
| `src/app/components/incidencia-list/*` | Dos secciones (activas/resueltas), columna Máquina, botón editar en resueltas, sin ID |

## Base de datos

| Script | Propósito |
|---|---|
| `config/database.sql` | Esquema completo actualizado |
| `config/reset_maquinas.sql` | Reset de máquinas + datos de ejemplo |

---

*Documentación generada el 11/05/2026*
