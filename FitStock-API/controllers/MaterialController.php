<?php
/*
 * MaterialController - CRUD de equipamiento deportivo.
 *
 * Gestiona máquinas y material prestable del gimnasio.
 * Los clientes pueden ver los materiales pero no crear, editar
 * ni eliminar. El filtro por tipo (?tipo=maquina|prestable)
 * permite separar máquinas de material prestable en las vistas.
 */

class MaterialController {
    public function handle($method, $path) {
        requireSession();
        $data = getJsonInput();

        /* Clientes no pueden crear ni eliminar materiales */
        if (($method === 'POST' || $method === 'DELETE') && $_SESSION['usuario_rol'] === 'cliente') {
            jsonResponse(["error" => "Acceso denegado"], 403);
        }

        /*
         * LISTAR MATERIALES (GET /api/materiales):
         * Devuelve todos los materiales. Opcionalmente filtra por
         * tipo (?tipo=maquina|prestable) para separar las vistas
         * de máquinas y material prestable en el frontend.
         */
        if ($method === 'GET') {
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

        /*
         * CREAR MATERIAL (POST /api/materiales):
         * Crea un nuevo material validando estado y tipo contra
         * listas blancas de valores permitidos.
         */
        } elseif ($method === 'POST') {
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

        /*
         * EDITAR MATERIAL (PUT /api/materiales/{id}):
         * Actualiza los datos del material. El estado se valida
         * contra la lista blanca de estados permitidos.
         */
        } elseif ($method === 'PUT' && isset($path[2])) {
            $nombre = trim($data['nombre'] ?? '');
            $descripcion = trim($data['descripcion'] ?? '');
            $estado = $data['estado'] ?? null;
            $ultima_rev = $data['ultima_rev'] ?? null;
            $ubicacion = trim($data['ubicacion'] ?? '');
            $id_tag_material = trim($data['id_tag_material'] ?? '');
            if ($estado !== null && !in_array($estado, ['operativo','averiado','mantenimiento','en_proceso','saliendo','en_reparacion','baja'])) {
                jsonResponse(["error" => "Estado inválido"], 400);
            }
            if ($nombre) {
                Material::actualizar($path[2], $nombre, $descripcion, $estado ?? 'operativo', $ultima_rev, $ubicacion ?: null, $id_tag_material ?: null);
                jsonResponse(["success" => true]);
            }
            jsonResponse(["error" => "Datos inválidos"], 400);

        /*
         * ELIMINAR MATERIAL (DELETE /api/materiales/{id}):
         * Elimina un material del sistema.
         */
        } elseif ($method === 'DELETE' && isset($path[2])) {
            Material::eliminar($path[2]);
            jsonResponse(["success" => true]);
        }
    }
}
