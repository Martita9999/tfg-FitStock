<?php
require_once "models/Usuario.php";

class UsuarioController {
    
    public function listar() {
        $usuarios = Usuario::obtenerTodos();
        require "views/usuarios/list.php";
    }

    public function crear() {
        require "views/usuarios/form.php";
    }

    // --- NUEVO MÉTODO PARA PROCESAR EL FORMULARIO ---
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Recogemos los datos del formulario (names del input)
            $nombre   = $_POST['nombre'];
            $email    = $_POST['email'];
            $password = $_POST['password'];
            $rol      = $_POST['rol'];

            // Llamamos al método crear del modelo
            $exito = Usuario::crear($nombre, $email, $password, $rol);

            if ($exito) {
                // Si sale bien, volvemos al listado
                header("Location: index.php?c=Usuario&a=listar");
            } else {
                echo "Hubo un error al guardar el usuario.";
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