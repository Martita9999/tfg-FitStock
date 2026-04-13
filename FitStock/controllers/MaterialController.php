<?php
    require_once "../models/Material.php";

    class MaterialController {

        public function listar(){
            $materiales = Material::obtenerTodos();
            require "../views/material/list.php";
        }

        public function crear(){
            require "../views/material/form.php";
        }

        public function guardar(){
            if ($_SERVER['REQUEST_METHOD'] === 'POST'){
                $nombre_equipo= $_POST['nombre_equipo'];
                $descripcion= $_POST['descripcion'] ?? '';
                $estado= $_POST['estado'] ?? 'operativo';
                
                
                $qr_identificador = "MAT-" . date("YmdHis");

                Material::crear($nombre_equipo, $descripcion, $estado, $qr_identificador);

                header("Location: index.php?view=material_listar");
                exit;
            }
        }

        public function eliminar(){
            $id = $_GET['id'] ?? null;
            if ($id) {
                Material::eliminar($id);
            }
            header("Location: index.php?view=material_listar");
            exit;
        }
    }
?>