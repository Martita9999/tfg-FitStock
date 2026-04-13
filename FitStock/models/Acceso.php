<?php
    require_once "../config/database.sql";

    class Acceso {
        private $id_acceso;
        private $id_usuario;
        private $fecha_entrada;

        
        public function __construct($id_acceso, $id_usuario, $fecha_entrada){
            $this->id_acceso= $id_acceso;
            $this->id_usuario= $id_usuario;
            $this->fecha_entrada= $fecha_entrada;
        }

        
        public static function registrar($id_usuario){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("INSERT INTO accesos_registro (id_usuario) 
                                        VALUES (?)");
            
            $resultado = $stmt->execute([$id_usuario]);
            
            if ($resultado){
                return $conexion->lastInsertId();
            }
            return false;
        }

        public static function eliminar($id){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("DELETE FROM accesos_registro WHERE id_acceso = ?");
            return $stmt->execute([$id]);
        }

    
        public static function obtenerUltimos($cantidad = 10){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("SELECT a.*, u.nombre as nombre_usuario 
                                        FROM accesos_registro a 
                                        LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario 
                                        ORDER BY a.fecha_entrada DESC 
                                        LIMIT ?");
            $stmt->execute([$cantidad]);
            
            $accesos = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
                $accesos[] = new Acceso($fila['id_acceso'], $fila['id_usuario'], $fila['fecha_entrada']);
            }
            return $accesos;
        }

       
        public static function obtenerTodos(){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("SELECT a.*, u.nombre as nombre_usuario 
                                        FROM accesos_registro a 
                                        LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario 
                                        ORDER BY a.fecha_entrada DESC");
            $stmt->execute();
            
            $accesos = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
                $accesos[] = new Acceso($fila['id_acceso'], $fila['id_usuario'], $fila['fecha_entrada']);
            }
            return $accesos;
        }


        public function getId(){
            return $this->id_acceso;
        }

        public function getIdUsuario(){
            return $this->id_usuario;
        }

        public function getFechaEntrada(){
            return $this->fecha_entrada;
        }
    }
?>