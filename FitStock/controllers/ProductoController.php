<?php
// FitStock/controllers/ProductoController.php

// 1. Quitamos el "../" porque el index ya está en la raíz
require_once "models/Producto.php";

class ProductoController {

    public function listar() {
        $productos = Producto::obtenerTodos();
        // 2. Quitamos el "../"
        require "views/productos/list.php";
    }

    public function crear() {
        // 3. Quitamos el "../"
        require "views/productos/form.php";
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre_prod = $_POST['nombre_prod'];
            $cant_actual = (int)$_POST['cant_actual'];
            $stock_minimo = (int)$_POST['stock_minimo'];
            $precio      = (float)$_POST['precio'];

            Producto::crear($nombre_prod, $cant_actual, $stock_minimo, $precio);

            // 4. Cambiamos la ruta para que use tu sistema de controladores
            header("Location: index.php?c=producto&a=listar");
            exit;
        }
    }

    public function eliminar() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            Producto::eliminar($id);   
        }
        // 5. Cambiamos la ruta aquí también
        header("Location: index.php?c=producto&a=listar");
        exit;
    }
}