<?php
/*
 * Conexión a la base de datos de FitStock.
 * Este archivo gestiona cómo nos conectamos a MySQL usando PDO,
 * y carga las credenciales desde un archivo .env por seguridad.
 * 
 * Os explico por qué es importante cada parte: desde cómo se
 * cargan las variables de entorno hasta cómo manejamos los errores
 * de conexión sin exponer información sensible.
 */

/*
 * Carga de variables desde el archivo .env:
 * Si existe un archivo .env en la raíz del proyecto, lo leemos línea
 * por línea y extraemos las variables. Las líneas que empiezan por #
 * son comentarios y las ignoramos.
 * 
 * Esto es más seguro que tener las credenciales hardcodeadas aquí,
 * porque el .env no se sube a GitHub (está en .gitignore) y cada
 * entorno (desarrollo, producción) tiene su propio .env.
 */
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
    /*
     * Asignamos las variables con el operador ?? (null coalescing):
     * si la clave no existe en el array $env, usamos el valor por defecto.
     * Esto evita errores si alguien se olvida de definir alguna variable.
     */
    $DB_HOST = $env['DB_HOST'] ?? "127.0.0.1";
    $DB_NAME = $env['DB_NAME'] ?? "fitstock";
    $DB_USER = $env['DB_USER'] ?? "fitstock";
    $DB_PASS = $env['DB_PASS'] ?? "";
} else {
    /*
     * Fallback para desarrollo local:
     * Solo se usa si no hay archivo .env. En producción nunca debería
     * llegar aquí, porque tendremos el .env configurado correctamente.
     */
    $DB_HOST = "127.0.0.1";
    $DB_NAME = "fitstock";
    $DB_USER = "fitstock";
    $DB_PASS = "Tokio2324";
}

/*
 * Función obtenerConexion():
 * Crea y devuelve una conexión PDO a la base de datos MySQL.
 * 
 * Uso: $pdo = obtenerConexion();
 * 
 * La conexión usa charset utf8mb4 para que los emojis y caracteres
 * especiales (acentos, ñ) se guarden y recuperen correctamente.
 * 
 * Configuramos PDO para que lance excepciones (ERRMODE_EXCEPTION)
 * en lugar de errores silenciosos. Así podemos capturarlas con
 * try-catch y manejarlas adecuadamente.
 */
function obtenerConexion() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    try {
        $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        /*
         * Seguridad: nunca mostrar detalles de la base de datos al usuario.
         * Si mostráramos $e->getMessage(), un atacante podría ver el nombre
         * de la base de datos, usuario, o incluso la contraseña en algunos casos.
         * 
         * En su lugar, guardamos el error real en el log de PHP (error_log)
         * para que el administrador pueda depurar, y mostramos un mensaje
         * genérico "Error interno del servidor" al usuario.
         */
        error_log("Error de conexión DB: " . $e->getMessage());
        die("Error interno del servidor");
    }
}

/*
 * Clase Conexion:
 * Un wrapper estático para obtener la conexión. La ventaja de tener
 * una clase es que podemos llamar a Conexion::conectar() desde cualquier
 * parte del código sin necesidad de incluir la función global.
 * 
 * También facilita hacer mocking en tests si algún día los escribimos.
 */
class Conexion {
    public static function conectar() {
        return obtenerConexion();
    }
}
