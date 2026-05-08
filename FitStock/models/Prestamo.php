<?php
require_once __DIR__ . "/../conexion.php";   // Importa la conexión a la base de datos

// Clase modelo para la tabla 'prestamos' - gestiona préstamos de material a usuarios
class Prestamo {
    private $id_prestamo;         // ID único del préstamo
    private $id_usuario;          // ID del usuario que toma prestado
    private $id_material;         // ID del material prestado
    private $fecha_inicio;        // Fecha en que se realizó el préstamo
    private $fecha_devolucion;    // Fecha de devolución (null si aún no se ha devuelto)
    private $nombre_usuario;      // Nombre del usuario (de la JOIN)
    private $nombre_material;     // Nombre del material (de la JOIN)

    // Constructor: asigna todos los valores al crear una instancia
    public function __construct($id_prestamo, $id_usuario, $id_material, $fecha_inicio, $fecha_devolucion, $nombre_usuario = '', $nombre_material = '') {
        $this->id_prestamo = $id_prestamo;
        $this->id_usuario = $id_usuario;
        $this->id_material = $id_material;
        $this->fecha_inicio = $fecha_inicio;
        $this->fecha_devolucion = $fecha_devolucion;
        $this->nombre_usuario = $nombre_usuario;
        $this->nombre_material = $nombre_material;
    }

    // Obtiene todos los préstamos con los nombres de usuario y material
    public static function obtenerTodos() {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT p.*, u.nombre as nombre_usuario, m.nombre_equipo FROM prestamos p LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario LEFT JOIN material m ON p.id_material = m.id_material ORDER BY p.fecha_inicio DESC");
        $stmt->execute();
        $prestamos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {   // Itera sobre cada fila
            $prestamos[] = new Prestamo($fila['id_prestamo'], $fila['id_usuario'], $fila['id_material'], $fila['fecha_inicio'], $fila['fecha_devolucion'], $fila['nombre_usuario'], $fila['nombre_equipo']);
        }
        return $prestamos;   // Devuelve array de objetos Prestamo
    }

    // Obtiene solo los préstamos activos (sin fecha de devolución)
    public static function obtenerActivos() {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT p.*, u.nombre as nombre_usuario, m.nombre_equipo FROM prestamos p LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario LEFT JOIN material m ON p.id_material = m.id_material WHERE p.fecha_devolucion IS NULL ORDER BY p.fecha_inicio DESC");
        $stmt->execute();
        $prestamos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $prestamos[] = new Prestamo($fila['id_prestamo'], $fila['id_usuario'], $fila['id_material'], $fila['fecha_inicio'], $fila['fecha_devolucion'], $fila['nombre_usuario'], $fila['nombre_equipo']);
        }
        return $prestamos;
    }

    // Crea un nuevo préstamo en la base de datos
    public static function crear($id_usuario, $id_material, $fecha_devolucion) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO prestamos (id_usuario, id_material, fecha_devolucion) VALUES (?, ?, ?)");
        $resultado = $stmt->execute([$id_usuario, $id_material, $fecha_devolucion]);
        if ($resultado) {
            return $conexion->lastInsertId();   // Devuelve el ID del nuevo préstamo
        }
        return false;
    }

    // Marca un préstamo como devuelto (fecha_devolucion = fecha actual)
    public static function devolver($id_prestamo) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE prestamos SET fecha_devolucion = CURRENT_DATE() WHERE id_prestamo = ?");
        return $stmt->execute([$id_prestamo]);
    }

    // Elimina un préstamo por su ID
    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM prestamos WHERE id_prestamo = ?");
        return $stmt->execute([$id]);
    }

    // Getters para acceder a las propiedades privadas
    public function getId() { return $this->id_prestamo; }
    public function getIdUsuario() { return $this->id_usuario; }
    public function getIdMaterial() { return $this->id_material; }
    public function getFechaInicio() { return $this->fecha_inicio; }
    public function getFechaDevolucion() { return $this->fecha_devolucion; }
    public function getUsuarioNombre() { return $this->nombre_usuario; }
    public function getMaterialNombre() { return $this->nombre_material; }
    public function getFecha() { return $this->fecha_inicio; }   // Alias para compatibilidad
    public function estaDevuelto() { return $this->fecha_devolucion !== null; }  // Comprueba si está devuelto
}
