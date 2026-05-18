<?php
/*
 * IncidenciaController - Gestión de averías y problemas.
 *
 * Permite reportar incidencias en máquinas/material, que
 * automáticamente marcan el material como 'averiado'. Al resolver
 * la incidencia, el material vuelve a 'operativo'. El cambio de
 * estado a 'en_proceso' marca el material como 'en_reparacion'.
 */

class IncidenciaController {
    public function handle($method, $path) {
        requireSession();
        $data = getJsonInput();

        /* Clientes no pueden eliminar incidencias */
        if ($method === 'DELETE' && $_SESSION['usuario_rol'] === 'cliente') {
            jsonResponse(["error" => "Acceso denegado"], 403);
        }

        /*
         * LISTAR INCIDENCIAS (GET /api/incidencias):
         * - Clientes: solo ven las incidencias que reportaron.
         * - Admin/entrenador: ven todas, filtran por ?id_usuario=
         * Los datos incluyen nombre del material, tag y ubicación
         * gracias a los JOINs en el modelo Incidencia.
         */
        if ($method === 'GET') {
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

        /*
         * CREAR INCIDENCIA (POST /api/incidencias):
         * Crea una incidencia y automáticamente marca el material
         * como 'averiado'. Valida la prioridad contra lista blanca
         * (baja, media, alta) y verifica que el material exista.
         */
        } elseif ($method === 'POST') {
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

        /*
         * EDITAR INCIDENCIA (PUT /api/incidencias/{id}):
         * Actualiza descripción, prioridad y/o estado.
         * Al cambiar el estado:
         * - 'resuelta': el material vuelve a 'operativo'.
         * - 'en_proceso': el material pasa a 'en_reparacion'.
         */
        } elseif ($method === 'PUT' && isset($path[2])) {
            $descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : null;
            $prioridad = $data['prioridad'] ?? null;
            $estado = $data['estado'] ?? null;
            Incidencia::actualizar($path[2], $prioridad, $estado, $descripcion);
            $inc = Incidencia::obtenerPorId($path[2]);
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

        /*
         * ELIMINAR INCIDENCIA (DELETE /api/incidencias/{id}):
         * Solo admin/entrenador pueden eliminar incidencias.
         */
        } elseif ($method === 'DELETE' && isset($path[2])) {
            Incidencia::eliminar($path[2]);
            jsonResponse(["success" => true]);
        }
    }
}
