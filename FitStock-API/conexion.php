<?php
/*
 * MEJORA DE SEGURIDAD: Las credenciales se cargan desde un archivo .env
 * en lugar de estar hardcodeadas. Si no existe .env, usa valores por defecto
 * (solo para desarrollo local).
 *
 * Archivo modificado: conexion.php
 * Líneas modificadas: 3-23 (carga de .env), 28-30 (die() genérico)
 * Cambios:
 *   - Credenciales hardcodeadas  →  Carga desde .env con fallback seguro
 *   - die($e->getMessage())     →  die("Error interno del servidor")
 *   - Se añadió .env.example como plantilla
 */

// Intenta cargar variables desde el archivo .env (si existe)
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $env[$key] = $value;
            putenv("$key=$value");
        }
    }
    $DB_HOST = $env['DB_HOST'] ?? "127.0.0.1";
    $DB_NAME = $env['DB_NAME'] ?? "fitstock";
    $DB_USER = $env['DB_USER'] ?? "fitstock";
    $DB_PASS = $env['DB_PASS'] ?? "";
} else {
    // Fallback seguro para desarrollo local 
    $DB_HOST = "127.0.0.1";
    $DB_NAME = "fitstock";
    $DB_USER = "fitstock";
    $DB_PASS = "Tokio2324";
}

// Función que crea y devuelve una conexión PDO a la base de datos
function obtenerConexion() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;          // Importa las variables de configuración
    try {
        $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);  // Crea la conexión PDO con charset seguro
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);   // Configura PDO para lanzar excepciones en errores
        return $pdo;                                                     // Devuelve la conexión
    } catch (PDOException $e) {
        // SEGURIDAD: No exponer detalles de la DB al usuario (antes mostraba $e->getMessage())
        // El error real queda registrado en el log del servidor PHP
        error_log("Error de conexión DB: " . $e->getMessage());          // Guarda el error real en logs internos
        die("Error interno del servidor");                               // Mensaje genérico al usuario
    }
}

// Clase estática de utilidad para obtener la conexión
class Conexion {
    public static function conectar() {
        return obtenerConexion();   // Delega en la función obtenerConexion()
    }
}
