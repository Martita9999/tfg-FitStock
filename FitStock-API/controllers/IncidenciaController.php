<?php

class IncidenciaController {
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
                    self::actualizar($path[2], $data);
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
        if ($_SESSION['usuario_rol'] === 'cliente') {
            $incidencias = Incidencia::obtenerTodos($_SESSION['usuario_id']);
        } else {
            $id_usuario = $_GET['id_usuario'] ?? null;
            $incidencias = Incidencia::obtenerTodos($id_usuario);
        }
        $list = array_map(function($inc) {
            return [
                "id" => $inc->getId(),
                "id_material" => $inc->getIdMaterial(),
                "id_user_rep" => $inc->getIdUser(),
                "descripcion" => $inc->getDescripcion(),
                "prioridad" => $inc->getPrioridad(),
                "estado" => $inc->getEstado(),
                "created_at" => $inc->getCreatedAt(),
                "fecha_resolucion" => $inc->getFechaResolucion(),
                "nombre_material" => $inc->getNombreMaterial(),
                "id_tag_material" => $inc->getIdTagMaterial(),
                "ubicacion" => $inc->getUbicacion()
            ];
        }, $incidencias);
        jsonResponse($list);
    }

    private static function crear($data) {
        $id_material = $data['id_material'] ?? '';
        $descripcion = trim($data['descripcion'] ?? '');
        $prioridad = $data['prioridad'] ?? 'media';

        if (!in_array($prioridad, ['baja', 'media', 'alta'])) {
            jsonResponse(["error" => "Prioridad inválida"], 400);
        }
        if ($id_material && $descripcion) {
            if (!Material::obtenerPorId($id_material)) {
                jsonResponse(["error" => "El material no existe"], 400);
            }
            Incidencia::crear($id_material, $_SESSION['usuario_id'], $descripcion, $prioridad);
            $conexion = Conexion::conectar();
            $stmt = $conexion->prepare("UPDATE material SET estado = 'averiado' WHERE id_material = ?");
            $stmt->execute([$id_material]);
            jsonResponse(["success" => true]);
        }
        jsonResponse(["error" => "Datos inválidos"], 400);
    }

    private static function actualizar($id, $data) {
        $descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : null;
        $prioridad = $data['prioridad'] ?? null;
        $estado = $data['estado'] ?? null;

        Incidencia::actualizar($id, $prioridad, $estado, $descripcion);
        $inc = Incidencia::obtenerPorId($id);
        if ($inc && $inc->getIdMaterial()) {
            $conexion = Conexion::conectar();
            if ($estado === 'resuelta') {
                $stmt = $conexion->prepare("UPDATE material SET estado = 'operativo' WHERE id_material = ?");
                $stmt->execute([$inc->getIdMaterial()]);
            } elseif ($estado === 'en_proceso') {
                $stmt = $conexion->prepare("UPDATE material SET estado = 'en_reparacion' WHERE id_material = ?");
                $stmt->execute([$inc->getIdMaterial()]);
            }
        }
        jsonResponse(["success" => true]);
    }

    private static function eliminar($id) {
        Incidencia::eliminar($id);
        jsonResponse(["success" => true]);
    }
}
