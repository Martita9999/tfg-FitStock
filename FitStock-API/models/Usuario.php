<?php
require_once __DIR__ . "/../conexion.php";   // Importa la conexión a la base de datos

// Clase modelo para la tabla 'usuarios' - gestiona usuarios del sistema
class Usuario {
    private $id;
    private $nombre;
    private $email;
    private $password_hash;
    private $rol;

    public function __construct($id, $nombre, $email, $password_hash, $rol) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->password_hash = $password_hash;
        $this->rol = $rol;
    }

    // Busca un usuario por su ID en la base de datos
    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();                                        // Obtiene la conexión PDO
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id_usuario = ?"); // Prepara la consulta
        $stmt->execute([$id]);                                                    // Ejecuta con el ID como parámetro
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);                                   // Obtiene la fila como array asociativo
        if ($fila) {
            return new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['password_hash'], $fila['rol']);
        }
        return null;
    }

    public static function obtenerPorEmail($email) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['password_hash'], $fila['rol']);
        }
        return null;
    }

    public static function crear($nombre, $email, $password, $rol) {
        $conexion = Conexion::conectar();
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nombre, $email, $password_hash, $rol]);
    }

    public static function actualizarAdmin($id, $nombre, $email, $password = null, $rol = null) {
        $conexion = Conexion::conectar();
        $campos = [];
        $valores = [];
        $campos[] = "nombre = ?";
        $valores[] = $nombre;
        $campos[] = "email = ?";
        $valores[] = $email;
        if ($password) {
            $campos[] = "password_hash = ?";
            $valores[] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($rol) {
            $campos[] = "rol = ?";
            $valores[] = $rol;
        }
        $valores[] = $id;
        $sql = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id_usuario = ?";
        $stmt = $conexion->prepare($sql);
        return $stmt->execute($valores);
    }

    // Elimina un usuario por su ID
    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        return $stmt->execute([$id]);   // Ejecuta la eliminación y devuelve true/false
    }

    // Obtiene todos los usuarios ordenados por nombre
    public static function obtenerTodos() {
        $conexion = Conexion::conectar();
        $stmt = $conexion->query("SELECT * FROM usuarios ORDER BY nombre");  // Consulta sin parámetros
        $usuarios = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {                      // Itera sobre cada fila
            $usuarios[] = new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['password_hash'], $fila['rol']);
        }
        return $usuarios;
    }

    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getEmail() { return $this->email; }
    public function getPasswordHash() { return $this->password_hash; }
    public function getRol() { return $this->rol; }
}
