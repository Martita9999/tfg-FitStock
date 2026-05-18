<?php
/*
 * ResumenController - Dashboard de administración.
 *
 * Devuelve datos agregados para el panel principal de la aplicación:
 * - Conteo de incidencias por estado (abierta, en_proceso, resuelta)
 * - Productos con stock por debajo del mínimo (alertas de reposición)
 * - Máquinas agrupadas por estado (operativo, averiado, en_reparacion...)
 * - Gasto total de todos los usuarios y desglose por usuario
 *
 * Estas consultas de agregación no encajan en los modelos CRUD
 * estándar, por lo que se ejecutan directamente aquí con SQL.
 */

class ResumenController {
    public function handle($method, $path) {
        requireSession();

        if ($method === 'GET') {
            $conexion = Conexion::conectar();

            /* Incidencias agrupadas por estado */
            $stmt = $conexion->query("SELECT estado_inc, COUNT(*) as total FROM incidencias GROUP BY estado_inc");
            $incidencias = ['abierta' => 0, 'en_proceso' => 0, 'resuelta' => 0];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $incidencias[$fila['estado_inc']] = intval($fila['total']);
            }

            /* Productos con stock por debajo del mínimo */
            $stmt = $conexion->query("SELECT id_producto, nombre_prod, cant_actual, stock_minimo FROM productos_stock WHERE cant_actual < stock_minimo ORDER BY cant_actual ASC");
            $stock_bajo = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stock_bajo[] = [
                    "id" => intval($fila['id_producto']),
                    "nombre" => $fila['nombre_prod'],
                    "cantidad" => intval($fila['cant_actual']),
                    "stock_minimo" => intval($fila['stock_minimo'])
                ];
            }

            /* Máquinas agrupadas por estado */
            $stmt = $conexion->query("SELECT estado, COUNT(*) as total FROM material WHERE tipo = 'maquina' GROUP BY estado");
            $maquinas = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $maquinas[$fila['estado']] = intval($fila['total']);
            }
            $total_maquinas = array_sum($maquinas);

            /* Gasto total de todas las compras */
            $stmt = $conexion->query("SELECT COALESCE(SUM(total), 0) as total FROM compras");
            $total_gastado = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

            /* Gasto desglosado por usuario (con LEFT JOIN para incluir usuarios sin compras) */
            $stmt = $conexion->query(
                "SELECT u.id_usuario, u.nombre, u.email, COALESCE(SUM(c.total), 0) as total
                 FROM usuarios u
                 LEFT JOIN compras c ON u.id_usuario = c.id_usuario
                 GROUP BY u.id_usuario
                 ORDER BY total DESC"
            );
            $gastos_por_usuario = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $gastos_por_usuario[] = [
                    "id" => intval($fila['id_usuario']),
                    "nombre" => $fila['nombre'],
                    "email" => $fila['email'],
                    "total" => floatval($fila['total'])
                ];
            }

            jsonResponse([
                "incidencias" => $incidencias,
                "stock_bajo" => $stock_bajo,
                "maquinas" => [
                    "por_estado" => $maquinas,
                    "total" => $total_maquinas
                ],
                "gastos" => [
                    "total" => $total_gastado,
                    "por_usuario" => $gastos_por_usuario
                ]
            ]);
        }
    }
}
