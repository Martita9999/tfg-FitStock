<?php
/* ----------------------------------------------------
 * Modelo Material
 * ----------------------------------------------------
 * Este modelo gestiona todo el equipamiento y material
 * deportivo del gimnasio. Tenemos dos tipos:
 *   - 'maquina': equipos fijos como cintas, bicis...
 *   - 'prestable': material que se puede prestar (mancuernas, etc.)
 *
 * La clase también se encarga de generar identificadores
 * únicos para cada material (tipo CIN-001, BAN-002...).
 * ---------------------------------------------------- */

require_once __DIR__ . "/../conexion.php";  // Traemos la conexión a la BD

class Material {
    /* Propiedades privadas que se corresponden con las columnas de la tabla 'material' */
    private $id_material;                 // ID único en la BD
    private $nombre_equipo;               // Nombre del equipo (ej: "Cinta de correr")
    private $descripcion;                 // Descripción breve
    private $ubicacion;                   // Dónde está en el gimnasio
    private $estado;                      // operativo, averiado, mantenimiento...
    private $tipo;                        // 'maquina' o 'prestable'
    private $id_tag_material;             // Identificador único tipo CIN-001
    private $ultima_rev;                  // Fecha de la última revisión

    /* Constructor: guarda todos los valores en las propiedades.
       La ubicación es opcional (algunos materiales no tienen sitio fijo). */
    public function __construct($id_material, $nombre_equipo, $descripcion, $estado, $tipo, $id_tag_material, $ultima_rev, $ubicacion = null) {
        $this->id_material = $id_material;            // Guardamos el ID
        $this->nombre_equipo = $nombre_equipo;        // Guardamos el nombre
        $this->descripcion = $descripcion;            // Guardamos la descripción
        $this->estado = $estado;                      // Guardamos el estado
        $this->tipo = $tipo;                          // Guardamos el tipo
        $this->id_tag_material = $id_tag_material;    // Guardamos el tag identificativo
        $this->ultima_rev = $ultima_rev;              // Guardamos la fecha de revisión
        $this->ubicacion = $ubicacion;                // Guardamos la ubicación (puede ser null)
    }

