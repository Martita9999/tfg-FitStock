<?php
// conexion.php (En la raíz)
function obtenerConexion() {
    $host = "localhost";
    $db   = "fitstock_db"; 
    $user = "root";
    $pass = "";
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}