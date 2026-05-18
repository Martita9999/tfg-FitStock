<?php
/*
 * UsuarioController - CRUD de usuarios del gimnasio.
 *
 * Gestiona todo el ciclo de vida de los usuarios: listar, crear,
 * editar, eliminar, cambiar contraseña y forzar cambio.
 * El acceso está controlado por roles:
 * - Admin: acceso total a todas las operaciones.
 * - Entrenador: puede gestionar usuarios excepto admins.
 * - Cliente: solo puede cambiar su propia contraseña.
 */

class UsuarioController {
    public function handle($method, $path) {
        requireSession();
        $data = getJsonInput();

        /*
         * CAMBIAR CONTRASEÑA PROPIA (PUT /api/usuarios/cambiar-password):
         * Cualquier usuario autenticado puede cambiar su contraseña.
         * Necesita la contraseña actual (old_password) para verificarla
         * antes de actualizar. Al cambiarla, se limpia la bandera
         * forzar_cambio_password.
         */
        if ($method === 'PUT' && isset($path[2]) && $path[2] === 'cambiar-password') {
            $old_password = $data['old_password'] ?? '';
            $new_password = $data['new_password'] ?? '';

            if (strlen($new_password) < 8) {
                jsonResponse(["error" => "La nueva contraseña debe tener al menos 8 caracteres"], 400);
            }

            if ($old_password && $new_password) {
                $usuario = Usuario::obtenerPorId($_SESSION['usuario_id']);
                if ($usuario && password_verify($old_password, $usuario->getPasswordHash())) {
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $conexion = Conexion::conectar();
                    $stmt = $conexion->prepare("UPDATE usuarios SET password_hash = ?, forzar_cambio_password = 0 WHERE id_usuario = ?");
                    $stmt->execute([$password_hash, $_SESSION['usuario_id']]);
                    jsonResponse(["success" => true]);
                } else {
                    jsonResponse(["error" => "La contraseña actual no es correcta"], 400);
                }
            } else {
                jsonResponse(["error" => "Contraseña inválida"], 400);
            }
            return;
        }

        /* Los clientes no pueden acceder al CRUD de usuarios */
        if ($_SESSION['usuario_rol'] === 'cliente') {
            jsonResponse(["error" => "Acceso denegado"], 403);
        }

        /*
         * LISTAR USUARIOS (GET /api/usuarios):
         * Devuelve todos los usuarios con sus datos (sin contraseñas).
         */
        if ($method === 'GET') {
            $usuarios = Usuario::obtenerTodos();
            $list = array_map(function($u) {
                return [
                    "id" => $u->getId(),
                    "nombre" => $u->getNombre(),
                    "email" => $u->getEmail(),
                    "rol" => $u->getRol(),
                    "forzar_cambio_password" => intval($u->getForzarCambioPassword())
                ];
            }, $usuarios);
            jsonResponse($list);

        /*
         * FORZAR CAMBIO DE CONTRASEÑA (POST /api/usuarios/forzar-cambio):
         * Marca a un usuario para que deba cambiar la contraseña
         * en el próximo inicio de sesión. Disponible para admin y
         * entrenador.
         */
        } elseif ($method === 'POST' && isset($path[2]) && $path[2] === 'forzar-cambio') {
            if ($_SESSION['usuario_rol'] !== 'admin' && $_SESSION['usuario_rol'] !== 'entrenador') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            $id_usuario = $data['id_usuario'] ?? null;
            if ($id_usuario) {
                Usuario::forzarCambioPassword($id_usuario);
                jsonResponse(["success" => true]);
            } else {
                jsonResponse(["error" => "ID de usuario requerido"], 400);
            }

        /*
         * CREAR USUARIO (POST /api/usuarios):
         * Solo admin puede crear usuarios de cualquier rol.
         * Validamos: rol permitido, contraseña ≥ 8 caracteres, email válido.
         */
        } elseif ($method === 'POST') {
            if ($_SESSION['usuario_rol'] !== 'admin') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            $nombre = trim($data['nombre'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $rol = $data['rol'] ?? 'cliente';

            if (!in_array($rol, ['admin', 'entrenador', 'cliente'])) {
                jsonResponse(["error" => "Rol inválido"], 400);
            }
            if (strlen($password) < 8) {
                jsonResponse(["error" => "La contraseña debe tener al menos 8 caracteres"], 400);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(["error" => "Email inválido"], 400);
            }

            Usuario::crear($nombre, $email, $password, $rol);
            jsonResponse(["success" => true]);

        /*
         * EDITAR USUARIO (PUT /api/usuarios/{id}):
         * Admin y entrenador pueden editar. El entrenador tiene
         * restricciones: no puede editar admins ni asignar rol admin.
         */
        } elseif ($method === 'PUT' && isset($path[2])) {
            if ($_SESSION['usuario_rol'] !== 'admin' && $_SESSION['usuario_rol'] !== 'entrenador') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            $nombre = trim($data['nombre'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? null;
            $rol = $data['rol'] ?? null;
            if ($rol !== null && !in_array($rol, ['admin', 'entrenador', 'cliente'])) {
                jsonResponse(["error" => "Rol inválido"], 400);
            }
            if ($nombre && $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    jsonResponse(["error" => "Email inválido"], 400);
                }
                if ($password !== null && strlen($password) < 8) {
                    jsonResponse(["error" => "La contraseña debe tener al menos 8 caracteres"], 400);
                }
                if ($_SESSION['usuario_rol'] === 'entrenador') {
                    $target = Usuario::obtenerPorId($path[2]);
                    if ($target && $target->getRol() === 'admin') {
                        jsonResponse(["error" => "No puedes editar un administrador"], 403);
                    }
                    if ($rol === 'admin') {
                        jsonResponse(["error" => "No puedes asignar el rol admin"], 403);
                    }
                }
                Usuario::actualizarAdmin($path[2], $nombre, $email, $password, $rol);
                jsonResponse(["success" => true]);
            } else {
                jsonResponse(["error" => "Datos inválidos"], 400);
            }

        /*
         * ELIMINAR USUARIO (DELETE /api/usuarios/{id}):
         * Solo admin puede eliminar usuarios.
         */
        } elseif ($method === 'DELETE' && isset($path[2])) {
            if ($_SESSION['usuario_rol'] !== 'admin') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
            Usuario::eliminar($path[2]);
            jsonResponse(["success" => true]);
        }
    }
}
