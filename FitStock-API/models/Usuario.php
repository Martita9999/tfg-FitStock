<?php
/* ----------------------------------------------------
 * Modelo Usuario
 * ----------------------------------------------------
 * El modelo más importante del sistema.
 * Gestiona todo lo relacionado con los usuarios:
 * registro, login, roles y cambio de contraseña.
 *
 * Algo curioso de este modelo: en lugar de usar
 * AUTO_INCREMENT de MySQL, nosotros mismos calculamos
 * el siguiente ID libre para reutilizar IDs de usuarios
 * eliminados (evitamos huecos en la numeración).
 *
 * Roles disponibles: admin, entrenador, cliente.
 * ---------------------------------------------------- */

require_once __DIR__ . "/../conexion.php";  // Traemos la conexión a la BD

class Usuario {
    /* Propiedades privadas del usuario */
    private $id;                           // ID numérico del usuario en la BD
    private $nombre;                       // Nombre completo
    private $email;                        // Email único (login)
    private $password_hash;                // Contraseña guardada con bcrypt
    private $rol;                          // admin, entrenador o cliente
    private $forzar_cambio_password;       // 1 = tiene que cambiar contraseña al entrar

    /* Constructor: creamos el objeto usuario con todos sus datos.
       forzar_cambio_password es opcional y por defecto vale 0 (no forzar). */
    public function __construct($id, $nombre, $email, $password_hash, $rol, $forzar_cambio_password = 0) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->password_hash = $password_hash;
        $this->rol = $rol;
        $this->forzar_cambio_password = $forzar_cambio_password;
    }

    /* buscarPorId(): busca un usuario por su ID numérico en la BD.
       Usamos consultas preparadas con ? para evitar SQL injection. */
    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();                        // Abrimos conexión
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");  // Consulta con placeholder
        $stmt->execute([$id]);                                   // Pasamos el ID como parámetro seguro
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);                  // Obtenemos la fila como array asociativo
        if ($fila) {
            return new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['password_hash'], $fila['rol'], $fila['forzar_cambio_password'] ?? 0);
        }
        return null;                                             // Si no existe, devolvemos null
    }

    /* buscarPorEmail(): busca usuario por email (único en BD).
       Fundamental para el login: primero localizamos por email,
       luego verificamos la contraseña con password_verify(). */
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

    /* calcularSiguienteId(): implementa el "gap finding".
       En lugar de AUTO_INCREMENT, buscamos el primer ID libre
       recorriendo los existentes. Ej: si hay IDs 1,2,5, devuelve 3.
       Así reutilizamos IDs de usuarios eliminados. */
    private static function obtenerSiguienteIdLibre() {
        $conexion = Conexion::conectar();
        $stmt = $conexion->query("SELECT id_usuario FROM usuarios ORDER BY id_usuario");  // IDs ordenados
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);                          // Los pasamos a array simple
        $expected = 1;                                                       // Empezamos por el 1
        foreach ($ids as $id) {                                              // Recorremos los IDs existentes
            if ($id > $expected) {                                           // Si hay un salto...
                return $expected;                                            // ...ese es el primer hueco libre
            }
            $expected = $id + 1;                                             // Siguiente número esperado
        }
        return $expected;                                                    // Si no hay huecos, devuelve el siguiente
    }

    /* crear(): añade un nuevo usuario a la BD.
       1. Calculamos el ID con obtenerSiguienteIdLibre()
       2. La contraseña se hashea con bcrypt (NUNCA texto plano)
       3. Los nuevos usuarios se crean con forzar_cambio_password = 0 */
    public static function crear($nombre, $email, $password, $rol) {
        $conexion = Conexion::conectar();
        $password_hash = password_hash($password, PASSWORD_DEFAULT);  // Hasheamos con bcrypt
        $nuevoId = self::obtenerSiguienteIdLibre();                   // Calculamos ID libre
        $stmt = $conexion->prepare("INSERT INTO usuarios (id_usuario, nombre, email, password_hash, rol) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$nuevoId, $nombre, $email, $password_hash, $rol]);
    }

    /* actualizarAdmin(): modifica datos de usuario desde el panel admin.
       La SQL se construye dinámicamente: solo añadimos password_hash
       y rol si se proporcionan, evitando sobrescribir con NULL. */
    public static function actualizarAdmin($id, $nombre, $email, $password = null, $rol = null) {
        $conexion = Conexion::conectar();
        $campos = [];                                                    // Lista de campos a actualizar
        $valores = [];                                                   // Valores correspondientes
        $campos[] = "nombre = ?";
        $valores[] = $nombre;
        $campos[] = "email = ?";
        $valores[] = $email;
        if ($password) {                                                 // Si pasan contraseña nueva...
            $campos[] = "password_hash = ?";
            $valores[] = password_hash($password, PASSWORD_DEFAULT);     // ...la hasheamos
        }
        if ($rol) {                                                      // Si pasan rol nuevo...
            $campos[] = "rol = ?";
            $valores[] = $rol;
        }
        $valores[] = $id;
        $sql = "UPDATE usuarios SET " . implode(", ", $campos) . " WHERE id_usuario = ?";  // SQL dinámica
        $stmt = $conexion->prepare($sql);
        return $stmt->execute($valores);
    }

    /* eliminar(): borra un usuario por su ID */
    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        return $stmt->execute([$id]);
    }

    /* listarTodos(): devuelve todos los usuarios ordenados por nombre.
       Se usa en la gestión de usuarios del panel de admin. */
    public static function obtenerTodos() {
        $conexion = Conexion::conectar();
        $stmt = $conexion->query("SELECT * FROM usuarios ORDER BY nombre");
        $usuarios = [];                                                  // Array donde guardamos los objetos
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $usuarios[] = new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['password_hash'], $fila['rol'], $fila['forzar_cambio_password'] ?? 0);
        }
        return $usuarios;
    }

    /* forzarCambioPassword(): marca a un usuario para que deba cambiar
       la contraseña en el próximo login. Lo usa el admin al crear
       usuarios o por seguridad al resetear credenciales. */
    public static function forzarCambioPassword($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE usuarios SET forzar_cambio_password = 1 WHERE id_usuario = ?");
        return $stmt->execute([$id]);
    }

    /* Getters para acceder a las propiedades privadas desde fuera */
    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getEmail() { return $this->email; }
    public function getPasswordHash() { return $this->password_hash; }
    public function getRol() { return $this->rol; }
    public function getForzarCambioPassword() { return $this->forzar_cambio_password; }
}
