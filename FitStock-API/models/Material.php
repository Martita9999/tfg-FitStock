<?php
require_once __DIR__ . "/../conexion.php";

class Material {
    private $id_material;
    private $nombre_equipo;
    private $descripcion;
    private $ubicacion;
    private $estado;
    private $tipo;
    private $qr_identificador;
    private $ultima_rev;

    public function __construct($id_material, $nombre_equipo, $descripcion, $estado, $tipo, $qr_identificador, $ultima_rev, $ubicacion = null) {
        $this->id_material = $id_material;
        $this->nombre_equipo = $nombre_equipo;
        $this->descripcion = $descripcion;
        $this->estado = $estado;
        $this->tipo = $tipo;
        $this->qr_identificador = $qr_identificador;
        $this->ultima_rev = $ultima_rev;
        $this->ubicacion = $ubicacion;
    }

    private static function generarQr($nombre, $conexion) {
        $words = preg_split('/\s+/', $nombre);
        $prefix = '';
        $skip = ['de', 'la', 'el', 'los', 'las', 'del', 'en', 'por', 'con', 'un', 'una'];
        foreach ($words as $w) {
            $lower = strtolower(trim($w));
            if ($lower !== '' && !in_array($lower, $skip)) {
                $prefix .= strtoupper(substr($w, 0, 3));
            }
        }
        if (strlen($prefix) < 2) $prefix = 'MAQ';
        $prefix = substr($prefix, 0, 5);

        $stmt = $conexion->prepare("SELECT qr_identificador FROM material WHERE qr_identificador LIKE ? ORDER BY qr_identificador DESC LIMIT 1");
        $stmt->execute([$prefix . '-%']);
        $last = $stmt->fetchColumn();
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $num = intval($m[1]) + 1;
        } else {
            $num = 1;
        }
        return $prefix . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    public static function obtenerTodos($tipo = null) {
        $conexion = Conexion::conectar();
        if ($tipo) {
            $stmt = $conexion->prepare("SELECT * FROM material WHERE tipo = ? ORDER BY nombre_equipo");
            $stmt->execute([$tipo]);
        } else {
            $stmt = $conexion->prepare("SELECT * FROM material ORDER BY nombre_equipo");
            $stmt->execute();
        }
        $materiales = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $materiales[] = new Material($fila['id_material'], $fila['nombre_equipo'], $fila['descripcion'], $fila['estado'], $fila['tipo'], $fila['qr_identificador'], $fila['ultima_rev'], $fila['ubicacion'] ?? null);
        }
        return $materiales;
    }

    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM material WHERE id_material = ?");
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Material($fila['id_material'], $fila['nombre_equipo'], $fila['descripcion'], $fila['estado'], $fila['tipo'], $fila['qr_identificador'], $fila['ultima_rev'], $fila['ubicacion'] ?? null);
        }
        return null;
    }

    public static function obtenerPorQR($qr_identificador) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM material WHERE qr_identificador = ?");
        $stmt->execute([$qr_identificador]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Material($fila['id_material'], $fila['nombre_equipo'], $fila['descripcion'], $fila['estado'], $fila['tipo'], $fila['qr_identificador'], $fila['ultima_rev'], $fila['ubicacion'] ?? null);
        }
        return null;
    }

    public static function crear($nombre_equipo, $descripcion, $estado, $tipo, $qr_identificador, $ultima_rev = null, $ubicacion = null) {
        $conexion = Conexion::conectar();
        if (!$qr_identificador) {
            $qr_identificador = self::generarQr($nombre_equipo, $conexion);
        }
        $stmt = $conexion->prepare("INSERT INTO material (nombre_equipo, descripcion, estado, tipo, qr_identificador, ultima_rev, ubicacion) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $resultado = $stmt->execute([$nombre_equipo, $descripcion, $estado, $tipo, $qr_identificador, $ultima_rev, $ubicacion]);
        if ($resultado) {
            return $conexion->lastInsertId();
        }
        return false;
    }

    public static function actualizar($id_material, $nombre_equipo, $descripcion, $estado, $ultima_rev, $ubicacion = null) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE material SET nombre_equipo = ?, descripcion = ?, estado = ?, ultima_rev = ?, ubicacion = ? WHERE id_material = ?");
        return $stmt->execute([$nombre_equipo, $descripcion, $estado, $ultima_rev, $ubicacion, $id_material]);
    }

    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM material WHERE id_material = ?");
        return $stmt->execute([$id]);
    }

    public function getId() { return $this->id_material; }
    public function getNombre() { return $this->nombre_equipo; }
    public function getDescripcion() { return $this->descripcion; }
    public function getUbicacion() { return $this->ubicacion; }
    public function getEstado() { return $this->estado; }
    public function getTipo() { return $this->tipo; }
    public function getQrIdentificador() { return $this->qr_identificador; }
    public function getUltimaRev() { return $this->ultima_rev; }
    public function estaOperativo() { return $this->estado === 'operativo'; }
}
