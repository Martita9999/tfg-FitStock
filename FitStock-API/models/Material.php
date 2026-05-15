<?php
require_once __DIR__ . "/../conexion.php";

// Clase modelo para la tabla 'material' - gestiona equipamiento y material deportivo
class Material {
    private $id_material;         // ID único del material
    private $nombre_equipo;       // Nombre del equipo
    private $descripcion;         // Descripción breve del material
    private $ubicacion;           // Ubicación en el gimnasio
    private $estado;              // Estado: 'operativo', 'averiado', 'mantenimiento', etc.
    private $tipo;                // Tipo: 'maquina' o 'prestable'
    private $id_tag_material;     // Identificador único del material
    private $ultima_rev;          // Fecha de la última revisión

    // Constructor: asigna todos los valores al crear una instancia
    public function __construct($id_material, $nombre_equipo, $descripcion, $estado, $tipo, $id_tag_material, $ultima_rev, $ubicacion = null) {
        $this->id_material = $id_material;
        $this->nombre_equipo = $nombre_equipo;
        $this->descripcion = $descripcion;
        $this->estado = $estado;
        $this->tipo = $tipo;
        $this->id_tag_material = $id_tag_material;
        $this->ultima_rev = $ultima_rev;
        $this->ubicacion = $ubicacion;
    }

    // Genera un identificador único (id_tag_material) a partir del nombre del material.
    // Algoritmo:
    //   1. Toma las primeras 3 letras mayúsculas de cada palabra significativa (ignora artículos/preposiciones).
    //   2. Si el prefijo resultante es muy corto (<2 caracteres), usa 'MAQ' por defecto.
    //   3. Acorta el prefijo a 5 caracteres máximo.
    //   4. Busca el último id_tag_material existente con ese prefijo en la BD.
    //   5. Incrementa el número secuencial (ej: BAN-001, BAN-002, ...).
    // Esto garantiza identificadores legibles y únicos para cada material.
    private static function generarIdTag($nombre, $conexion) {
        $words = preg_split('/\s+/', $nombre);
        $prefix = '';
        // Palabras a ignorar (artículos, preposiciones) para evitar prefijos genéricos
        $skip = ['de', 'la', 'el', 'los', 'las', 'del', 'en', 'por', 'con', 'un', 'una'];
        foreach ($words as $w) {
            $lower = strtolower(trim($w));
            if ($lower !== '' && !in_array($lower, $skip)) {
                $prefix .= strtoupper(substr($w, 0, 3));
            }
        }
        if (strlen($prefix) < 2) $prefix = 'MAQ';
        $prefix = substr($prefix, 0, 5);

        // Consulta el último tag existente con este prefijo para continuar la secuencia numérica
        $stmt = $conexion->prepare("SELECT id_tag_material FROM material WHERE id_tag_material LIKE ? ORDER BY id_tag_material DESC LIMIT 1");
        $stmt->execute([$prefix . '-%']);
        $last = $stmt->fetchColumn();
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }
        return $prefix . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    // Obtiene todos los materiales, opcionalmente filtrados por tipo
    public static function obtenerTodos($tipo = null) {
        $conexion = Conexion::conectar();
        if ($tipo) {
            $stmt = $conexion->prepare("SELECT * FROM material WHERE tipo = ? ORDER BY id_tag_material");
            $stmt->execute([$tipo]);
        } else {
            $stmt = $conexion->prepare("SELECT * FROM material ORDER BY id_tag_material");
            $stmt->execute();
        }
        $materiales = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $materiales[] = new Material($fila['id_material'], $fila['nombre_equipo'], $fila['descripcion'], $fila['estado'], $fila['tipo'], $fila['id_tag_material'], $fila['ultima_rev'], $fila['ubicacion'] ?? null);
        }
        return $materiales;
    }

    // Busca un material por su ID
    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM material WHERE id_material = ?");
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Material($fila['id_material'], $fila['nombre_equipo'], $fila['descripcion'], $fila['estado'], $fila['tipo'], $fila['id_tag_material'], $fila['ultima_rev'], $fila['ubicacion'] ?? null);
        }
        return null;
    }

    // Busca un material por su identificador
    public static function obtenerPorIdTag($id_tag_material) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM material WHERE id_tag_material = ?");
        $stmt->execute([$id_tag_material]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Material($fila['id_material'], $fila['nombre_equipo'], $fila['descripcion'], $fila['estado'], $fila['tipo'], $fila['id_tag_material'], $fila['ultima_rev'], $fila['ubicacion'] ?? null);
        }
        return null;
    }

    // Crea un nuevo material en la base de datos (genera identificador si no se proporciona)
    public static function crear($nombre_equipo, $descripcion, $estado, $tipo, $id_tag_material, $ultima_rev = null, $ubicacion = null) {
        $conexion = Conexion::conectar();
        if (!$id_tag_material) {
            $id_tag_material = self::generarIdTag($nombre_equipo, $conexion);
        }
        $stmt = $conexion->prepare("INSERT INTO material (nombre_equipo, descripcion, estado, tipo, id_tag_material, ultima_rev, ubicacion) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $resultado = $stmt->execute([$nombre_equipo, $descripcion, $estado, $tipo, $id_tag_material, $ultima_rev, $ubicacion]);
        if ($resultado) {
            return $conexion->lastInsertId();
        }
        return false;
    }

    // Actualiza los datos de un material existente en la base de datos.
    // Permite modificar todos los campos simultáneamente: nombre, descripción, estado,
    // última revisión, ubicación e identificador. Requiere el ID del material a actualizar.
    public static function actualizar($id_material, $nombre_equipo, $descripcion, $estado, $ultima_rev, $ubicacion = null, $id_tag_material = null) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE material SET nombre_equipo = ?, descripcion = ?, estado = ?, ultima_rev = ?, ubicacion = ?, id_tag_material = ? WHERE id_material = ?");
        return $stmt->execute([$nombre_equipo, $descripcion, $estado, $ultima_rev, $ubicacion, $id_tag_material, $id_material]);
    }

    // Elimina un material por su ID
    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM material WHERE id_material = ?");
        return $stmt->execute([$id]);
    }

    // Getters para acceder a las propiedades privadas
    public function getId() { return $this->id_material; }
    public function getNombre() { return $this->nombre_equipo; }
    public function getDescripcion() { return $this->descripcion; }
    public function getUbicacion() { return $this->ubicacion; }
    public function getEstado() { return $this->estado; }
    public function getTipo() { return $this->tipo; }
    public function getIdTagMaterial() { return $this->id_tag_material; }  // Devuelve el identificador del material
    public function getUltimaRev() { return $this->ultima_rev; }
    public function estaOperativo() { return $this->estado === 'operativo'; }  // Comprueba si el material está operativo
}
