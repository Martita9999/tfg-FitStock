# Sistema de Préstamos — Documentación completa

## 1. Estructura de la tabla en BD (`config/database.sql` ~línea 40)

```
prestamos
├── id_prestamo       INT AUTO_INCREMENT PRIMARY KEY
├── id_usuario        INT (FK → usuarios)
├── id_material       INT (FK → material)
├── fecha_inicio      DATETIME DEFAULT CURRENT_TIMESTAMP
├── fecha_devolucion  DATETIME NULL
└── estado            VARCHAR(30) DEFAULT 'activo'
```

**Captura**: Mostrar la tabla `prestamos` en phpMyAdmin o el SQL de creación (~línea 38-44 de `database.sql`).

---

## 2. Modelo `models/Prestamo.php` — Métodos

| Método | Línea | Descripción | SQL generado |
|---|---|---|---|
| `obtenerTodos($id_usuario)` | 25 | Lista todos los préstamos, opcionalmente filtrados por usuario | `SELECT ... JOIN ... ORDER BY fecha_inicio DESC` |
| `obtenerPendientes($tipo)` | 41 | Filtra por estado (`pendiente`, `activo`, etc.) | `SELECT ... WHERE p.estado = ?` |
| `crear($id_usuario, $id_material, $fecha_devolucion, $estado)` | 53 | Crea un nuevo préstamo con estado inicial | `INSERT INTO prestamos ...` |
| `devolver($id_prestamo)` | 63 | Cliente solicita devolución → estado = `pendiente_devolucion` | `UPDATE ... SET estado = 'pendiente_devolucion'` |
| `aprobar($id_prestamo)` | 69 | Admin aprueba → estado = `activo` | `UPDATE ... SET estado = 'activo'` |
| `confirmarDevolucion($id_prestamo)` | 75 | Admin confirma devolución → estado = `devuelto` + fecha | `UPDATE ... SET estado = 'devuelto', fecha_devolucion = CURRENT_DATE()` |
| `actualizar($id_prestamo, $fecha_devolucion)` | 81 | Cambia fecha de devolución prevista | `UPDATE ... SET fecha_devolucion = ?` |
| `eliminar($id)` | 87 | Borra un préstamo | `DELETE FROM prestamos WHERE id_prestamo = ?` |

**Getters** (líneas 93-102): `getId()`, `getIdUsuario()`, `getIdMaterial()`, `getFecha()`, `getFechaDevolucion()`, `getEstado()`, `getUsuarioNombre()`, `getMaterialNombre()`, `estaDevuelto()`

**Captura**: El contenido completo de `Prestamo.php` con los métodos numerados.

---

## 3. API Endpoints (`api/index.php` ~líneas 607-676)

### 3.1 GET /api/prestamos (líneas 612-634)
- **Filtro por estado**: `?estado=pendiente` → llama a `obtenerPendientes()`
- **Cliente**: solo ve sus préstamos (`$_SESSION['usuario_id']`)
- **Admin/entrenador**: pueden filtrar por `?id_usuario=`
- **Respuesta**: JSON con id, id_usuario, id_material, usuario, material, fecha, devolucion, estado
- **Captura**: La sección `case 'prestamos':` con el bloque `if ($method === 'GET')`

### 3.2 POST /api/prestamos (líneas 635-652)
- **Cliente**: `$estado = 'pendiente'` automático, usa su session id
- **Admin/entrenador**: pueden elegir `$data['estado']` y `$data['id_usuario']`
- **Validación**: comprueba que el material existe (`Material::obtenerPorId()`)
- **Campos**: `id_material` (requerido), `fecha_devolucion` (opcional)
- **Captura**: El bloque `elseif ($method === 'POST')` con la validación

### 3.3 PUT /api/prestamos/{id}/aprobar (líneas 653-656)
- Cambia estado a `activo`
- Solo accesible desde frontend de admin/entrenador
- **Captura**: Las rutas con `$path[3] === 'aprobar'`

### 3.4 PUT /api/prestamos/{id}/confirmar-devolucion (líneas 657-659)
- Cambia estado a `devuelto` y registra `fecha_devolucion`
- Solo admin/entrenador
- **Captura**: La ruta `$path[3] === 'confirmar-devolucion'`

### 3.5 PUT /api/prestamos/{id} (líneas 663-671)
- Si envía `fecha_devolucion` → actualiza la fecha (`actualizar()`)
- Si no envía fecha → el cliente marca para devolución (`devolver()` → estado `pendiente_devolucion`)
- **Captura**: El bloque `elseif ($method === 'PUT' && isset($path[2]))`

### 3.6 DELETE /api/prestamos/{id} (líneas 672-675)
- Elimina préstamo. Los clientes tienen denegado el DELETE (línea 609-611).
- **Captura**: El bloque `elseif ($method === 'DELETE')`

---

## 4. Ciclo de vida del estado

```
[Crea cliente]      → estado = 'pendiente'
                          ↓
[Aprueba admin]     → estado = 'activo'
                          ↓
[Cliente devuelve]  → estado = 'pendiente_devolucion'
                          ↓
[Admin confirma]    → estado = 'devuelto' + fecha_devolucion
```

**Captura**: Diagrama de flujo del proceso.

---

## 5. Frontend — Vistas asociadas

### 5.1 Vista cliente (prestamos-list)
- Botón "Solicitar préstamo" → POST con `estado = 'pendiente'`
- Lista de préstamos con columna "Estado"
- Botón "Devolver" en los activos → PUT sin fecha → `devolver()`
- **Captura**: Pantalla del cliente con sus préstamos y el botón Devolver

### 5.2 Vista admin/entrenador
- Lista completa con filtros por estado
- Botón "Aprobar" en los pendientes → PUT `/aprobar`
- Botón "Confirmar devolución" en los `pendiente_devolucion` → PUT `/confirmar-devolucion`
- **Captura**: Pantalla de admin con los botones de acción

---

## 6. Datos de ejemplo (`config/database.sql` ~líneas 165-169)

```sql
INSERT INTO prestamos (id_usuario, id_material, fecha_inicio, fecha_devolucion, estado) VALUES
(2, 1, '2026-05-01 10:00:00', '2026-05-05', 'devuelto'),
(3, 2, '2026-05-10 12:00:00', '2026-05-15', 'devuelto'),
(3, 3, '2026-05-14 09:00:00', NULL, 'activo'),
(2, 5, '2026-05-16 11:00:00', NULL, 'pendiente');
```

**Captura**: Los INSERTs en `database.sql`.

---

## 7. Guía de capturas para la memoria

| # | Qué capturar | Dónde |
|---|---|---|
| 1 | Tabla `prestamos` SQL | `database.sql` líneas 38-44 |
| 2 | Clase Prestamo con métodos | `Prestamo.php` completo (103 líneas) |
| 3 | Endpoint GET | `index.php` líneas 607-634 |
| 4 | Endpoint POST + validación | `index.php` líneas 635-652 |
| 5 | Rutas de aprobar/confirmar | `index.php` líneas 653-662 |
| 6 | PUT devolver + DELETE | `index.php` líneas 663-676 |
| 7 | INSERTs de ejemplo | `database.sql` líneas 165-169 |
| 8 | Panel cliente (lista + botón Devolver) | Navegar como cliente a /admin/prestamos/activos |
| 9 | Panel admin (botones Aprobar y Confirmar) | Navegar como admin a /admin/prestamos/activos |
| 10 | Dashboard admin (card Préstamos Pendientes) | /admin/home |
