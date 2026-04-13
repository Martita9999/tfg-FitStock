<?php
    require_once "../models/Incidencia.php";

    class IncidenciaController {

        public function listar(){
            $incidencias = Incidencia::obtenerTodos();
            require "../views/incidencias/list.php";
        }

        public function crear(){
            require "../views/incidencias/form.php";
        }

        public function guardar(){
            if ($_SERVER['REQUEST_METHOD'] === 'POST'){
                $id_material= (int)$_POST['id_material'];
                $id_user_rep= (int)$_POST['id_user_rep'];
                $descripcion= $_POST['descripcion'];
                $prioridad= $_POST['prioridad'];
                $estado_inc= $_POST['estado_inc']; 

               
                Incidencia::crear($id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc);

               
                header("Location: index.php?view=incidencias_listar");
                exit;
            }
        }

        public function eliminar(){
            $id = $_GET['id'] ?? null;
            if ($id){
                Incidencia::eliminar($id);
            }
            header("Location: index.php?view=incidencias_listar");
            exit;
        }
    }
?>