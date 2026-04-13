<?php
    require_once "../config/database.sql";

    class Producto {
        private $id_producto;
        private $nombre_prod;
        private $cant_actual;
        private $stock_minimo;
        private $precio;

       
        public function __construct($id_producto, $nombre_prod, $cant_actual, $stock_minimo, $precio){
            $this->id_producto= $id_producto;
            $this->nombre_prod= $nombre_prod;
            $this->cant_actual= $cant_actual;
            $this->stock_minimo= $stock_minimo;
            $this->precio= $precio;
        }

      
        public static function obtenerTodos(){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("SELECT * FROM productos_stock ORDER BY nombre_prod");
            $stmt->execute();
            
            $productos = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
                $productos[] = new Producto($fila['id_producto'], $fila['nombre_prod'], $fila['cant_actual'], $fila['stock_minimo'], $fila['precio']);
            }
            return $productos;
        }

       
        public static function obtenerPorId($id){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("SELECT * FROM productos_stock WHERE id_producto = ?");
            $stmt->execute([$id]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila){
                return new Producto($fila['id_producto'], $fila['nombre_prod'], $fila['cant_actual'], $fila['stock_minimo'], $fila['precio']);
            }
            return null;
        }

        
        public static function obtenerStockBajo(){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("SELECT * FROM productos_stock 
                                        WHERE cant_actual <= stock_minimo 
                                        ORDER BY nombre_prod");
            $stmt->execute();
            
            $productos = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
                $productos[] = new Producto($fila['id_producto'], $fila['nombre_prod'], $fila['cant_actual'], $fila['stock_minimo'], $fila['precio']);
            }
            return $productos;
        }

       
        public static function crear($nombre_prod, $cant_actual, $stock_minimo, $precio){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("INSERT INTO productos_stock 
                                        (nombre_prod, cant_actual, stock_minimo, precio) 
                                        VALUES (?, ?, ?, ?)");
            
            $resultado = $stmt->execute([$nombre_prod, $cant_actual, $stock_minimo, $precio]);
            
            if ($resultado){
                return $conexion->lastInsertId();
            }
            return false;
        }

       
        public static function actualizarStock($id_producto, $nueva_cantidad){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("UPDATE productos_stock 
                                        SET cant_actual = ? 
                                        WHERE id_producto = ?");
            
            return $stmt->execute([$nueva_cantidad, $id_producto]);
        }

         public static function eliminar($id){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("DELETE FROM productos WHERE id_producto = ?");
            return $stmt->execute([$id]);
        }
      
        public function getId(){
            return $this->id_producto;
        }

        public function getNombre(){
            return $this->nombre_prod;
        }

        public function getCantidadActual(){
            return $this->cant_actual;
        }

        public function getStockMinimo(){
            return $this->stock_minimo;
        }

        public function getPrecio(){
            return $this->precio;
        }

       
        public function tieneStockBajo(){
            return $this->cant_actual <= $this->stock_minimo;
        }
    }
?>