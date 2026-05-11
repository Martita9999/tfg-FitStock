<?php
require_once __DIR__ . "/../conexion.php";   // Importa la conexión a la base de datos

// Clase modelo para la tabla 'usuarios' - gestiona usuarios del sistema
class Usuario {
    private $id;              // ID único del usuario (autoincremental)
    private $nombre;          // Nombre completo del usuario
    private $email;           // Correo electrónico (único en la BD)
    private $password_hash;   // Hash de la contraseña (bcrypt)
    private $rol;             // Rol del usuario: 'admin', 'entrenador' o 'cliente'

    // Constructor: asigna todos los valores al crear una instancia
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
            return new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['password_hash'], $fila['rol']);  // Crea y devuelve objeto Usuario
        }
        return null;   // Devuelve null si no se encuentra el usuario
    }

    // Busca un usuario por su correo electrónico
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

    // Crea un nuevo usuario en la base de datos con contraseña hasheada
    public static function crear($nombre, $email, $password, $rol) {
        $conexion = Conexion::conectar();
        $password_hash = password_hash($password, PASSWORD_DEFAULT);  // Genera el hash bcrypt de la contraseña
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nombre, $email, $password_hash, $rol]);  // Inserta y devuelve true/false
    }

    // Actualiza los datos de un usuario (nombre, email y opcionalmente contraseña)
    public static function actualizar($id, $nombre, $email, $password = null) {
        $conexion = Conexion::conectar();
        if ($password) {                                          // Si se proporciona nueva contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);  // Hashea la nueva contraseña
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, email = ?, password_hash = ? WHERE id_usuario = ?");
            return $stmt->execute([$nombre, $email, $password_hash, $id]);
        } else {                                                   // Si no hay contraseña nueva
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id_usuario = ?");
            return $stmt->execute([$nombre, $email, $id]);
        }
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
        return $usuarios;   // Devuelve array de objetos Usuario
    }

    // Getters para acceder a las propiedades privadas
    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getEmail() { return $this->email; }
    public function getPasswordHash() { return $this->password_hash; }
    public function getRol() { return $this->rol; }
}
