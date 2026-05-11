<?php
require_once __DIR__ . "/../conexion.php";   // Importa la conexión a la base de datos

// Clase modelo para la tabla 'productos_stock' - gestiona productos en stock del gimnasio
class Producto {
    private $id_producto;    // ID único del producto
    private $nombre_prod;    // Nombre del producto
    private $descripcion;    // Descripción breve del producto
    private $cant_actual;    // Cantidad actual en stock
    private $stock_minimo;   // Cantidad mínima antes de alertar
    private $precio;         // Precio unitario del producto

    // Constructor: asigna todos los valores al crear una instancia
    public function __construct($id_producto, $nombre_prod, $descripcion, $cant_actual, $stock_minimo, $precio) {
        $this->id_producto = $id_producto;
        $this->nombre_prod = $nombre_prod;
        $this->descripcion = $descripcion;
        $this->cant_actual = $cant_actual;
        $this->stock_minimo = $stock_minimo;
        $this->precio = $precio;
    }

    // Obtiene todos los productos ordenados por nombre
    public static function obtenerTodos() {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM productos_stock ORDER BY nombre_prod");
        $stmt->execute();
        $productos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {   // Itera sobre cada fila
            $productos[] = new Producto($fila['id_producto'], $fila['nombre_prod'], $fila['descripcion'] ?? null, $fila['cant_actual'], $fila['stock_minimo'], $fila['precio']);
        }
        return $productos;   // Devuelve array de objetos Producto
    }

    // Busca un producto por su ID
    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM productos_stock WHERE id_producto = ?");
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Producto($fila['id_producto'], $fila['nombre_prod'], $fila['descripcion'] ?? null, $fila['cant_actual'], $fila['stock_minimo'], $fila['precio']);
        }
        return null;
    }

    // Obtiene productos con stock por debajo del mínimo
    public static function obtenerStockBajo() {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM productos_stock WHERE cant_actual <= stock_minimo ORDER BY nombre_prod");
        $stmt->execute();
        $productos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = new Producto($fila['id_producto'], $fila['nombre_prod'], $fila['descripcion'] ?? null, $fila['cant_actual'], $fila['stock_minimo'], $fila['precio']);
        }
        return $productos;
    }

    // Crea un nuevo producto en la base de datos
    public static function crear($nombre_prod, $descripcion, $cant_actual, $stock_minimo, $precio) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO productos_stock (nombre_prod, descripcion, cant_actual, stock_minimo, precio) VALUES (?, ?, ?, ?, ?)");
        $resultado = $stmt->execute([$nombre_prod, $descripcion, $cant_actual, $stock_minimo, $precio]);
        if ($resultado) {
            return $conexion->lastInsertId();   // Devuelve el ID del nuevo producto
        }
        return false;
    }

    // Actualiza la cantidad de stock de un producto
    public static function actualizarStock($id_producto, $nueva_cantidad) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE productos_stock SET cant_actual = ? WHERE id_producto = ?");
        return $stmt->execute([$nueva_cantidad, $id_producto]);
    }

    // Actualiza todos los campos de un producto
    public static function actualizar($id_producto, $nombre, $descripcion, $cantidad, $stock_minimo, $precio) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE productos_stock SET nombre_prod = ?, descripcion = ?, cant_actual = ?, stock_minimo = ?, precio = ? WHERE id_producto = ?");
        return $stmt->execute([$nombre, $descripcion, $cantidad, $stock_minimo, $precio, $id_producto]);
    }

    // Elimina un producto por su ID
    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM productos_stock WHERE id_producto = ?");
        return $stmt->execute([$id]);
    }

    // Getters para acceder a las propiedades privadas
    public function getId() { return $this->id_producto; }
    public function getNombre() { return $this->nombre_prod; }
    public function getDescripcion() { return $this->descripcion; }
    public function getCantidadActual() { return $this->cant_actual; }
    public function getStockMinimo() { return $this->stock_minimo; }
    public function getPrecio() { return $this->precio; }
    public function tieneStockBajo() { return $this->cant_actual <= $this->stock_minimo; }  // Comprueba si el stock está bajo
}
