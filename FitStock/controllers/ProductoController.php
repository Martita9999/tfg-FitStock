<?php
    require_once "../models/Producto.php";

    class ProductoController{

        public function listar(){
            $productos = Producto::obtenerTodos();
            require "../views/productos/list.php";
        }

        public function crear(){
            require "../views/productos/form.php";
        }

        public function guardar(){
            if ($_SERVER['REQUEST_METHOD'] === 'POST'){
                $nombre_prod= $_POST['nombre_prod'];
                $cant_actual= (int)$_POST['cant_actual'];
                $stock_minimo= (int)$_POST['stock_minimo'];
                $precio= (float)$_POST['precio'];

                Producto::crear($nombre_prod, $cant_actual, $stock_minimo, $precio);

                header("Location: index.php?view=productos_listar");
                exit;
            }
        }

        public function eliminar(){
            $id = $_GET['id'] ?? null;
            if ($id) {
                Producto::eliminar($id);   
            }
            header("Location: index.php?view=productos_listar");
            exit;
        }
    }
?>    