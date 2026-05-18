<?php
/*
 * CompraController - Registro de compras de productos.
 *
 * Permite registrar las compras que los usuarios hacen en el
 * gimnasio. Los clientes solo ven sus propias compras, mientras
 * que admin/entrenador pueden ver todas y filtrar por usuario.
 * El total se calcula como cantidad × precio_unitario.
 */

class CompraController {
    public function handle($method, $path) {
        requireSession();
        $data = getJsonInput();

        /*
         * LISTAR COMPRAS (GET /api/compras):
         * - Clientes: solo ven sus propias compras.
         * - Admin/entrenador: ven todas, pueden filtrar por ?id_usuario=
         */
        if ($method === 'GET') {
            if ($_SESSION['usuario_rol'] === 'cliente') {
                $compras = Compra::obtenerTodos($_SESSION['usuario_id']);
            } else {
                $id_usuario = $_GET['id_usuario'] ?? null;
                $compras = Compra::obtenerTodos($id_usuario);
            }
            $list = array_map(function($c) {
                return [
                    "id" => $c->getId(),
                    "id_usuario" => $c->getIdUsuario(),
                    "id_producto" => $c->getIdProducto(),
                    "nombre_producto" => $c->getNombreProducto(),
                    "cantidad" => intval($c->getCantidad()),
                    "precio_unitario" => floatval($c->getPrecioUnitario()),
                    "total" => floatval($c->getTotal()),
                    "fecha_compra" => $c->getFechaCompra()
                ];
            }, $compras);
            jsonResponse($list);

        /*
         * REGISTRAR COMPRA (POST /api/compras):
         * Crea una compra para el usuario autenticado.
         * Verifica que el producto exista antes de registrar.
         * El frontend envía id_producto, cantidad y precio_unitario.
         */
        } elseif ($method === 'POST') {
            $id_producto = $data['id_producto'] ?? '';
            $cantidad = intval($data['cantidad'] ?? 1);
            $precio_unitario = floatval($data['precio_unitario'] ?? 0);
            if ($id_producto && $cantidad > 0 && $precio_unitario > 0) {
                $conexion = Conexion::conectar();
                $stmt = $conexion->prepare("SELECT id_producto FROM productos_stock WHERE id_producto = ?");
                $stmt->execute([$id_producto]);
                if (!$stmt->fetch()) {
                    jsonResponse(["error" => "El producto no existe"], 400);
                }
                Compra::crear($_SESSION['usuario_id'], $id_producto, $cantidad, $precio_unitario);
                jsonResponse(["success" => true]);
            }
            jsonResponse(["error" => "Datos inválidos"], 400);
        }
    }
}
