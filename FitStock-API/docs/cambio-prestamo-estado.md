# Cambio Principal en la API: Sistema de Estados en Préstamos

## Resumen

Se añadió el campo `estado` a la tabla `prestamos` para gestionar el ciclo de vida completo de un préstamo: pendiente → activo → pendiente_devolucion → devuelto.

## Archivos modificados

### 1. `config/database.sql` — Línea 40

Se añadió la columna `estado` en la tabla `prestamos`:

```sql
estado VARCHAR(30) DEFAULT 'activo',
```

### 2. `models/Prestamo.php`

**Nueva propiedad y constructor** (líneas 10, 14):
- `private $estado;`
- Constructor acepta `$estado = 'activo'` como parámetro opcional

**Nuevos métodos añadidos:**

| Método | Línea | Función |
|---|---|---|
| `obtenerPendientes($tipo)` | 41 | Filtra préstamos por estado (WHERE p.estado = ?) |
| `aprobar($id_prestamo)` | 69 | Cambia estado a 'activo' |
| `confirmarDevolucion($id_prestamo)` | 75 | Cambia a 'devuelto' y registra fecha |
| `getEstado()` | 98 | Getter del estado |

**Método modificado:**
- `crear($id_usuario, $id_material, $fecha_devolucion, $estado)` — línea 53: ahora acepta `$estado` como cuarto parámetro y lo incluye en el INSERT

**Método `devolver()`** — línea 63: ya no actualiza fecha_devolucion directamente, ahora cambia estado a 'pendiente_devolucion'

### 3. `api/index.php` — Caso `prestamos` (líneas 591-657)

**GET /api/prestamos** (líneas 596-618):
- Nueva variable `$estadoFiltro = $_GET['estado']` para filtrar por estado desde URL
- Si hay filtro → `Prestamo::obtenerPendientes($estadoFiltro)`
- Si es cliente → solo sus préstamos con `$_SESSION['usuario_id']`
- Admin/entrenador → pueden filtrar por `?id_usuario=`
- Respuesta incluye `"estado" => $p->getEstado()` (línea 615)

**POST /api/prestamos** (líneas 619-633):
- Cliente: `$estado = 'pendiente'` (línea 622)
- Admin/entrenador: `$estado = $data['estado'] ?? 'pendiente'` (línea 625)
- Llamada a `Prestamo::crear(...)` incluye `$estado` como cuarto parámetro (línea 630)

**PUT /api/prestamos/{id}/aprobar** (líneas 634-637):
- Nueva sub-ruta que llama a `Prestamo::aprobar($path[2])`

**PUT /api/prestamos/{id}/confirmar-devolucion** (líneas 638-640):
- Nueva sub-ruta que llama a `Prestamo::confirmarDevolucion($path[2])`

## Flujo del ciclo de vida

```
[Cliente] Crea préstamo → estado = 'pendiente'
                              ↓
[Admin] Aprueba           → estado = 'activo'
                              ↓
[Cliente] Marca devolución → estado = 'pendiente_devolucion'
                              ↓
[Admin] Confirma devolución → estado = 'devuelto' + fecha_devolucion
```

## Datos de ejemplo en database.sql (líneas 165-169)

```sql
INSERT INTO prestamos (id_usuario, id_material, fecha_inicio, fecha_devolucion, estado) VALUES
(2, 1, '2026-05-01 10:00:00', '2026-05-05', 'devuelto'),
(3, 2, '2026-05-10 12:00:00', '2026-05-15', 'devuelto'),
(3, 3, '2026-05-14 09:00:00', NULL, 'activo'),
(2, 5, '2026-05-16 11:00:00', NULL, 'pendiente');
```
