<?php
// FitStock/api/logout.php

session_start(); // Es obligatorio iniciar la sesión para poder destruirla

// 1. Usamos tu conexión estándar. 
// Subimos un nivel (../) porque este archivo está en la carpeta 'api'
if (file_exists("../conexion.php")) {
    require_once "../conexion.php";
}

// 2. Limpieza total de la sesión
$_SESSION = array(); // Vacía el array de sesión
session_unset();     // Libera las variables
session_destroy();   // Destruye la sesión en el servidor

// 3. Respuesta en formato JSON para que el frontend sepa que ha terminado
header('Content-Type: application/json');
echo json_encode([
    "success" => true,
    "message" => "Sesión cerrada correctamente"
]);
exit();
?>