<?php
session_start();
require_once "conexion.php"; 

$c = $_GET['c'] ?? 'Usuario';
$a = $_GET['a'] ?? 'listar';

$nombreControlador = ucfirst(strtolower($c)) . "Controller";
$archivoControlador = "controllers/" . $nombreControlador . ".php";

if (file_exists($archivoControlador)) {
    require_once $archivoControlador;
    $objeto = new $nombreControlador();
    $objeto->$a();
} else {
    header("Location: index.php?c=usuario&a=listar");
}