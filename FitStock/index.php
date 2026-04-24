<?php
// index.php
session_start();
require_once "conexion.php"; 

// Capturamos controlador y acción
$c = isset($_GET['c']) ? $_GET['c'] : 'Usuario';
$a = isset($_GET['a']) ? $_GET['a'] : 'listar';

$nombreControlador = ucfirst(strtolower($c)) . "Controller";
$archivoControlador = "controllers/" . $nombreControlador . ".php";

if (file_exists($archivoControlador)) {
    require_once $archivoControlador;
    if (class_exists($nombreControlador)) {
        $objeto = new $nombreControlador();
        if (method_exists($objeto, $a)) {
            $objeto->$a(); 
        } else {
            echo "La acción no existe.";
        }
    }
} else {
    // Si no existe el controlador, redirigimos al listado por defecto
    header("Location: index.php?c=Usuario&a=listar");
    exit;
}
?>