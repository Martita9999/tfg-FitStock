<?php

class PrestamoController {
    public static function handle($method, $path, $data) {
        requireSession();

        if ($method === 'DELETE' && $_SESSION['usuario_rol'] !== 'admin') {
            jsonResponse(["error" => "Acceso denegado"], 403);
        }

        switch ($method) {
            case 'GET':
                self::listar();
                break;
            case 'POST':
                self::crear($data);
                break;
            case 'PUT':
                if (isset($path[2])) {
                    if (isset($path[3])) {
                        self::actualizarEstado($path[2], $path[3]);
                    } else {
                        self::actualizar($path[2], $data);
                    }
                }
                break;
            case 'DELETE':
                if (isset($path[2])) {
                    self::eliminar($path[2]);
                }
                break;
        }
    }

    private static function listar() {
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
    }

    private static function crear($data) {
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
    }

    private static function actualizar($id, $data) {
        $fecha_devolucion = $data['fecha_devolucion'] ?? null;
        if ($fecha_devolucion !== null || array_key_exists('fecha_devolucion', $data)) {
            Prestamo::actualizar($id, $fecha_devolucion);
            jsonResponse(["success" => true, "message" => "Préstamo actualizado"]);
        } else {
            Prestamo::devolver($id);
            jsonResponse(["success" => true, "message" => "Préstamo marcado para devolución"]);
        }
    }

    private static function actualizarEstado($id, $accion) {
        if ($accion === 'aprobar') {
            Prestamo::aprobar($id);
            jsonResponse(["success" => true, "message" => "Préstamo aprobado"]);
        } elseif ($accion === 'confirmar-devolucion') {
            Prestamo::confirmarDevolucion($id);
            jsonResponse(["success" => true, "message" => "Devolución confirmada"]);
        } else {
            jsonResponse(["error" => "Acción no reconocida"], 400);
        }
    }

    private static function eliminar($id) {
        Prestamo::eliminar($id);
        jsonResponse(["success" => true]);
    }
}
