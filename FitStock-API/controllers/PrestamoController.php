<?php
/*
 * PrestamoController - Gestión de préstamos de material.
 *
 * Controla el ciclo completo del préstamo: pendiente → activo →
 * pendiente_devolucion → devuelto. Los clientes solo gestionan
 * sus propios préstamos (los crean como pendientes y ven solo
 * los suyos), mientras que admin/entrenador administran todos.
 */

class PrestamoController {
    public function handle($method, $path) {
        requireSession();
        $data = getJsonInput();

        /* Clientes no pueden eliminar préstamos */
        if ($method === 'DELETE' && $_SESSION['usuario_rol'] === 'cliente') {
            jsonResponse(["error" => "Acceso denegado"], 403);
        }

        /*
         * LISTAR PRÉSTAMOS (GET /api/prestamos):
         * - Clientes: solo ven sus propios préstamos.
         * - Admin/entrenador: ven todos, con filtro por ?id_usuario=
         * - Filtro por estado: ?estado=pendiente|activo|...
         */
        if ($method === 'GET') {
            $estadoFiltro = $_GET['estado'] ?? null;
            if ($estadoFiltro) {
                $prestamos = Prestamo::obtenerPendientes($estadoFiltro);
            } elseif ($_SESSION['usuario_rol'] === 'cliente') {
                $prestamos = Prestamo::obtenerTodos($_SESSION['usuario_id']);
            } else {
                $id_usuario = $_GET['id_usuario'] ?? null;
                $prestamos = Prestamo::obtenerTodos($id_usuario);
            }
            $list = array_map(function($p) {
                return [
                    "id" => $p->getId(),
                    "id_usuario" => $p->getIdUsuario(),
                    "id_material" => $p->getIdMaterial(),
                    "usuario" => $p->getUsuarioNombre(),
                    "material" => $p->getMaterialNombre(),
                    "fecha" => $p->getFecha(),
                    "devolucion" => $p->getFechaDevolucion(),
                    "estado" => $p->getEstado()
                ];
            }, $prestamos);
            jsonResponse($list);

        /*
         * CREAR PRÉSTAMO (POST /api/prestamos):
         * - Clientes: crean préstamos para sí mismos en estado 'pendiente'.
         * - Admin/entrenador: pueden asignar a cualquier usuario y
         *   establecer el estado inicial (ej: 'activo' directamente).
         */
        } elseif ($method === 'POST') {
            if ($_SESSION['usuario_rol'] === 'cliente') {
                $id_usuario = $_SESSION['usuario_id'];
                $estado = 'pendiente';
            } else {
                $id_usuario = $data['id_usuario'] ?? $_SESSION['usuario_id'];
                $estado = $data['estado'] ?? 'pendiente';
            }
            $id_material = $data['id_material'] ?? '';
            $fecha_devolucion = $data['fecha_devolucion'] ?? null;
            if ($id_material) {
                if (!Material::obtenerPorId($id_material)) {
                    jsonResponse(["error" => "El material no existe"], 400);
                }
                Prestamo::crear($id_usuario, $id_material, $fecha_devolucion, $estado);
                jsonResponse(["success" => true]);
            }
            jsonResponse(["error" => "Datos inválidos"], 400);

        /*
         * ACCIONES SOBRE PRÉSTAMO (PUT /api/prestamos/{id}/accion):
         * - aprobar: cambia el estado a 'activo' (admin/entrenador).
         * - confirmar-devolucion: cierra el ciclo (admin/entrenador).
         */
        } elseif ($method === 'PUT' && isset($path[2]) && isset($path[3])) {
            if ($path[3] === 'aprobar') {
                Prestamo::aprobar($path[2]);
                jsonResponse(["success" => true, "message" => "Préstamo aprobado"]);
            } elseif ($path[3] === 'confirmar-devolucion') {
                Prestamo::confirmarDevolucion($path[2]);
                jsonResponse(["success" => true, "message" => "Devolución confirmada"]);
            } else {
                jsonResponse(["error" => "Acción no reconocida"], 400);
            }

        /*
         * ACTUALIZAR PRÉSTAMO (PUT /api/prestamos/{id}):
         * Si se envía fecha_devolucion, actualiza la fecha.
         * Si no, marca el préstamo como 'pendiente_devolucion'.
         */
        } elseif ($method === 'PUT' && isset($path[2])) {
            $fecha_devolucion = $data['fecha_devolucion'] ?? null;
            if ($fecha_devolucion !== null || array_key_exists('fecha_devolucion', $data)) {
                Prestamo::actualizar($path[2], $fecha_devolucion);
                jsonResponse(["success" => true, "message" => "Préstamo actualizado"]);
            } else {
                Prestamo::devolver($path[2]);
                jsonResponse(["success" => true, "message" => "Préstamo marcado para devolución"]);
            }

        /*
         * ELIMINAR PRÉSTAMO (DELETE /api/prestamos/{id}):
         * Solo admin/entrenador pueden eliminar préstamos.
         */
        } elseif ($method === 'DELETE' && isset($path[2])) {
            Prestamo::eliminar($path[2]);
            jsonResponse(["success" => true]);
        }
    }
}
