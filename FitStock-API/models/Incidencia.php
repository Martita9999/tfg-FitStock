<?php
require_once __DIR__ . "/../conexion.php";

class Incidencia {
    private $id_incidencia;
    private $id_material;
    private $id_user_rep;
    private $descripcion;
    private $prioridad;
    private $estado_inc;
    private $created_at;
    private $fecha_resolucion;
    private $nombre_material;

    public function __construct($id_incidencia, $id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc, $created_at = null, $fecha_resolucion = null, $nombre_material = null) {
        $this->id_incidencia = $id_incidencia;
        $this->id_material = $id_material;
        $this->id_user_rep = $id_user_rep;
        $this->descripcion = $descripcion;
        $this->prioridad = $prioridad;
        $this->estado_inc = $estado_inc;
        $this->created_at = $created_at;
        $this->fecha_resolucion = $fecha_resolucion;
        $this->nombre_material = $nombre_material;
    }

    private static function columnasExtra($conexion) {
        $existentes = [];
        foreach (['created_at', 'fecha_resolucion'] as $col) {
            try {
                $conexion->query("SELECT $col FROM incidencias LIMIT 0");
                $existentes[] = "i.$col";
            } catch (Exception $e) {}
        }
        return $existentes;
    }

    public static function obtenerTodos() {
        $conexion = Conexion::conectar();
        $base = ['i.id_incidencia', 'i.id_material', 'i.id_user_rep', 'i.descripcion', 'i.prioridad', 'i.estado_inc'];
        $extra = self::columnasExtra($conexion);
        $cols = implode(', ', array_merge($base, $extra));
        $sql = "SELECT $cols, COALESCE(m.nombre_equipo, '—') as nombre_material
                FROM incidencias i
                LEFT JOIN material m ON i.id_material = m.id_material
                ORDER BY i.id_incidencia DESC";
        $stmt = $conexion->query($sql);
        $incidencias = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $incidencias[] = new Incidencia(
                $fila['id_incidencia'], $fila['id_material'], $fila['id_user_rep'],
                $fila['descripcion'], $fila['prioridad'], $fila['estado_inc'],
                $fila['created_at'] ?? null, $fila['fecha_resolucion'] ?? null,
                $fila['nombre_material']
            );
        }
        return $incidencias;
    }

    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();
        $base = ['i.id_incidencia', 'i.id_material', 'i.id_user_rep', 'i.descripcion', 'i.prioridad', 'i.estado_inc'];
        $extra = self::columnasExtra($conexion);
        $cols = implode(', ', array_merge($base, $extra));
        $sql = "SELECT $cols, COALESCE(m.nombre_equipo, '—') as nombre_material
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
                $fila['created_at'] ?? null, $fila['fecha_resolucion'] ?? null,
                $fila['nombre_material']
            );
        }
        return null;
    }

    public static function crear($id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc = 'abierta') {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO incidencias (id_material, id_user_rep, descripcion, prioridad, estado_inc) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc]);
        return $conexion->lastInsertId();
    }

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
            try {
                $conexion->query("SELECT fecha_resolucion FROM incidencias LIMIT 0");
                $campos[] = "fecha_resolucion = IF(? = 'resuelta', NOW(), NULL)";
                $valores[] = $estado_inc;
            } catch (Exception $e) {}
        }
        if (empty($campos)) return true;
        $valores[] = $id;
        $sql = "UPDATE incidencias SET " . implode(", ", $campos) . " WHERE id_incidencia = ?";
        $stmt = $conexion->prepare($sql);
        return $stmt->execute($valores);
    }

    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM incidencias WHERE id_incidencia = ?");
        return $stmt->execute([$id]);
    }

    public function getId() { return $this->id_incidencia; }
    public function getIdMaterial() { return $this->id_material; }
    public function getIdUser() { return $this->id_user_rep; }
    public function getDescripcion() { return $this->descripcion; }
    public function getPrioridad() { return $this->prioridad; }
    public function getEstado() { return $this->estado_inc; }
    public function getCreatedAt() { return $this->created_at; }
    public function getFechaResolucion() { return $this->fecha_resolucion; }
    public function getNombreMaterial() { return $this->nombre_material; }
}
