<?php
/*
 * AuthController - Autenticación de usuarios.
 *
 * Gestiona el login, registro y logout de la aplicación.
 * El login tiene rate limiting para evitar fuerza bruta,
 * el registro crea usuarios con rol 'cliente' por defecto,
 * y el logout destruye la sesión completamente.
 */

class AuthController {
    public function handle($method, $path) {
        $data = getJsonInput();
        $resource = $path[1] ?? '';

        /*
         * LOGIN (POST /api/login):
         * 1. Validamos el email con FILTER_VALIDATE_EMAIL.
         * 2. Aplicamos rate limiting (10 intentos en 15 min por IP).
         * 3. Buscamos al usuario por email y verificamos la contraseña
         *    con password_verify() contra el hash bcrypt.
         * 4. Si coincide: regeneramos el ID de sesión (evita session
         *    fixation), guardamos los datos en $_SESSION, limpiamos
         *    el rate limit, y devolvemos los datos del usuario.
         * 5. Si no: devolvemos 401 genérico ("Credenciales inválidas").
         */
        if ($resource === 'login') {
            if ($method === 'POST') {
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

        /*
         * REGISTRO (POST /api/registro):
         * Crea un nuevo usuario con rol 'cliente' por defecto.
         * Validaciones: contraseña ≥ 8 caracteres, email válido,
         * nombre, email y password no vacíos.
         * Si el email ya existe (UNIQUE en BD), capturamos la
         * excepción y devolvemos mensaje claro.
         */
        } elseif ($resource === 'registro') {
            if ($method === 'POST') {
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

        /*
         * LOGOUT (POST /api/logout):
         * Elimina la cookie de sesión del navegador (fecha pasada),
         * vacía $_SESSION y destruye la sesión en el servidor.
         */
        } elseif ($resource === 'logout') {
            if ($method === 'POST') {
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
    }
}