    /* generarIdTag(): genera identificadores legibles tipo "BAN-001".
       Algoritmo:
       1. Toma 3 letras de cada palabra importante (ignora artículos)
       2. Si es muy corto (<2 letras), usa 'MAQ' por defecto
       3. Limita a 5 caracteres máximo
       4. Busca el último ID con ese prefijo en la BD y suma 1 */
    private static function generarIdTag($nombre, $conexion) {
        $words = preg_split('/\s+/', $nombre);                      // Separamos por espacios
        $prefix = '';
        $skip = ['de', 'la', 'el', 'los', 'las', 'del', 'en', 'por', 'con', 'un', 'una'];  // Palabras a ignorar
        foreach ($words as $w) {
            $lower = strtolower(trim($w));
            if ($lower !== '' && !in_array($lower, $skip)) {
                $prefix .= strtoupper(substr($w, 0, 3));            // Cogemos 3 letras
            }
        }
        if (strlen($prefix) < 2) $prefix = 'MAQ';                  // Fallback por defecto
        $prefix = substr($prefix, 0, 5);                            // Limitamos a 5 caracteres

        $stmt = $conexion->prepare("SELECT id_tag_material FROM material WHERE id_tag_material LIKE ? ORDER BY id_tag_material DESC LIMIT 1");  // Buscamos último tag
        $stmt->execute([$prefix . '-%']);
        $last = $stmt->fetchColumn();
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $num = intval($m[1]) + 1;                               // Incrementamos el número
        } else {
            $num = 1;                                                // Primer elemento
        }
        return $prefix . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);  // Formato: PREF-001
    }

    /* listarTodos(): devuelve todos los materiales (opcionalmente filtrados por tipo).
       Ordenados por su identificador visible (id_tag_material). */
    public static function obtenerTodos($tipo = null) {
        $conexion = Conexion::conectar();
        if ($tipo) {
            $stmt = $conexion->prepare("SELECT * FROM material WHERE tipo = ? ORDER BY id_tag_material");  // Filtramos por tipo
            $stmt->execute([$tipo]);
        } else {
            $stmt = $conexion->prepare("SELECT * FROM material ORDER BY id_tag_material");  // Todos sin filtro
            $stmt->execute();
        }
        $materiales = [];                                            // Array donde guardamos los objetos
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $materiales[] = new Material($fila['id_material'], $fila['nombre_equipo'], $fila['descripcion'], $fila['estado'], $fila['tipo'], $fila['id_tag_material'], $fila['ultima_rev'], $fila['ubicacion'] ?? null);
        }
        return $materiales;
    }

    /* buscarPorId(): busca material por su ID numérico interno.
       Devuelve el objeto Material o null si no existe. */
    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM material WHERE id_material = ?");
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Material($fila['id_material'], $fila['nombre_equipo'], $fila['descripcion'], $fila['estado'], $fila['tipo'], $fila['id_tag_material'], $fila['ultima_rev'], $fila['ubicacion'] ?? null);
        }
        return null;
    }

    /* buscarPorIdTag(): busca material por su código visible (ej: "CIN-001").
       Diferente del ID numérico interno. */
    public static function obtenerPorIdTag($id_tag_material) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("SELECT * FROM material WHERE id_tag_material = ?");  // Buscamos por tag
        $stmt->execute([$id_tag_material]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Material($fila['id_material'], $fila['nombre_equipo'], $fila['descripcion'], $fila['estado'], $fila['tipo'], $fila['id_tag_material'], $fila['ultima_rev'], $fila['ubicacion'] ?? null);
        }
        return null;
    }

    /* crear(): añade un material nuevo. Si no se pasa id_tag_material,
       se genera automáticamente con el algoritmo generarIdTag(). */
    public static function crear($nombre_equipo, $descripcion, $estado, $tipo, $id_tag_material, $ultima_rev = null, $ubicacion = null) {
        $conexion = Conexion::conectar();
        if (!$id_tag_material) {
            $id_tag_material = self::generarIdTag($nombre_equipo, $conexion);  // Generamos tag automático
        }
        $stmt = $conexion->prepare("INSERT INTO material (nombre_equipo, descripcion, estado, tipo, id_tag_material, ultima_rev, ubicacion) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $resultado = $stmt->execute([$nombre_equipo, $descripcion, $estado, $tipo, $id_tag_material, $ultima_rev, $ubicacion]);
        if ($resultado) {
            return $conexion->lastInsertId();            // Devolvemos ID del nuevo material
        }
        return false;
    }

    /* actualizar(): modifica todos los campos de un material.
       Localizamos por ID para saber cuál actualizar. */
    public static function actualizar($id_material, $nombre_equipo, $descripcion, $estado, $ultima_rev, $ubicacion = null, $id_tag_material = null) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("UPDATE material SET nombre_equipo = ?, descripcion = ?, estado = ?, ultima_rev = ?, ubicacion = ?, id_tag_material = ? WHERE id_material = ?");
        return $stmt->execute([$nombre_equipo, $descripcion, $estado, $ultima_rev, $ubicacion, $id_tag_material, $id_material]);
    }

    /* eliminar(): borra un material por su ID */
    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM material WHERE id_material = ?");
        return $stmt->execute([$id]);
    }

    /* Getters y métodos auxiliares */
    public function getId() { return $this->id_material; }
    public function getNombre() { return $this->nombre_equipo; }
    public function getDescripcion() { return $this->descripcion; }
    public function getUbicacion() { return $this->ubicacion; }
    public function getEstado() { return $this->estado; }
    public function getTipo() { return $this->tipo; }
    public function getIdTagMaterial() { return $this->id_tag_material; }
    public function getUltimaRev() { return $this->ultima_rev; }
    public function estaOperativo() { return $this->estado === 'operativo'; }
}
