<?php
    require_once "../models/Prestamo.php";

    class PrestamoController{

        public function listar(){
            $prestamos = Prestamo::obtenerTodos();
            require "../views/prestamos/list.php";
        }

        public function listarActivos(){
            $prestamos = Prestamo::obtenerActivos();
            require "../views/prestamos/list.php";
        }

        public function crear(){
            require "../views/prestamos/form.php";
        }

        public function guardar(){
            if ($_SERVER['REQUEST_METHOD'] === 'POST'){
                $id_usuario= $_POST['id_usuario'];
                $id_material= $_POST['id_material'];
                $fecha_devolucion= $_POST['fecha_devolucion'];

                Prestamo::crear($id_usuario, $id_material, $fecha_devolucion);

                header("Location: index.php?view=prestamos_listar");
                exit;
            }
        }

        public function devolver(){
            $id_prestamo = $_GET['id'] ?? null;

            if ($id_prestamo) {
                Prestamo::devolver($id_prestamo);
            }

            header("Location: index.php?view=prestamos_listar");
            exit;
        }
    }
?>