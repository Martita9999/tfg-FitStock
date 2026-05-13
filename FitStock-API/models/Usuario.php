<?php
require_once __DIR__ . "/../conexion.php";   // Importa la conexión a la base de datos

/*
 * Modelo Usuario.
 * Representa un registro de la tabla 'usuarios' y proporciona métodos estáticos
 * para operaciones CRUD. Incluye funcionalidad para forzar el cambio de contraseña
 * en el primer inicio de sesión o por decisión administrativa.
 */
class Usuario {
    private $id;
    private $nombre;
    private $email;
    private $password_hash;
    private $rol;
    // Indica si el usuario debe cambiar su contraseña en el próximo inicio de sesión.
    //  0 = no forzar, 1 = forzar cambio. Se usa tras crear un usuario admin o para restablecer credenciales.
    private $forzar_cambio_password;

    // Constructor: recibe todos los datos del usuario, con valor opcional para forzar_cambio_password (por defecto 0).
    public function __construct($id, $nombre, $email, $password_hash, $rol, $forzar_cambio_password = 0) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->password_hash = $password_hash;
        $this->rol = $rol;
        $this->forzar_cambio_password = $forzar_cambio_password;
    }

    // Busca un usuario por su ID en la base de datos
    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();                                        // Obtiene la conexión PDO
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id_usuario = ?"); // Prepara la consulta
        $stmt->execute([$id]);                                                    // Ejecuta con el ID como parámetro
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);                                   // Obtiene la fila como array asociativo
        if ($fila) {
            return new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['password_hash'], $fila['rol'], $fila['forzar_cambio_password'] ?? 0);
        }
        return null;
    }

    public static function obtenerPorEmail($email) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['password_hash'], $fila['rol'], $fila['forzar_cambio_password'] ?? 0);
        }
        return null;
    }

    /*
     * Encuentra el primer ID libre (sin usar) en la tabla 'usuarios'.
     * Algoritmo de "gap finding": recorre los IDs existentes en orden ascendente
     * y devuelve el primer número que no está presente. Esto permite reutilizar
     * IDs de usuarios eliminados sin depender de AUTO_INCREMENT.
     */
    private static function obtenerSiguienteIdLibre() {
        $conexion = Conexion::conectar();
        $stmt = $conexion->query("SELECT id_usuario FROM usuarios ORDER BY id_usuario");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $expected = 1;
        foreach ($ids as $id) {
            if ($id > $expected) {
                return $expected;
            }
            $expected = $id + 1;
        }
        return $expected;
    }

    /*
     * Crea un nuevo usuario en la base de datos.
     * En lugar de usar AUTO_INCREMENT, se calcula el ID mediante obtenerSiguienteIdLibre()
     * para reutilizar IDs de usuarios previamente eliminados, evitando huecos en la numeración.
     * La contraseña se almacena hasheada con PASSWORD_DEFAULT (bcrypt).
     */
    public static function crear($nombre, $email, $password, $rol) {
        $conexion = Conexion::conectar();
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $nuevoId = self::obtenerSiguienteIdLibre();
        $stmt = $conexion->prepare("INSERT INTO usuarios (id_usuario, nombre, email, password_hash, rol) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$nuevoId, $nombre, $email, $password_hash, $rol]);
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
            $usuarios[] = new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['password_hash'], $fila['rol'], $fila['forzar_cambio_password'] ?? 0);
        }
        return $usuarios;
    }

    // Marca al usuario para que deba cambiar su contraseña en el próximo inicio de sesión.
    // Útil cuando un administrador restablece credenciales o crea un usuario nuevo.
    public static function forzarCambioPassword($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE usuarios SET forzar_cambio_password = 1 WHERE id_usuario = ?");
        return $stmt->execute([$id]);
    }

    // Elimina la marca de cambio forzado de contraseña.
    // Se invoca automáticamente cuando el usuario completa el cambio de contraseña.
    public static function limpiarForzarCambioPassword($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE usuarios SET forzar_cambio_password = 0 WHERE id_usuario = ?");
        return $stmt->execute([$id]);
    }

    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getEmail() { return $this->email; }
    public function getPasswordHash() { return $this->password_hash; }
    public function getRol() { return $this->rol; }
    public function getForzarCambioPassword() { return $this->forzar_cambio_password; }
}
