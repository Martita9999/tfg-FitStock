<?php

class AuthController {
    public static function handle($method, $path, $data) {
        switch ($method) {
            case 'POST':
                $resource = $path[1] ?? '';
                if ($resource === 'login') {
                    self::login($data);
                } elseif ($resource === 'registro') {
                    self::registro($data);
                } elseif ($resource === 'logout') {
                    self::logout();
                }
                break;
        }
        jsonResponse(["error" => "Método no permitido"], 405);
    }

    private static function login($data) {
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(["error" => "Email inválido"], 400);
        }

        checkRateLimit($_SERVER['REMOTE_ADDR']);

        if ($email && $password) {
            $usuario = Usuario::obtenerPorEmail($email);
            if ($usuario && password_verify($password, $usuario->getPasswordHash())) {
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $usuario->getId();
                $_SESSION['usuario_nombre'] = $usuario->getNombre();
                $_SESSION['usuario_rol'] = $usuario->getRol();

                clearRateLimit($_SERVER['REMOTE_ADDR']);

                jsonResponse([
                    "success" => true,
                    "user" => [
                        "id" => $usuario->getId(),
                        "nombre" => $usuario->getNombre(),
                        "email" => $usuario->getEmail(),
                        "rol" => $usuario->getRol(),
                        "forzar_cambio_password" => intval($usuario->getForzarCambioPassword())
                    ]
                ]);
            }
        }
        jsonResponse(["error" => "Credenciales inválidas"], 401);
    }

    private static function registro($data) {
        $nombre = trim($data['nombre'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (strlen($password) < 8) {
            jsonResponse(["error" => "La contraseña debe tener al menos 8 caracteres"], 400);
        }

        if ($nombre && $email && $password && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                Usuario::crear($nombre, $email, $password, 'cliente');
                jsonResponse(["success" => true, "message" => "Usuario registrado"]);
            } catch (Exception $e) {
                jsonResponse(["error" => "El correo ya está registrado"], 400);
            }
        }
        jsonResponse(["error" => "Datos inválidos"], 400);
    }

    private static function logout() {
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        $_SESSION = [];
        session_destroy();
        jsonResponse(["success" => true]);
    }
}
