<?php
require_once "models/Usuario.php";

class UsuarioController {
    public function listar() {
        // Obtenemos los datos antes de cargar la vista
        $usuarios = Usuario::obtenerTodos();
        require "views/usuarios/list.php";
    }

    public function crear() {
        require "views/usuarios/form.php";
    }

    public function eliminar() {
        if (isset($_GET['id'])) {
            Usuario::eliminar($_GET['id']);
        }
        header("Location: index.php?c=usuario&a=listar");
    }
}