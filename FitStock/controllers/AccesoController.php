<?php
    require_once "../models/Acceso.php";

    class AccesoController {
        public function listar(){
            $accesos = Acceso::obtenerTodos();
            require "../views/accesos/list.php";
        }

        public function crear(){
            require "../views/accesos/form.php";
        }

        public function guardar(){
            if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            
                $id_usuario = (int)$_POST['id_usuario'];
                
                Acceso::registrar($id_usuario);

               
                header("Location: index.php?view=accesos_listar");
                exit;
            }
        }

        public function eliminar(){
            $id = $_GET['id'] ?? null;
            if ($id) {
                Acceso::eliminar($id);
            }
            header("Location: index.php?view=accesos_listar");
            exit;
        }
    }
?>