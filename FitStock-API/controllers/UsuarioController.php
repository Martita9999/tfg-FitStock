<?php

class UsuarioController {
    public static function handle($method, $path, $data) {
        requireSession();

        if ($method === 'PUT' && isset($path[2]) && $path[2] === 'cambiar-password') {
            self::cambiarPassword($data);
            return;
        }

        if ($_SESSION['usuario_rol'] === 'cliente') {
            jsonResponse(["error" => "Acceso denegado"], 403);
        }

        switch ($method) {
            case 'GET':
                self::listar();
                break;
            case 'POST':
                if (isset($path[2]) && $path[2] === 'forzar-cambio') {
                    self::forzarCambio($data);
                } else {
                    self::crear($data);
                }
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
    }

    private static function crear($data) {
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
    }

    private static function actualizar($id, $data) {
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
                $target = Usuario::obtenerPorId($id);
                if ($target && $target->getRol() === 'admin') {
                    jsonResponse(["error" => "No puedes editar un administrador"], 403);
                }
                if ($rol === 'admin') {
                    jsonResponse(["error" => "No puedes asignar el rol admin"], 403);
                }
            }
            Usuario::actualizarAdmin($id, $nombre, $email, $password, $rol);
            jsonResponse(["success" => true]);
        }
        jsonResponse(["error" => "Datos inválidos"], 400);
    }

    private static function eliminar($id) {
        if ($_SESSION['usuario_rol'] !== 'admin') {
            jsonResponse(["error" => "Acceso denegado"], 403);
        }
        Usuario::eliminar($id);
        jsonResponse(["success" => true]);
    }

    private static function cambiarPassword($data) {
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
        }
        jsonResponse(["error" => "Contraseña inválida"], 400);
    }

    private static function forzarCambio($data) {
        if ($_SESSION['usuario_rol'] !== 'admin' && $_SESSION['usuario_rol'] !== 'entrenador') {
            jsonResponse(["error" => "Acceso denegado"], 403);
        }
        $id_usuario = $data['id_usuario'] ?? null;
        if ($id_usuario) {
            Usuario::forzarCambioPassword($id_usuario);
            jsonResponse(["success" => true]);
        }
        jsonResponse(["error" => "ID de usuario requerido"], 400);
    }
}
