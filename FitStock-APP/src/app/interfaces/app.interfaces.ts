export interface Usuario {
  id: number;
  nombre: string;
  email: string;
  rol: string;
  forzar_cambio_password?: number; // 1 = el usuario debe cambiar su contraseña al iniciar sesión
}

// Interfaz para un material/equipo deportivo
export interface Material {
  id: number;             // ID único del material
  nombre: string;         // Nombre del equipo
  descripcion?: string;   // Descripción opcional
  ubicacion?: string;     // Ubicación en el gimnasio
  estado: string;         // 'operativo', 'averiado', 'mantenimiento'
  tipo: string;           // 'maquina' o 'prestable'
  id_tag_material?: string;  // Identificador único del material
  ultima_rev?: string | null;  // Fecha de última revisión (nullable)
}

// Interfaz para un préstamo de material
export interface Prestamo {
  id: number;               // ID único del préstamo
  id_usuario?: number;      // ID del usuario (opcional en respuestas)
  id_material?: number;     // ID del material (opcional en respuestas)
  usuario: string;          // Nombre del usuario (de JOIN)
  material: string;         // Nombre del material (de JOIN)
  fecha: string;            // Fecha del préstamo
  devolucion: string | null; // Fecha de devolución (null si pendiente)
}

// Interfaz para un producto en stock
export interface ProductoStock {
  id: number;           // ID único del producto
  nombre: string;       // Nombre del producto
  descripcion?: string; // Descripción breve
  cantidad: number;     // Cantidad actual en stock
  stock_minimo: number; // Stock mínimo para alerta
  precio: number;       // Precio unitario
}

// Interfaz para una compra de producto
export interface Compra {
  id: number;
  id_usuario: number;
  id_producto: number;
  nombre_producto: string;
  cantidad: number;
  precio_unitario: number;
  total: number;
  fecha_compra: string;
}

// Interfaz para una incidencia reportada
export interface Incidencia {
  id: number;               // ID único de la incidencia
  id_material?: number;     // ID del material relacionado
  id_user_rep?: number;     // ID del usuario que reportó
  descripcion: string;      // Descripción del problema
  prioridad: string;        // 'baja', 'media', 'alta'
  estado: string;           // 'abierta', 'en_proceso', 'resuelta'
  created_at?: string | null;       // Fecha de creación
  fecha_resolucion?: string | null;  // Fecha de resolución
  nombre_material?: string;          // Nombre del material (de JOIN)
  id_tag_material?: string;          // Identificador del material (de JOIN)
  ubicacion?: string;                // Ubicación del material (de JOIN)
}
