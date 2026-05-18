<?php

class MaterialController {
    public static function handle($method, $path, $data) {
        requireSession();

        if ($method === 'DELETE') {
            if ($_SESSION['usuario_rol'] !== 'admin') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
        } elseif ($method === 'POST' && $_SESSION['usuario_rol'] === 'cliente') {
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
        $tipo = $_GET['tipo'] ?? null;
        $materiales = Material::obtenerTodos($tipo);
        $list = array_map(function($m) {
            return [
                "id" => $m->getId(),
                "nombre" => $m->getNombre(),
                "descripcion" => $m->getDescripcion(),
                "ubicacion" => $m->getUbicacion(),
                "estado" => $m->getEstado(),
                "tipo" => $m->getTipo(),
                "id_tag_material" => $m->getIdTagMaterial(),
                "ultima_rev" => $m->getUltimaRev()
            ];
        }, $materiales);
        jsonResponse($list);
    }

    private static function crear($data) {
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $estado = $data['estado'] ?? 'operativo';
        $tipo = $data['tipo'] ?? 'maquina';
        $id_tag_material = trim($data['id_tag_material'] ?? '');
        $ubicacion = trim($data['ubicacion'] ?? '');

        if (!in_array($estado, ['operativo','averiado','mantenimiento','en_proceso','saliendo','en_reparacion','baja'])) {
            jsonResponse(["error" => "Estado inválido"], 400);
        }
        if (!in_array($tipo, ['maquina', 'prestable'])) {
            jsonResponse(["error" => "Tipo inválido"], 400);
        }
        if ($nombre) {
            Material::crear($nombre, $descripcion, $estado, $tipo, $id_tag_material, null, $ubicacion ?: null);
            jsonResponse(["success" => true]);
        }
        jsonResponse(["error" => "Datos inválidos"], 400);
    }

    private static function actualizar($id, $data) {
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $estado = $data['estado'] ?? null;
        $ultima_rev = $data['ultima_rev'] ?? null;
        $ubicacion = trim($data['ubicacion'] ?? '');
        $id_tag_material = trim($data['id_tag_material'] ?? '');

        if ($estado !== null && $nombre && $estado && !in_array($estado, ['operativo','averiado','mantenimiento','en_proceso','saliendo','en_reparacion','baja'])) {
            jsonResponse(["error" => "Estado inválido"], 400);
        }
        if ($nombre && $estado) {
            Material::actualizar($id, $nombre, $descripcion, $estado, $ultima_rev, $ubicacion ?: null, $id_tag_material ?: null);
            jsonResponse(["success" => true]);
        }
        jsonResponse(["error" => "Datos inválidos"], 400);
    }

    private static function eliminar($id) {
        Material::eliminar($id);
        jsonResponse(["success" => true]);
    }
}
