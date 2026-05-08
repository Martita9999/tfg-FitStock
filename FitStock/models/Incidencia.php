<?php
require_once __DIR__ . "/../conexion.php";   // Importa la conexión a la base de datos

// Clase modelo para la tabla 'incidencias' - gestiona reportes de incidencias en materiales
class Incidencia {
    private $id_incidencia;   // ID único de la incidencia
    private $id_material;     // ID del material relacionado
    private $id_user_rep;     // ID del usuario que reportó la incidencia
    private $descripcion;     // Descripción detallada del problema
    private $prioridad;       // Prioridad: 'baja', 'media', 'alta', 'critica'
    private $estado_inc;      // Estado: 'abierta', 'en_proceso', 'resuelta'

    // Constructor: asigna todos los valores al crear una instancia
    public function __construct($id_incidencia, $id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc) {
        $this->id_incidencia = $id_incidencia;
        $this->id_material = $id_material;
        $this->id_user_rep = $id_user_rep;
        $this->descripcion = $descripcion;
        $this->prioridad = $prioridad;
        $this->estado_inc = $estado_inc;
    }

    // Obtiene todas las incidencias ordenadas por ID descendente (más recientes primero)
    public static function obtenerTodos() {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM incidencias ORDER BY id_incidencia DESC");
        $stmt->execute();
        $incidencias = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {   // Itera sobre cada fila
            $incidencias[] = new Incidencia($fila['id_incidencia'], $fila['id_material'], $fila['id_user_rep'], $fila['descripcion'], $fila['prioridad'], $fila['estado_inc']);
        }
        return $incidencias;   // Devuelve array de objetos Incidencia
    }

    // Busca una incidencia por su ID
    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM incidencias WHERE id_incidencia = ?");
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Incidencia($fila['id_incidencia'], $fila['id_material'], $fila['id_user_rep'], $fila['descripcion'], $fila['prioridad'], $fila['estado_inc']);
        }
        return null;
    }

    // Crea una nueva incidencia, estado por defecto 'abierta'
    public static function crear($id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc = 'abierta') {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO incidencias (id_material, id_user_rep, descripcion, prioridad, estado_inc) VALUES (?, ?, ?, ?, ?)");
        $ok = $stmt->execute([$id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc]);
        if ($ok) {
            return $conexion->lastInsertId();   // Devuelve el ID de la nueva incidencia
        }
        return false;
    }

    // Actualiza la prioridad y/o estado de una incidencia (solo campos no nulos)
    public static function actualizar($id, $prioridad, $estado_inc) {
        $conexion = Conexion::conectar();
        $campos = [];
        $valores = [];
        if ($prioridad !== null) {
            $campos[] = "prioridad = ?";
            $valores[] = $prioridad;
        }
        if ($estado_inc !== null) {
            $campos[] = "estado_inc = ?";
            $valores[] = $estado_inc;
        }
        if (empty($campos)) return true;
        $valores[] = $id;
        $stmt = $conexion->prepare("UPDATE incidencias SET " . implode(", ", $campos) . " WHERE id_incidencia = ?");
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
    public function getIdUser() { return $this->id_user_rep; }
    public function getDescripcion() { return $this->descripcion; }
    public function getPrioridad() { return $this->prioridad; }
    public function getEstado() { return $this->estado_inc; }
}
