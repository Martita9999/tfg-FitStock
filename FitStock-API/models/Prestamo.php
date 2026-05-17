<?php
require_once __DIR__ . "/../conexion.php";

/* Prestamo: modelo para gestionar préstamos de material deportivo.
 * Los préstamos siguen un ciclo de vida: pendiente -> activo -> pendiente_devolucion -> devuelto.
 * Cada método estático ejecuta una consulta SQL parametrizada (segura contra inyección). */
class Prestamo {
    private $id_prestamo;
    private $id_usuario;
    private $id_material;
    private $fecha_inicio;
    private $fecha_devolucion;
    private $estado;                      // pendiente | activo | pendiente_devolucion | devuelto
    private $nombre_usuario;              // JOIN opcional para mostrar nombre en lugar de ID
    private $nombre_material;             // JOIN opcional para mostrar nombre del equipo

    public function __construct($id_prestamo, $id_usuario, $id_material, $fecha_inicio, $fecha_devolucion, $estado = 'activo', $nombre_usuario = '', $nombre_material = '') {
        $this->id_prestamo = $id_prestamo;
        $this->id_usuario = $id_usuario;
        $this->id_material = $id_material;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_devolucion = $fecha_devolucion;
        $this->estado = $estado;
        $this->nombre_usuario = $nombre_usuario;
        $this->nombre_material = $nombre_material;
    }

    /* obtenerTodos: lista todos los préstamos con JOIN a usuarios y material.
     * Si se pasa id_usuario, filtra solo los de ese usuario. Ordenados por fecha DESC. */
    public static function obtenerTodos($id_usuario = null) {
        $conexion = Conexion::conectar();
        $sql = "SELECT p.*, u.nombre as nombre_usuario, m.nombre_equipo FROM prestamos p LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario LEFT JOIN material m ON p.id_material = m.id_material";
        if ($id_usuario) {
            $sql .= " WHERE p.id_usuario = ?";
        }
        $sql .= " ORDER BY p.fecha_inicio DESC";
        $stmt = $conexion->prepare($sql);
        $stmt->execute($id_usuario ? [$id_usuario] : []);
        $prestamos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $prestamos[] = new Prestamo($fila['id_prestamo'], $fila['id_usuario'], $fila['id_material'], $fila['fecha_inicio'], $fila['fecha_devolucion'], $fila['estado'] ?? 'activo', $fila['nombre_usuario'], $fila['nombre_equipo']);
        }
        return $prestamos;
    }

    /* obtenerPendientes: filtra préstamos por estado concreto (pendiente, activo, pendiente_devolucion, devuelto).
     * Útil para las vistas de "Activos", "Pendientes de devolver", etc. */
    public static function obtenerPendientes($tipo = 'pendiente') {
        $conexion = Conexion::conectar();
        $sql = "SELECT p.*, u.nombre as nombre_usuario, m.nombre_equipo FROM prestamos p LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario LEFT JOIN material m ON p.id_material = m.id_material WHERE p.estado = ? ORDER BY p.fecha_inicio DESC";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$tipo]);
        $prestamos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $prestamos[] = new Prestamo($fila['id_prestamo'], $fila['id_usuario'], $fila['id_material'], $fila['fecha_inicio'], $fila['fecha_devolucion'], $fila['estado'] ?? 'activo', $fila['nombre_usuario'], $fila['nombre_equipo']);
        }
        return $prestamos;
    }

    /* crear: inserta un nuevo préstamo. El estado inicial lo define quien llama:
     * - Cliente: 'pendiente' (requiere aprobación admin)
     * - Admin/entrenador: 'activo' directo */
    public static function crear($id_usuario, $id_material, $fecha_devolucion = null, $estado = 'activo') {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO prestamos (id_usuario, id_material, fecha_devolucion, estado) VALUES (?, ?, ?, ?)");
        $resultado = $stmt->execute([$id_usuario, $id_material, $fecha_devolucion, $estado]);
        if ($resultado) {
            return $conexion->lastInsertId();
        }
        return false;
    }

    /* devolver: el cliente solicita devolución -> estado = pendiente_devolucion.
     * El admin deberá confirmar después con confirmarDevolucion(). */
    public static function devolver($id_prestamo) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE prestamos SET estado = 'pendiente_devolucion' WHERE id_prestamo = ?");
        return $stmt->execute([$id_prestamo]);
    }

    /* aprobar: admin/entrenador aprueba un préstamo pendiente -> estado = activo. */
    public static function aprobar($id_prestamo) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE prestamos SET estado = 'activo' WHERE id_prestamo = ?");
        return $stmt->execute([$id_prestamo]);
    }

    /* confirmarDevolucion: admin confirma la devolución -> estado = devuelto + fecha actual. */
    public static function confirmarDevolucion($id_prestamo) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE prestamos SET estado = 'devuelto', fecha_devolucion = CURRENT_DATE() WHERE id_prestamo = ?");
        return $stmt->execute([$id_prestamo]);
    }

    /* actualizar: cambia solo la fecha de devolución prevista de un préstamo. */
    public static function actualizar($id_prestamo, $fecha_devolucion) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE prestamos SET fecha_devolucion = ? WHERE id_prestamo = ?");
        return $stmt->execute([$fecha_devolucion, $id_prestamo]);
    }

    /* eliminar: borra físico del préstamo. Solo admin/entrenador (cliente tiene denegado DELETE en index.php). */
    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM prestamos WHERE id_prestamo = ?");
        return $stmt->execute([$id]);
    }

    /* Getters: acceso controlado a propiedades privadas */
    public function getId() { return $this->id_prestamo; }
    public function getIdUsuario() { return $this->id_usuario; }
    public function getIdMaterial() { return $this->id_material; }
    public function getFechaInicio() { return $this->fecha_inicio; }
    public function getFechaDevolucion() { return $this->fecha_devolucion; }
    public function getEstado() { return $this->estado; }
    public function getUsuarioNombre() { return $this->nombre_usuario; }
    public function getMaterialNombre() { return $this->nombre_material; }
    public function getFecha() { return $this->fecha_inicio; }           // Alias para compatibilidad con otras vistas
    public function estaDevuelto() { return $this->fecha_devolucion !== null; }
}
