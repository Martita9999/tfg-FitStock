<?php
require_once __DIR__ . "/../conexion.php";

// Clase modelo para la tabla 'compras' - gestiona los pedidos realizados por los usuarios
class Compra {
    private $id_compra;           // ID único de la compra
    private $id_usuario;          // ID del usuario que realizó la compra
    private $id_producto;         // ID del producto comprado
    private $nombre_producto;     // Nombre del producto (de la JOIN)
    private $cantidad;            // Cantidad de unidades compradas
    private $precio_unitario;     // Precio por unidad en el momento de la compra
    private $total;               // Total calculado = cantidad * precio_unitario
    private $fecha_compra;        // Fecha y hora en que se realizó la compra

    // Constructor: asigna todos los valores al crear una instancia
    public function __construct($id_compra, $id_usuario, $id_producto, $nombre_producto, $cantidad, $precio_unitario, $total, $fecha_compra) {
        $this->id_compra = $id_compra;
        $this->id_usuario = $id_usuario;
        $this->id_producto = $id_producto;
        $this->nombre_producto = $nombre_producto;
        $this->cantidad = $cantidad;
        $this->precio_unitario = $precio_unitario;
        $this->total = $total;
        $this->fecha_compra = $fecha_compra;
    }

    // Obtiene todas las compras, opcionalmente filtradas por usuario.
    // Incluye el nombre del producto mediante JOIN con productos_stock.
    // Útil para mostrar historial de compras a administradores o a cada usuario.
    public static function obtenerTodos($id_usuario = null) {
        $conexion = Conexion::conectar();
        $sql = "SELECT c.id_compra, c.id_usuario, c.id_producto,
                       COALESCE(p.nombre_prod, '—') as nombre_producto,
                       c.cantidad, c.precio_unitario, c.total, c.fecha_compra
                FROM compras c
                LEFT JOIN productos_stock p ON c.id_producto = p.id_producto";
        if ($id_usuario) {
            $sql .= " WHERE c.id_usuario = ?";
        }
        $sql .= " ORDER BY c.fecha_compra DESC";
        $stmt = $conexion->prepare($sql);
        $stmt->execute($id_usuario ? [$id_usuario] : []);
        $compras = [];
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $compras[] = new Compra(
                $fila['id_compra'], $fila['id_usuario'], $fila['id_producto'],
                $fila['nombre_producto'], $fila['cantidad'],
                $fila['precio_unitario'], $fila['total'], $fila['fecha_compra']
            );
        }
        return $compras;
    }

    // Crea una nueva compra en la base de datos.
    // Calcula el total automáticamente como cantidad * precio_unitario.
    // Devuelve el ID de la compra insertada.
    public static function crear($id_usuario, $id_producto, $cantidad, $precio_unitario) {
        $conexion = Conexion::conectar();
        $total = $cantidad * $precio_unitario;
        $stmt = $conexion->prepare("INSERT INTO compras (id_usuario, id_producto, cantidad, precio_unitario, total) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_usuario, $id_producto, $cantidad, $precio_unitario, $total]);
        return $conexion->lastInsertId();
    }

    // Getters para acceder a las propiedades privadas
    public function getId() { return $this->id_compra; }
    public function getIdUsuario() { return $this->id_usuario; }
    public function getIdProducto() { return $this->id_producto; }
    public function getNombreProducto() { return $this->nombre_producto; }
    public function getCantidad() { return $this->cantidad; }
    public function getPrecioUnitario() { return $this->precio_unitario; }
    public function getTotal() { return $this->total; }
    public function getFechaCompra() { return $this->fecha_compra; }
}
