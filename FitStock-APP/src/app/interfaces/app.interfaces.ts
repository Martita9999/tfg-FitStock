// Interfaz para un usuario del sistema
export interface Usuario {
  id: number;           // ID único del usuario
  nombre: string;       // Nombre completo
  email: string;        // Correo electrónico
  rol: string;          // Rol: 'admin', 'entrenador' o 'cliente'
}

// Interfaz para un material/equipo deportivo
export interface Material {
  id: number;             // ID único del material
  nombre: string;         // Nombre del equipo
  descripcion?: string;   // Descripción opcional
  ubicacion?: string;     // Ubicación en el gimnasio
  estado: string;         // 'operativo', 'averiado', 'mantenimiento'
  tipo: string;           // 'maquina' o 'prestable'
  qr?: string;            // Código QR identificador (opcional)
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

// Interfaz para una incidencia reportada
export interface Incidencia {
  id: number;               // ID único de la incidencia
  id_material?: number;     // ID del material relacionado
  id_user_rep?: number;     // ID del usuario que reportó
  descripcion: string;      // Descripción del problema
  prioridad: string;        // 'baja', 'media', 'alta', 'critica'
  estado: string;           // 'abierta', 'en_proceso', 'resuelta'
  created_at?: string | null;       // Fecha de creación
  fecha_resolucion?: string | null;  // Fecha de resolución
  nombre_material?: string;          // Nombre del material (de JOIN)
}
