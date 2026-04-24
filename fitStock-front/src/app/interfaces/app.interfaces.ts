export interface Producto {
  id: number;
  nombre: string;
  categoria: string;
  resistencia?: string; 
  tamano?: string;      
  stockDisponible: number;
  imagenUrl?: string;  
}

export interface Prestamo {
  id: number;
  productoId: number;
  productoNombre: string;
  usuarioNombre: string;
  usuarioTelefono: string;
  cantidad: number;
  diasPrestamo: number;
  observaciones?: string;
  estado: 'Pendiente' | 'Entregado' | 'Devuelto'; 
}

export interface Incidencia {
  id: number;
  area: string;
  maquinaEquipo?: string;
  titulo: string;
  descripcion: string;
  urgencia: 'Baja' | 'Moderada' | 'Alta' | 'Crítica';
  usuarioNombre: string;
  estado: 'Abierta' | 'En Proceso' | 'Resuelta'; 
}