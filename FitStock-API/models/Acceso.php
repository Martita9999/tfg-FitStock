<?php
require_once __DIR__ . "/../conexion.php";   // Importa la conexión a la base de datos

// Clase modelo para la tabla 'accesos_registro' - registra entradas/salidas de usuarios
class Acceso {
    private $id_acceso;       // ID único del registro de acceso
    private $id_usuario;      // ID del usuario que accede
    private $fecha_entrada;   // Fecha y hora de entrada

    // Constructor: asigna todos los valores al crear una instancia
    public function __construct($id_acceso, $id_usuario, $fecha_entrada){
        $this->id_acceso = $id_acceso;
        $this->id_usuario = $id_usuario;
        $this->fecha_entrada = $fecha_entrada;
    }

    // Registra la entrada de un usuario en el gimnasio
    public static function registrar($id_usuario){
        $conexion = obtenerConexion();
        $stmt = $conexion->prepare("INSERT INTO accesos_registro (id_usuario) VALUES (?)");
        $resultado = $stmt->execute([$id_usuario]);
        if ($resultado){
            return $conexion->lastInsertId();   // Devuelve el ID del nuevo registro
        }
        return false;
    }

    // Elimina un registro de acceso por su ID
    public static function eliminar($id){
        $conexion = obtenerConexion();
        $stmt = $conexion->prepare("DELETE FROM accesos_registro WHERE id_acceso = ?");
        return $stmt->execute([$id]);
    }

    // Obtiene los últimos N registros de acceso (por defecto 10)
    public static function obtenerUltimos($cantidad = 10){
        $conexion = obtenerConexion();
        $stmt = $conexion->prepare("SELECT a.*, u.nombre as nombre_usuario FROM accesos_registro a LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario ORDER BY a.fecha_entrada DESC LIMIT ?");
        $stmt->execute([$cantidad]);
        $accesos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)){   // Itera sobre cada fila
            $accesos[] = new Acceso($fila['id_acceso'], $fila['id_usuario'], $fila['fecha_entrada']);
        }
        return $accesos;   // Devuelve array de objetos Acceso
    }

    // Obtiene todos los registros de acceso ordenados por fecha descendente
    public static function obtenerTodos(){
        $conexion = obtenerConexion();
        $stmt = $conexion->prepare("SELECT a.*, u.nombre as nombre_usuario FROM accesos_registro a LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario ORDER BY a.fecha_entrada DESC");
        $stmt->execute();
        $accesos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
            $accesos[] = new Acceso($fila['id_acceso'], $fila['id_usuario'], $fila['fecha_entrada']);
        }
        return $accesos;
    }

    // Busca un registro de acceso por su ID
    public static function obtenerPorId($id) {
        $conexion = obtenerConexion();
        $stmt = $conexion->prepare("SELECT * FROM accesos_registro WHERE id_acceso = ?");
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Acceso($fila['id_acceso'], $fila['id_usuario'], $fila['fecha_entrada']);
        }
        return null;
    }

    // Getters para acceder a las propiedades privadas
    public function getId(){ return $this->id_acceso; }
    public function getIdUsuario(){ return $this->id_usuario; }
    public function getFechaEntrada(){ return $this->fecha_entrada; }
}
