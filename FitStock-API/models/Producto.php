<?php
/* ----------------------------------------------------
 * Modelo Producto
 * ----------------------------------------------------
 * Gestiona los productos que se venden en la tienda del
 * gimnasio (suplementos, bebidas, etc.).
 *
 * Cada producto tiene:
 *   - Nombre y descripción
 *   - Cantidad actual en stock
 *   - Stock mínimo para saber cuándo reponer
 *   - Precio unitario
 *
 * El dashboard usa este modelo para alertar cuando
 * un producto tiene stock bajo (cant_actual <= stock_minimo).
 * ---------------------------------------------------- */

require_once __DIR__ . "/../conexion.php";  // Traemos la conexión a la BD

class Producto {
    /* Propiedades que se corresponden con la tabla 'productos_stock' */
    private $id_producto;                 // ID único del producto
    private $nombre_prod;                 // Nombre del producto
    private $descripcion;                 // Descripción breve
    private $cant_actual;                 // Cuántas unidades hay ahora
    private $stock_minimo;                // Mínimo antes de alertar
    private $precio;                      // Precio por unidad

    /* Constructor: guarda todos los valores al crear el objeto */
    public function __construct($id_producto, $nombre_prod, $descripcion, $cant_actual, $stock_minimo, $precio) {
        $this->id_producto = $id_producto;  // Guardamos el ID
        $this->nombre_prod = $nombre_prod;  // Guardamos el nombre
        $this->descripcion = $descripcion;  // Guardamos la descripción
        $this->cant_actual = $cant_actual;  // Guardamos el stock actual
        $this->stock_minimo = $stock_minimo;  // Guardamos el mínimo
        $this->precio = $precio;            // Guardamos el precio
    }

    /* listarTodos(): devuelve todos los productos ordenados por nombre.
       Se usa en el listado de la tienda. */
    public static function obtenerTodos() {
        $conexion = Conexion::conectar();             // Abrimos conexión
        $stmt = $conexion->prepare("SELECT * FROM productos_stock ORDER BY nombre_prod");
        $stmt->execute();
        $productos = [];                               // Array donde guardamos los objetos
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = new Producto($fila['id_producto'], $fila['nombre_prod'], $fila['descripcion'] ?? null, $fila['cant_actual'], $fila['stock_minimo'], $fila['precio']);
        }
        return $productos;                             // Devolvemos la lista completa
    }

    /* buscarPorId(): busca un producto por su ID.
       Consulta parametrizada para evitar SQL injection. */
    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM productos_stock WHERE id_producto = ?");  // Placeholder seguro
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Producto($fila['id_producto'], $fila['nombre_prod'], $fila['descripcion'] ?? null, $fila['cant_actual'], $fila['stock_minimo'], $fila['precio']);
        }
        return null;                                   // Si no existe, null
    }

    /* obtenerStockBajo(): productos con cantidad <= stock mínimo.
       Se usa en el dashboard para alertar de reposición. */
    public static function obtenerStockBajo() {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM productos_stock WHERE cant_actual <= stock_minimo ORDER BY nombre_prod");  // Filtro stock bajo
        $stmt->execute();
        $productos = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = new Producto($fila['id_producto'], $fila['nombre_prod'], $fila['descripcion'] ?? null, $fila['cant_actual'], $fila['stock_minimo'], $fila['precio']);
        }
        return $productos;
    }

    /* crear(): añade un nuevo producto a la tienda.
       Devuelve el ID del producto recién creado. */
    public static function crear($nombre_prod, $descripcion, $cant_actual, $stock_minimo, $precio) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO productos_stock (nombre_prod, descripcion, cant_actual, stock_minimo, precio) VALUES (?, ?, ?, ?, ?)");
        $resultado = $stmt->execute([$nombre_prod, $descripcion, $cant_actual, $stock_minimo, $precio]);
        if ($resultado) {
            return $conexion->lastInsertId();            // Devolvemos el ID generado
        }
        return false;                                    // Si falla, false
    }

    /* actualizarStock(): cambia solo la cantidad de un producto.
       Se usa al comprar (descontar) o al reponer inventario. */
    public static function actualizarStock($id_producto, $nueva_cantidad) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE productos_stock SET cant_actual = ? WHERE id_producto = ?");
        return $stmt->execute([$nueva_cantidad, $id_producto]);
    }

    /* actualizar(): modifica todos los campos de un producto.
       Se usa en el modal de edición del panel de admin. */
    public static function actualizar($id_producto, $nombre, $descripcion, $cantidad, $stock_minimo, $precio) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE productos_stock SET nombre_prod = ?, descripcion = ?, cant_actual = ?, stock_minimo = ?, precio = ? WHERE id_producto = ?");
        return $stmt->execute([$nombre, $descripcion, $cantidad, $stock_minimo, $precio, $id_producto]);
    }

    /* eliminar(): borra un producto por su ID */
    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM productos_stock WHERE id_producto = ?");
        return $stmt->execute([$id]);
    }

    /* Getters y métodos auxiliares */
    public function getId() { return $this->id_producto; }
    public function getNombre() { return $this->nombre_prod; }
    public function getDescripcion() { return $this->descripcion; }
    public function getCantidadActual() { return $this->cant_actual; }
    public function getStockMinimo() { return $this->stock_minimo; }
    public function getPrecio() { return $this->precio; }
    public function tieneStockBajo() { return $this->cant_actual <= $this->stock_minimo; }
}
