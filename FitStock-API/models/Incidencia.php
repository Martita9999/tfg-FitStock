<?php
/* ----------------------------------------------------
 * Modelo Incidencia
 * ----------------------------------------------------
 * Aquí gestionamos los reportes de problemas en los
 * materiales/máquinas del gimnasio.
 * Cuando una máquina se estropea, se crea una incidencia
 * para llevar un registro y control de la reparación.
 *
 * Datos importantes:
 *   - Cada incidencia está asociada a un material (máquina)
 *   - Tiene prioridad (baja/media/alta)
 *   - Tiene estado (abierta/en_proceso/resuelta)
 *   - Al resolverla, se guarda la fecha automáticamente
 * ---------------------------------------------------- */

require_once __DIR__ . "/../conexion.php";  // Traemos la conexión a la BD

class Incidencia {
    /* Propiedades privadas que representan las columnas de la tabla 'incidencias' */
    private $id_incidencia;               // ID único de la incidencia
    private $id_material;                 // El material/máquina con el problema
    private $id_user_rep;                 // Usuario que reportó la incidencia
    private $descripcion;                 // Explicación de lo que pasa
    private $prioridad;                   // baja, media o alta
    private $estado_inc;                  // abierta, en_proceso o resuelta
    private $created_at;                  // Fecha de creación (automática)
    private $fecha_resolucion;            // Fecha de resolución (la pone el sistema)
    private $nombre_material;             // Nombre del material (con JOIN)
    private $id_tag_material;             // Identificador tipo CIN-001 (con JOIN)
    private $ubicacion;                   // Dónde está el material

    /* Constructor: montamos el objeto con todos los datos.
       Los parámetros de JOIN son opcionales, solo se rellenan en consultas. */
    public function __construct($id_incidencia, $id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc, $created_at = null, $fecha_resolucion = null, $nombre_material = null, $id_tag_material = null, $ubicacion = null) {
        $this->id_incidencia = $id_incidencia;        // Guardamos el ID de incidencia
        $this->id_material = $id_material;            // Guardamos el ID del material
        $this->id_user_rep = $id_user_rep;            // Guardamos el ID del reportador
        $this->descripcion = $descripcion;            // Guardamos la descripción
        $this->prioridad = $prioridad;                // Guardamos la prioridad
        $this->estado_inc = $estado_inc;              // Guardamos el estado
        $this->created_at = $created_at;              // Guardamos la fecha de creación
        $this->fecha_resolucion = $fecha_resolucion;  // Guardamos la fecha de resolución
        $this->nombre_material = $nombre_material;    // Guardamos el nombre (de JOIN)
        $this->id_tag_material = $id_tag_material;    // Guardamos el tag (de JOIN)
        $this->ubicacion = $ubicacion;                // Guardamos la ubicación (de JOIN)
    }

