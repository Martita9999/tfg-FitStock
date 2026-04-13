<?php
    require_once "../config/database.sql";

    class Usuario {
        private $id_usuario;
        private $nombre;
        private $email;
        private $rol;
        public $password_hash;

   
        public function __construct($id_usuario, $nombre, $email, $rol){
            $this->id_usuario= $id_usuario;
            $this->nombre= $nombre;
            $this->email= $email;
            $this->rol= $rol;
        }

       
        public static function obtenerTodos(){
            $conexion= obtenerConexion();
            $stmt= $conexion->prepare("SELECT id_usuario, nombre, email, rol FROM usuarios ORDER BY nombre");
            $stmt->execute();
            
            $usuarios = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
                $usuarios[] = new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['rol']);
            }
            return $usuarios;
        }

       
        public static function obtenerPorId($id){
            $conexion= obtenerConexion();
            $stmt= $conexion->prepare("SELECT id_usuario, nombre, email, rol FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$id]);
            $fila= $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila){
                return new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['rol']);
            }
            return null;
        }

     
        public static function obtenerPorEmail($email){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("SELECT id_usuario, nombre, email, rol, password_hash 
                                        FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila){
                $usuario = new Usuario($fila['id_usuario'], $fila['nombre'], $fila['email'], $fila['rol']);
                $usuario-> password_hash = $fila['password_hash'];  
                return $usuario;
            }
            return null;
        }


        public static function crear($nombre, $email, $password_hash, $rol = 'cliente'){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password_hash, rol) 
                                        VALUES (?, ?, ?, ?)");
            
            $ok = $stmt->execute([$nombre, $email, $password_hash, $rol]);
            
            if ($ok) {
                return $conexion->lastInsertId();
            }
            return false;
        }


        public static function actualizar($id, $nombre, $email, $rol){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("UPDATE usuarios 
                                        SET nombre = ?, email = ?, rol = ? 
                                        WHERE id_usuario = ?");
            return $stmt->execute([$nombre, $email, $rol, $id]);
        }

        public static function eliminar($id){
            $conexion = obtenerConexion();
            $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            return $stmt->execute([$id]);
        }
        

        public function getId(){
            return $this->id_usuario;
        }

        public function getNombre(){
            return $this->nombre;
        }

        public function getEmail(){
            return $this->email;
        }

        public function getRol(){
            return $this->rol;
        }

        public function esAdmin(){
            return $this->rol === 'admin';
        }
    }
?>