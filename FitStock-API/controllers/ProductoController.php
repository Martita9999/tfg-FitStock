<?php
/*
 * ProductoController - CRUD de productos en stock.
 *
 * Gestiona el inventario de productos (suplementos, etc.) con
 * control de stock mínimo y precios. Incluye subida de imágenes
 * con validación del tipo MIME real usando finfo para evitar
 * que se suban archivos maliciosos disfrazados de imágenes.
 */

class ProductoController {
    public function handle($method, $path) {
        requireSession();
        $data = getJsonInput();

        /* Clientes no pueden crear, editar ni eliminar productos */
        if (($method === 'POST' || $method === 'DELETE') && $_SESSION['usuario_rol'] === 'cliente') {
            jsonResponse(["error" => "Acceso denegado"], 403);
        }

        /*
         * LISTAR PRODUCTOS (GET /api/productos):
         * Devuelve todos los productos con su stock actual y precio.
         */
        if ($method === 'GET') {
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

        /*
         * SUBIR IMAGEN (POST /api/productos/subir-imagen):
         * Valida el tipo MIME real con finfo (lee los bytes del archivo)
         * en lugar de confiar en Content-Type del navegador, que puede
         * ser falseado. Solo permite JPG, PNG, GIF y WebP.
         * Guarda la imagen en public/images/productos/ con nombre .jpg.
         */
        } elseif ($method === 'POST' && isset($path[2]) && $path[2] === 'subir-imagen') {
            $nombre = trim($data['nombre'] ?? '');
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
            } else {
                jsonResponse(["error" => "Error al guardar la imagen"], 500);
            }

        /*
         * CREAR PRODUCTO (POST /api/productos):
         * Validaciones: cantidad no negativa, precio mayor que 0.
         */
        } elseif ($method === 'POST') {
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

        /*
         * EDITAR PRODUCTO (PUT /api/productos/{id}):
         * Puede actualizar todos los datos a la vez o solo el stock
         * si solo se envía el campo cantidad sin nombre.
         */
        } elseif ($method === 'PUT' && isset($path[2])) {
            $nombre = trim($data['nombre'] ?? '');
            $descripcion = trim($data['descripcion'] ?? '');
            $cantidad = intval($data['cantidad'] ?? -1);
            $stock_minimo = intval($data['stock_minimo'] ?? -1);
            $precio = floatval($data['precio'] ?? -1);
            if ($nombre && $cantidad >= 0 && $stock_minimo >= 0 && $precio >= 0) {
                Producto::actualizar($path[2], $nombre, $descripcion ?: null, $cantidad, $stock_minimo, $precio);
                jsonResponse(["success" => true]);
            } elseif ($cantidad >= 0 && !isset($data['nombre'])) {
                Producto::actualizarStock($path[2], $cantidad);
                jsonResponse(["success" => true]);
            }
            jsonResponse(["error" => "Datos inválidos"], 400);

        /*
         * ELIMINAR PRODUCTO (DELETE /api/productos/{id}):
         */
        } elseif ($method === 'DELETE' && isset($path[2])) {
            Producto::eliminar($path[2]);
            jsonResponse(["success" => true]);
        }
    }
}