    /* listarTodas(): recupera todas las incidencias ordenadas (más reciente primero).
       Hacemos LEFT JOIN con material para mostrar nombre, tag y ubicación.
       Si se pasa id_usuario, filtra por el usuario que reportó (id_user_rep).
       Así el frontend tiene toda la info en un solo objeto. */
    public static function obtenerTodos($id_usuario = null) {
        $conexion = Conexion::conectar();              // Abrimos conexión
        $sql = "SELECT i.id_incidencia, i.id_material, i.id_user_rep, i.descripcion,
                       i.prioridad, i.estado_inc, i.created_at, i.fecha_resolucion,
                       COALESCE(m.nombre_equipo, '—') as nombre_material,
                       m.id_tag_material as id_tag_material,
                       m.ubicacion as ubicacion
                FROM incidencias i
                LEFT JOIN material m ON i.id_material = m.id_material";  // JOIN para nombres
        if ($id_usuario) {
            $sql .= " WHERE i.id_user_rep = ?";         // Filtramos por usuario reportador
        }
        $sql .= " ORDER BY i.id_incidencia DESC";                        // Más reciente primero
        $stmt = $conexion->prepare($sql);
        $stmt->execute($id_usuario ? [$id_usuario] : []);
        $incidencias = [];                               // Array donde guardamos los objetos
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $incidencias[] = new Incidencia(
                $fila['id_incidencia'], $fila['id_material'], $fila['id_user_rep'],
                $fila['descripcion'], $fila['prioridad'], $fila['estado_inc'],
                $fila['created_at'], $fila['fecha_resolucion'],
                $fila['nombre_material'], $fila['id_tag_material'],
                $fila['ubicacion']
            );
        }
        return $incidencias;                             // Devolvemos el array completo
    }

    /* buscarPorId(): busca una incidencia por su ID con JOIN a material.
       Se usa al editar para cargar los datos existentes.
       Si no existe, devuelve null. */
    public static function obtenerPorId($id) {
        $conexion = Conexion::conectar();
        $sql = "SELECT i.id_incidencia, i.id_material, i.id_user_rep, i.descripcion,
                       i.prioridad, i.estado_inc, i.created_at, i.fecha_resolucion,
                       COALESCE(m.nombre_equipo, '—') as nombre_material,
                       m.id_tag_material as id_tag_material,
                       m.ubicacion as ubicacion
                FROM incidencias i
                LEFT JOIN material m ON i.id_material = m.id_material
                WHERE i.id_incidencia = ?";                           // Filtramos por ID
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            return new Incidencia(
                $fila['id_incidencia'], $fila['id_material'], $fila['id_user_rep'],
                $fila['descripcion'], $fila['prioridad'], $fila['estado_inc'],
                $fila['created_at'], $fila['fecha_resolucion'],
                $fila['nombre_material'], $fila['id_tag_material'],
                $fila['ubicacion']
            );
        }
        return null;                                     // No encontrada
    }

    /* crear(): inserta una nueva incidencia.
       Por defecto el estado es 'abierta'.
       Devuelve el ID de la incidencia creada. */
    public static function crear($id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc = 'abierta') {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("INSERT INTO incidencias (id_material, id_user_rep, descripcion, prioridad, estado_inc) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc]);
        return $conexion->lastInsertId();                // Devolvemos el ID generado
    }

    /* actualizar(): modifica una incidencia. Solo actualiza los campos
       que NO son null, permitiendo cambios parciales.
       - Si el estado pasa a 'resuelta', pone fecha_resolucion = NOW()
       - Si ya no está 'resuelta', pone fecha_resolucion = NULL */
    public static function actualizar($id, $prioridad, $estado_inc, $descripcion = null) {
        $conexion = Conexion::conectar();
        $campos = [];                                    // Lista de campos a actualizar
        $valores = [];                                   // Valores correspondientes
        if ($descripcion !== null) {
            $campos[] = "descripcion = ?";
            $valores[] = $descripcion;
        }
        if ($prioridad !== null) {
            $campos[] = "prioridad = ?";
            $valores[] = $prioridad;
        }
        if ($estado_inc !== null) {
            $campos[] = "estado_inc = ?";
            $valores[] = $estado_inc;
            $campos[] = "fecha_resolucion = IF(? = 'resuelta', NOW(), NULL)";  // Auto-fecha
            $valores[] = $estado_inc;
        }
        if (empty($campos)) return true;                 // Nada que actualizar
        $valores[] = $id;
        $sql = "UPDATE incidencias SET " . implode(", ", $campos) . " WHERE id_incidencia = ?";  // SQL dinámica
        $stmt = $conexion->prepare($sql);
        return $stmt->execute($valores);
    }

    /* eliminar(): borra una incidencia por su ID */
    public static function eliminar($id) {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare("DELETE FROM incidencias WHERE id_incidencia = ?");
        return $stmt->execute([$id]);
    }

    /* Getters: acceso a las propiedades privadas desde fuera */
    public function getId() { return $this->id_incidencia; }
    public function getIdMaterial() { return $this->id_material; }
    public function getIdUser() { return $this->id_user_rep; }
    public function getDescripcion() { return $this->descripcion; }
    public function getPrioridad() { return $this->prioridad; }
    public function getEstado() { return $this->estado_inc; }
    public function getCreatedAt() { return $this->created_at; }
    public function getFechaResolucion() { return $this->fecha_resolucion; }
    public function getNombreMaterial() { return $this->nombre_material; }
    public function getIdTagMaterial() { return $this->id_tag_material; }
    public function getUbicacion() { return $this->ubicacion; }
}
