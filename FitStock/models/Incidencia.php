<?php
    require_once "../config/database.sql";

    class Incidencia {
        private $id_incidencia;
        private $id_material;
        private $id_user_rep;
        private $descripcion;
        private $prioridad;
        private $estado_inc;

        
        public function __construct($id_incidencia, $id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc){
            $this->id_incidencia= $id_incidencia;
            $this->id_material= $id_material;
            $this->id_user_rep= $id_user_rep;
            $this->descripcion= $descripcion;
            $this->prioridad= $prioridad;
            $this->estado_inc= $estado_inc;
        }

        
        public static function obtenerTodos(){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("SELECT * FROM incidencias ORDER BY id_incidencia DESC");
            $stmt->execute();
            
            $incidencias = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
                $incidencias[] = new Incidencia($fila['id_incidencia'], $fila['id_material'], $fila['id_user_rep'], $fila['descripcion'], $fila['prioridad'], $fila['estado_inc']);
            }
            return $incidencias;
        }

       
        public static function obtenerPorId($id){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("SELECT * FROM incidencias WHERE id_incidencia = ?");
            $stmt->execute([$id]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila){
                return new Incidencia($fila['id_incidencia'], $fila['id_material'], $fila['id_user_rep'], $fila['descripcion'], $fila['prioridad'], $fila['estado_inc']);
            }
            return null;
        }

        
        public static function crear($id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc = 'abierta'){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("INSERT INTO incidencias (id_material, id_user_rep, descripcion, prioridad, estado_inc) 
                                        VALUES (?, ?, ?, ?, ?)");
            
            $ok = $stmt->execute([$id_material, $id_user_rep, $descripcion, $prioridad, $estado_inc]);
            
            if ($ok){
                return $conexion->lastInsertId();
            }
            return false;
        }

        
        public static function actualizar($id, $prioridad, $estado_inc){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("UPDATE incidencias 
                                        SET prioridad = ?, estado_inc = ? 
                                        WHERE id_incidencia = ?");
            return $stmt->execute([$prioridad, $estado_inc, $id]);
        }

       
        public static function eliminar($id){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("DELETE FROM incidencias WHERE id_incidencia = ?");
            return $stmt->execute([$id]);
        }

        
        public function getId(){
            return $this->id_incidencia;
        }

        public function getIdMaterial(){
            return $this->id_material;
        }

        public function getIdUser(){
            return $this->id_user_rep;
        }

        public function getDescripcion(){
            return $this->descripcion;
        }

        public function getPrioridad(){
            return $this->prioridad;
        }

        public function getEstado(){
            return $this->estado_inc;
        }
    }
?>