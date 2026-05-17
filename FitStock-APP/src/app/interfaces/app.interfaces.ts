/*
 * app.interfaces.ts: interfaces compartidas entre servicios y componentes.
 * Cada una refleja la estructura JSON de la API.
 */

export interface Usuario {                                              // Usuario del sistema
  id: number;
  nombre: string;
  email: string;
  rol: string;
  forzar_cambio_password?: number;                                      // 1 = debe cambiar contraseña
}

export interface Material {                                             // Equipo deportivo
  id: number;
  nombre: string;
  descripcion?: string;
  ubicacion?: string;
  estado: string;                                                       // operativo, averiado, etc.
  tipo: string;                                                         // 'maquina' | 'prestable'
  id_tag_material?: string;                                             // Código tipo CIN-001
  ultima_rev?: string | null;                                           // Fecha última revisión
}

export interface Prestamo {                                             // Préstamo de material
  id: number;
  id_usuario?: number;
  id_material?: number;
  usuario: string;                                                      // Nombre (viene de JOIN)
  material: string;                                                     // Nombre material (de JOIN)
  fecha: string;                                                        // Fecha inicio
  devolucion: string | null;                                            // null = pendiente
  estado?: string;                                                      // pendiente | activo | pendiente_devolucion | devuelto
}

export interface ProductoStock {                                        // Producto en tienda
  id: number;
  nombre: string;
  descripcion?: string;
  cantidad: number;                                                     // Stock actual
  stock_minimo: number;                                                 // Mínimo para alerta
  precio: number;
}

export interface Compra {                                               // Compra de producto
  id: number;
  id_usuario: number;
  id_producto: number;
  nombre_producto: string;                                              // Viene de JOIN
  cantidad: number;
  precio_unitario: number;
  total: number;                                                        // cantidad * precio
  fecha_compra: string;
}

export interface Incidencia {                                           // Reporte de avería
  id: number;
  id_material?: number;
  id_user_rep?: number;                                                 // Quién reportó
  descripcion: string;
  prioridad: string;                                                    // baja, media, alta
  estado: string;                                                       // abierta, en_proceso, resuelta
  created_at?: string | null;
  fecha_resolucion?: string | null;
  nombre_material?: string;                                             // Viene de JOIN
  id_tag_material?: string;
  ubicacion?: string;
}
