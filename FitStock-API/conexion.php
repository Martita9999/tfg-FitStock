<?php
// Configuración de la base de datos MySQL
$DB_HOST = "127.0.0.1";   // Dirección del servidor de base de datos (localhost)
$DB_NAME = "fitstock";     // Nombre de la base de datos
$DB_USER = "fitstock";     // Usuario de la base de datos
$DB_PASS = "Tokio2324";     // Contraseña del usuario de la base de datos

// Función que crea y devuelve una conexión PDO a la base de datos
function obtenerConexion() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;          // Importa las variables de configuración
    try {
        $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);  // Crea la conexión PDO
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);   // Configura PDO para lanzar excepciones en errores
        return $pdo;                                                     // Devuelve la conexión
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());                   // Muestra error y detiene la ejecución si falla
    }
}

// Clase estática de utilidad para obtener la conexión
class Conexion {
    public static function conectar() {
        return obtenerConexion();   // Delega en la función obtenerConexion()
    }
}
