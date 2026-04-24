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
            // 1. Captura de datos con valores por defecto para evitar errores
            $nombre   = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            
            // 2. Limpieza del ROL (Esto arregla lo de 'entrenador')
            $rol = isset($_POST['rol']) ? strtolower(trim($_POST['rol'])) : 'cliente';

            // 3. Validación mínima antes de llamar al modelo
            if (!empty($nombre) && !empty($email) && !empty($password)) {
                
                // Llamamos al método crear pasándole los 4 parámetros exactos
                if (Usuario::crear($nombre, $email, $password, $rol)) {
                    header("Location: index.php?c=Usuario&a=listar");
                    exit;
                } else {
                    echo "Error: No se pudo insertar en la base de datos.";
                }
            } else {
                echo "Error: Todos los campos son obligatorios.";
            }
        }
    }

    public function eliminar() {
        if (isset($_GET['id'])) {
            Usuario::eliminar($_GET['id']);
        }
        header("Location: index.php?c=Usuario&a=listar");
        exit;
    }
}