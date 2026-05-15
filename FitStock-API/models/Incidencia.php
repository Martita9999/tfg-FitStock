<?php
require_once __DIR__ . "/../conexion.php";

// Clase modelo para la tabla 'incidencias' - gestiona reportes de problemas en materiales
class Incidencia {
    private $id_incidencia;       // ID único de la incidencia
    private $id_material;         // ID del material afectado
    private $id_user_rep;         // ID del usuario que reportó
    private $descripcion;         // Descripción del problema
    private $prioridad;           // Prioridad: 'baja', 'media', 'alta'
    private $estado_inc;          // Estado: 'abierta', 'en_proceso', 'resuelta'
    private $created_at;          // Fecha de creación (de la BD)
    private $fecha_resolucion;    // Fecha de resolución (de la BD)
    private $nombre_material;     // Nombre del material (de JOIN)
    private $id_tag_material;     // Identificador del material (de JOIN)
    private $ubicacion;           // Ubicación del material (de JOIN)

    // Constructor: asigna todos los valores al crear una instancia.
    // Los parámetros de JOIN (nombre_material, id_tag_material, ubicacion) son opcionales
    // y solo se rellenan cuando la consulta incluye la tabla material.
    public function __construct($id_incidencia, $id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc, $created_at = null, $fecha_resolucion = null, $nombre_material = null, $id_tag_material = null, $ubicacion = null) {
        $this->id_incidencia = $id_incidencia;
        $this->id_material = $id_material;
        $this->id_user_rep = $id_user_rep;
        $this->descripcion = $descripcion;
        $this->prioridad = $prioridad;
        $this->estado_inc = $estado_inc;
        $this->created_at = $created_at;
        $this->fecha_resolucion = $fecha_resolucion;
        $this->nombre_material = $nombre_material;
        $this->id_tag_material = $id_tag_material;
        $this->ubicacion = $ubicacion;
    }

    // Obtiene todas las incidencias con el nombre del material asociado
    public static function obtenerTodos() {
        $conexion = Conexion::conectar();
        $sql = "SELECT i.id_incidencia, i.id_material, i.id_user_rep, i.descripcion,
                       i.prioridad, i.estado_inc, i.created_at, i.fecha_resolucion,
                       COALESCE(m.nombre_equipo, '—') as nombre_material,
                       m.id_tag_material as id_tag_material,
                       m.ubicacion as ubicacion
                FROM incidencias i
                LEFT JOIN material m ON i.id_material = m.id_material
                ORDER BY i.id_incidencia DESC";
        $stmt = $conexion->query($sql);
        $incidencias = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $incidencias[] = new Incidencia(
                $fila['id_incidencia'], $fila['id_material'], $fila['id_user_rep'],
                $fila['descripcion'], $fila['prioridad'], $fila['estado_inc'],
                $fila['created_at'], $fila['fecha_resolucion'],
                $fila['nombre_material'], $fila['id_tag_material'],
                $fila['ubicacion']
            );
        }
        return $incidencias;
    }

    // Busca una incidencia por su ID, incluyendo los datos del material asociado mediante JOIN
    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();
        $sql = "SELECT i.id_incidencia, i.id_material, i.id_user_rep, i.descripcion,
                       i.prioridad, i.estado_inc, i.created_at, i.fecha_resolucion,
                       COALESCE(m.nombre_equipo, '—') as nombre_material,
                       m.id_tag_material as id_tag_material,
                       m.ubicacion as ubicacion
                FROM incidencias i
                LEFT JOIN material m ON i.id_material = m.id_material
                WHERE i.id_incidencia = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Incidencia(
                $fila['id_incidencia'], $fila['id_material'], $fila['id_user_rep'],
                $fila['descripcion'], $fila['prioridad'], $fila['estado_inc'],
                $fila['created_at'], $fila['fecha_resolucion'],
                $fila['nombre_material'], $fila['id_tag_material'],
                $fila['ubicacion']
            );
        }
        return null;
    }

    // Crea una nueva incidencia en la base de datos
    public static function crear($id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc = 'abierta') {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO incidencias (id_material, id_user_rep, descripcion, prioridad, estado_inc) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc]);
        return $conexion->lastInsertId();
    }

    // Actualiza la descripción, prioridad y/o estado de una incidencia.
    // Solo modifica los campos que se proporcionan (no null).
    // Si el estado cambia a 'resuelta', establece automáticamente la fecha de resolución (NOW()).
    // Si el estado cambia a otro valor, pone fecha_resolucion a NULL.
    public static function actualizar($id, $prioridad, $estado_inc, $descripcion = null) {
        $conexion = Conexion::conectar();
        $campos = [];
        $valores = [];
        if ($descripcion !== null) {
            $campos[] = "descripcion = ?";
            $valores[] = $descripcion;
        }
        if ($prioridad !== null) {
            $campos[] = "prioridad = ?";
            $valores[] = $prioridad;
        }
        if ($estado_inc !== null) {
            $campos[] = "estado_inc = ?";
            $valores[] = $estado_inc;
            $campos[] = "fecha_resolucion = IF(? = 'resuelta', NOW(), NULL)";
            $valores[] = $estado_inc;
        }
        if (empty($campos)) return true;
        $valores[] = $id;
        $sql = "UPDATE incidencias SET " . implode(", ", $campos) . " WHERE id_incidencia = ?";
        $stmt = $conexion->prepare($sql);
        return $stmt->execute($valores);
    }

    // Elimina una incidencia por su ID
    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM incidencias WHERE id_incidencia = ?");
        return $stmt->execute([$id]);
    }

    // Getters para acceder a las propiedades privadas
    public function getId() { return $this->id_incidencia; }
    public function getIdMaterial() { return $this->id_material; }
    public function getIdUser() { return $this->id_user_rep; }            // ID del usuario que reportó
    public function getDescripcion() { return $this->descripcion; }
    public function getPrioridad() { return $this->prioridad; }
    public function getEstado() { return $this->estado_inc; }
    public function getCreatedAt() { return $this->created_at; }
    public function getFechaResolucion() { return $this->fecha_resolucion; }
    public function getNombreMaterial() { return $this->nombre_material; }  // Nombre del material (de JOIN)
    public function getIdTagMaterial() { return $this->id_tag_material; }   // Identificador único del material (de JOIN)
    public function getUbicacion() { return $this->ubicacion; }             // Ubicación del material (de JOIN)
}
