<?php
    require_once "../models/Usuario.php";

    class UsuarioController{
        public function listar() {
            $usuarios = Usuario::obtenerTodos();
            require "../views/usuarios/list.php";
        }

        
        public function crear() {
            require "../views/usuarios/form.php";
        }

       
        public function guardar() {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nombre= $_POST['nombre'];
                $email= $_POST['email'];
                $rol= $_POST['rol'] ?? 'cliente';
                
                $password_hash= password_hash($_POST['password'], PASSWORD_DEFAULT);
                $resultado= Usuario::crear($nombre, $email, $password_hash, $rol);


                if ($resultado) {
                    header("Location: index.php?view=usuarios_listar");
                    exit;
                } else {
                    $error = "Error al crear el usuario";
                    require "../views/usuarios/form.php";
                }
            }
        }

    
        public function editar() {
            $id = $_GET['id'] ?? null;
            
            if ($id) {
                $usuario = Usuario::obtenerPorId($id);
                require "../views/usuarios/form.php";
            } else {
                header("Location: index.php?view=usuarios_listar");
            }
        }

       
        public function actualizar() {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id= $_POST['id'];
                $nombre= $_POST['nombre'];
                $email= $_POST['email'];
                $rol= $_POST['rol'] ?? 'cliente';

             
                Usuario::actualizar($id, $nombre, $email, $rol);   

                header("Location: index.php?view=usuarios_listar");
                exit;
            }
        }

    
        public function eliminar() {
            $id = $_GET['id'] ?? null;
            
            if ($id) {
                Usuario::eliminar($id);   
            }
            
            header("Location: index.php?view=usuarios_listar");
            exit;
        }
    }
?>