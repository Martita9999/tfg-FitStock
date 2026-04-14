<?php
session_start();
require_once "conexion.php"; 

$c = isset($_GET['c']) ? $_GET['c'] : 'Usuario';
$a = isset($_GET['a']) ? $_GET['a'] : 'listar';

$nombreControlador = ucfirst(strtolower($c)) . "Controller";
$archivoControlador = "controllers/" . $nombreControlador . ".php";

if (file_exists($archivoControlador)) {
    require_once $archivoControlador;
    $objeto = new $nombreControlador();
    if (method_exists($objeto, $a)) {
        $objeto->$a(); // Esto llama al controlador, y el controlador carga la vista
    }
} else {
    header("Location: index.php?c=usuario&a=listar");
}