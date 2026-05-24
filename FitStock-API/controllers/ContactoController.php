<?php

class ContactoController {
    public static function handle($method, $path, $data) {
        if ($method === 'POST') {
            self::enviar($data);
        }
        jsonResponse(["error" => "Método no permitido"], 405);
    }

    private static function enviar($data) {
        checkRateLimitContacto($_SERVER['REMOTE_ADDR']);

        $email = trim($data['email'] ?? '');
        $mensaje = trim($data['mensaje'] ?? '');

        if (!$email || !$mensaje) {
            jsonResponse(["error" => "Todos los campos son obligatorios"], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(["error" => "Email inválido"], 400);
        }

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'infofitstockmadrid@gmail.com';
            $mail->Password   = getenv('MAIL_PASSWORD') ?: '';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('infofitstockmadrid@gmail.com', 'FitStock Contacto');
            $mail->addAddress('infofitstockmadrid@gmail.com');
            $mail->addReplyTo($email);

            $mail->Subject = 'Nuevo contacto desde FitStock';
            $mail->Body    = "Email de contacto: $email\n\nMensaje:\n$mensaje";
            $mail->AltBody = strip_tags($mail->Body);

            $mail->send();
            jsonResponse(["success" => true, "message" => "Mensaje enviado correctamente"]);
        } catch (PHPMailer\PHPMailer\Exception $e) {
            error_log("Error PHPMailer en contacto: " . $e->getMessage());
            jsonResponse(["error" => "Error al enviar el mensaje. Inténtelo de nuevo más tarde."], 500);
        } catch (Exception $e) {
            error_log("Error interno en contacto: " . $e->getMessage());
            jsonResponse(["error" => "Error interno del servidor"], 500);
        }
    }
}
