<?php
    require_once "../config/database.sql";

    class Prestamo {
        private $id_prestamo;
        private $id_usuario;
        private $id_material;
        private $fecha_inicio;
        private $fecha_devolucion;


        public function __construct($id_prestamo, $id_usuario, $id_material, $fecha_inicio, $fecha_devolucion){
            $this->id_prestamo= $id_prestamo;
            $this->id_usuario= $id_usuario;
            $this->id_material= $id_material;
            $this->fecha_inicio= $fecha_inicio;
            $this->fecha_devolucion= $fecha_devolucion;
        }

       
        public static function obtenerTodos(){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("SELECT p.*, 
                                            u.nombre as nombre_usuario, 
                                            m.nombre_equipo 
                                    FROM prestamos p 
                                    LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario 
                                    LEFT JOIN material m ON p.id_material = m.id_material 
                                    ORDER BY p.fecha_inicio DESC");
            $stmt->execute();
            
            $prestamos = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
                $prestamos[] = new Prestamo($fila['id_prestamo'], $fila['id_usuario'], $fila['id_material'], $fila['fecha_inicio'], $fila['fecha_devolucion']);
            }
            return $prestamos;
        }

      
        public static function obtenerActivos(){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("SELECT p.*, 
                                            u.nombre as nombre_usuario, 
                                            m.nombre_equipo 
                                    FROM prestamos p 
                                    LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario 
                                    LEFT JOIN material m ON p.id_material = m.id_material 
                                    WHERE p.fecha_devolucion IS NULL 
                                    ORDER BY p.fecha_inicio DESC");
            $stmt->execute();
            
            $prestamos = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
                $prestamos[] = new Prestamo($fila['id_prestamo'], $fila['id_usuario'], $fila['id_material'], $fila['fecha_inicio'], $fila['fecha_devolucion']);
            }
            return $prestamos;
        }

      
        public static function crear($id_usuario, $id_material, $fecha_devolucion){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("INSERT INTO prestamos 
                                        (id_usuario, id_material, fecha_devolucion) 
                                        VALUES (?, ?, ?)");
            
            $resultado = $stmt->execute([$id_usuario, $id_material, $fecha_devolucion]);
            
            if ($resultado) {
                return $conexion->lastInsertId();
            }
            return false;
        }

       
        public static function devolver($id_prestamo){
            $conexion = obtenerConexion();
            
            $stmt = $conexion->prepare("UPDATE prestamos 
                                        SET fecha_devolucion = CURRENT_DATE() 
                                        WHERE id_prestamo = ?");
            
            return $stmt->execute([$id_prestamo]);
        }


        public function getId(){
            return $this->id_prestamo;
        }

        public function getIdUsuario(){
            return $this->id_usuario;
        }

        public function getIdMaterial(){
            return $this->id_material;
        }

        public function getFechaInicio(){
            return $this->fecha_inicio;
        }

        public function getFechaDevolucion(){
            return $this->fecha_devolucion;
        }

     
        public function estaDevuelto(){
            return $this->fecha_devolucion !== null;
        }
    }
?>