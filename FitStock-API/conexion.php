<?php
// Configuración de la base de datos MySQL para PRODUCCIÓN (Arsys)
$DB_HOST = "qaqo430.chomsky.es";   // Host real de Arsys
$DB_NAME = "qaqo430";              // Nombre de la base de datos
$DB_USER = "qaqo430";              // Usuario de la base de datos
$DB_PASS = "17J16a8m28g!";         // Tu contraseña real

// Función que crea y devuelve una conexión PDO a la base de datos
function obtenerConexion() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    try {
        // Usamos PDO con los datos del servidor de Arsys
        $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
        
        // Configuraciones de seguridad y error
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    } catch (PDOException $e) {
        // En producción, podrías registrar el error en un log, pero por ahora lo mostramos para depurar
        die("Error de conexión en el servidor: " . $e->getMessage());
    }
}

// Clase estática de utilidad para obtener la conexión
class Conexion {
    public static function conectar() {
        return obtenerConexion();
    }
}
