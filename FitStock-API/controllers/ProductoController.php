<?php

class ProductoController {
    public static function handle($method, $path, $data) {
        requireSession();

        if ($method === 'DELETE') {
            if ($_SESSION['usuario_rol'] !== 'admin') {
                jsonResponse(["error" => "Acceso denegado"], 403);
            }
        } elseif ($method === 'POST' && $_SESSION['usuario_rol'] === 'cliente') {
            jsonResponse(["error" => "Acceso denegado"], 403);
        }

        if ($method === 'POST' && isset($path[2]) && $path[2] === 'subir-imagen') {
            self::subirImagen();
            return;
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
        $productos = Producto::obtenerTodos();
        $list = array_map(function($p) {
            return [
                "id" => $p->getId(),
                "nombre" => $p->getNombre(),
                "descripcion" => $p->getDescripcion(),
                "cantidad" => intval($p->getCantidadActual()),
                "stock_minimo" => intval($p->getStockMinimo()),
                "precio" => floatval($p->getPrecio())
            ];
        }, $productos);
        jsonResponse($list);
    }

    private static function subirImagen() {
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) {
            jsonResponse(["error" => "Nombre del producto requerido"], 400);
        }
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(["error" => "No se recibió ninguna imagen"], 400);
        }
        $imagen = $_FILES['imagen'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $imagen['tmp_name']);
        finfo_close($finfo);

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeReal, $allowedTypes)) {
            jsonResponse(["error" => "Formato no válido. Usa JPG, PNG, GIF o WebP"], 400);
        }

        $uploadDir = __DIR__ . '/../../FitStock-APP/public/images/productos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = $nombre . '.jpg';
        if (move_uploaded_file($imagen['tmp_name'], $uploadDir . $filename)) {
            jsonResponse(["success" => true, "imagen" => $filename]);
        }
        jsonResponse(["error" => "Error al guardar la imagen"], 500);
    }

    private static function crear($data) {
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $cantidad = intval($data['cantidad'] ?? 0);
        $stock_minimo = intval($data['stock_minimo'] ?? 0);
        $precio = floatval($data['precio'] ?? 0);

        if ($nombre) {
            if ($cantidad < 0) {
                jsonResponse(["error" => "La cantidad no puede ser negativa"], 400);
            }
            if ($precio <= 0) {
                jsonResponse(["error" => "El precio debe ser mayor que 0"], 400);
            }
            Producto::crear($nombre, $descripcion ?: null, $cantidad, $stock_minimo, $precio);
            jsonResponse(["success" => true]);
        }
        jsonResponse(["error" => "Datos inválidos"], 400);
    }

    private static function actualizar($id, $data) {
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');
        $cantidad = intval($data['cantidad'] ?? -1);
        $stock_minimo = intval($data['stock_minimo'] ?? -1);
        $precio = floatval($data['precio'] ?? -1);

        if ($nombre && $cantidad >= 0 && $stock_minimo >= 0 && $precio >= 0) {
            Producto::actualizar($id, $nombre, $descripcion ?: null, $cantidad, $stock_minimo, $precio);
            jsonResponse(["success" => true]);
        } elseif ($cantidad >= 0 && !isset($data['nombre'])) {
            Producto::actualizarStock($id, $cantidad);
            jsonResponse(["success" => true]);
        }
        jsonResponse(["error" => "Datos inválidos"], 400);
    }

    private static function eliminar($id) {
        Producto::eliminar($id);
        jsonResponse(["success" => true]);
    }
}
