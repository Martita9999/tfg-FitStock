<?php
/* ----------------------------------------------------
 * Modelo Compra
 * ----------------------------------------------------
 * Aquí gestionamos todo lo relacionado con las compras
 * que hacen los usuarios en la tienda del gimnasio.
 * Una compra registra qué producto se compró, cuántas
 * unidades, a qué precio y el total calculado.
 *
 * Cada método static se conecta a la BD usando la clase
 * Conexion que tenemos en conexion.php.
 * ---------------------------------------------------- */

require_once __DIR__ . "/../conexion.php";  // Traemos la conexión a la BD

class Compra {
    /* Propiedades privadas: cada una se corresponde con una columna de 'compras' */
    private $id_compra;                   // ID único de la compra
    private $id_usuario;                  // Usuario que hizo la compra
    private $id_producto;                 // Producto comprado
    private $nombre_producto;             // Nombre del producto (con JOIN)
    private $cantidad;                    // Unidades compradas
    private $precio_unitario;             // Precio por unidad en el momento de la compra
    private $total;                       // Total = cantidad * precio_unitario
    private $fecha_compra;                // Cuándo se hizo la compra

    /* Constructor: guarda todos los datos en las propiedades del objeto */
    public function __construct($id_compra, $id_usuario, $id_producto, $nombre_producto, $cantidad, $precio_unitario, $total, $fecha_compra) {
        $this->id_compra = $id_compra;                // Guardamos el ID de la compra
        $this->id_usuario = $id_usuario;              // Guardamos el ID del usuario
        $this->id_producto = $id_producto;            // Guardamos el ID del producto
        $this->nombre_producto = $nombre_producto;    // Guardamos el nombre (de JOIN)
        $this->cantidad = $cantidad;                  // Guardamos la cantidad
        $this->precio_unitario = $precio_unitario;    // Guardamos el precio unitario
        $this->total = $total;                        // Guardamos el total
        $this->fecha_compra = $fecha_compra;          // Guardamos la fecha
    }

    /* listarCompras(): devuelve todas las compras (opcionalmente filtradas por usuario).
       Hacemos LEFT JOIN con productos_stock para traer el nombre del producto.
       Ordenadas de la más reciente a la más antigua.
       Se usa en el dashboard para el historial de compras. */
    public static function obtenerTodos($id_usuario = null) {
        $conexion = Conexion::conectar();              // Abrimos conexión
        $sql = "SELECT c.id_compra, c.id_usuario, c.id_producto,
                       COALESCE(p.nombre_prod, '—') as nombre_producto,  -- JOIN para nombre
                       c.cantidad, c.precio_unitario, c.total, c.fecha_compra
                FROM compras c
                LEFT JOIN productos_stock p ON c.id_producto = p.id_producto";
        if ($id_usuario) {
            $sql .= " WHERE c.id_usuario = ?";          // Filtramos por usuario
        }
        $sql .= " ORDER BY c.fecha_compra DESC";        // Más reciente primero
        $stmt = $conexion->prepare($sql);
        $stmt->execute($id_usuario ? [$id_usuario] : []);
        $compras = [];                                   // Array donde guardamos los objetos
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $compras[] = new Compra(
                $fila['id_compra'], $fila['id_usuario'], $fila['id_producto'],
                $fila['nombre_producto'], $fila['cantidad'],
                $fila['precio_unitario'], $fila['total'], $fila['fecha_compra']
            );
        }
        return $compras;                                 // Devolvemos la lista
    }

    /* crear(): inserta una nueva compra en la BD.
       Calculamos el total automáticamente (cantidad * precio).
       Devuelve el ID de la compra creada. */
    public static function crear($id_usuario, $id_producto, $cantidad, $precio_unitario) {
        $conexion = Conexion::conectar();
        $total = $cantidad * $precio_unitario;           // Calculamos el total
        $stmt = $conexion->prepare("INSERT INTO compras (id_usuario, id_producto, cantidad, precio_unitario, total) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_usuario, $id_producto, $cantidad, $precio_unitario, $total]);
        return $conexion->lastInsertId();                // Devolvemos el ID generado
    }

    /* Getters: acceso a las propiedades privadas desde fuera */
    public function getId() { return $this->id_compra; }
    public function getIdUsuario() { return $this->id_usuario; }
    public function getIdProducto() { return $this->id_producto; }
    public function getNombreProducto() { return $this->nombre_producto; }
    public function getCantidad() { return $this->cantidad; }
    public function getPrecioUnitario() { return $this->precio_unitario; }
    public function getTotal() { return $this->total; }
    public function getFechaCompra() { return $this->fecha_compra; }
}
