<?php
// controllers/UsuarioController.php
require_once "models/Usuario.php";

class UsuarioController {
    
    public function listar() {
        $usuarios = Usuario::obtenerTodos();
        require "views/usuarios/list.php";
    }

    public function crear() {
        require "views/usuarios/form.php";
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre   = $_POST['nombre'];
            $email    = $_POST['email'];
            $password = $_POST['password'];
            $rol      = $_POST['rol']; // Captura el rol del select

            if (Usuario::crear($nombre, $email, $password, $rol)) {
                header("Location: index.php?c=Usuario&a=listar");
            } else {
                echo "Error al guardar el usuario.";
            }
        }
    }

    public function eliminar() {
        if (isset($_GET['id'])) {
            Usuario::eliminar($_GET['id']);
        }
        header("Location: index.php?c=Usuario&a=listar");
    }
}