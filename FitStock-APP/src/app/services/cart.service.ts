import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { ProductoStock } from '../interfaces/app.interfaces';
import { ProductosService } from './productos.service';
import { ComprasService } from './compras.service';

// Representa un producto dentro del carrito con su cantidad seleccionada
export interface CartItem {
  producto: ProductoStock;
  cantidad: number;
}

// Servicio que implementa la lógica del carrito de compras en memoria.
// Permite agregar, quitar, eliminar productos, calcular totales y
// finalizar la compra descontando stock y registrando las transacciones.
@Injectable({ providedIn: 'root' })
export class CartService {
  private productosService = inject(ProductosService);
  private comprasService = inject(ComprasService);

  // Array con los productos actuales en el carrito
  carrito: CartItem[] = [];

  // Calcula el importe total del carrito (suma de cantidad * precio de cada producto)
  get totalCarrito(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad * item.producto.precio, 0);
  }

  // Calcula el número total de unidades (suma de cantidades) en el carrito
  get totalItems(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad, 0);
  }

  // Agrega una unidad de un producto al carrito.
  // Si el producto ya existe, incrementa la cantidad siempre que no supere el stock disponible.
  // No permite agregar productos con stock cero.
  agregar(producto: ProductoStock) {
    if (producto.cantidad <= 0) return;
    const existente = this.carrito.find(item => item.producto.id === producto.id);
    if (existente) {
      if (existente.cantidad < producto.cantidad) existente.cantidad++;
    } else {
      this.carrito.push({ producto, cantidad: 1 });
    }
  }

  // Reduce en una unidad la cantidad de un item del carrito.
  // Si la cantidad llega a 0, elimina el item completamente.
  quitar(item: CartItem) {
    if (item.cantidad > 1) {
      item.cantidad--;
    } else {
      this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    }
  }

  // Elimina un item del carrito independientemente de su cantidad
  eliminar(item: CartItem) {
    this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
  }

  // Vacía el carrito por completo
  limpiar() {
    this.carrito = [];
  }

  // Procesa la compra de todos los items del carrito:
  // 1. Verifica stock suficiente para cada producto.
  // 2. Actualiza el stock llamando a ProductosService.updateProducto().
  // 3. Registra cada compra llamando a ComprasService.createCompra().
  // 4. Si todo es exitoso, vacía el carrito y devuelve null.
  // 5. Si hay error, devuelve un mensaje de error sin vaciar el carrito.
  async comprar(): Promise<string | null> {
    for (const item of this.carrito) {
      const nuevaCant = item.producto.cantidad - item.cantidad;
      if (nuevaCant < 0) {
        return `Stock insuficiente para ${item.producto.nombre}`;
      }
      try {
        await firstValueFrom(this.productosService.updateProducto(item.producto.id, { cantidad: nuevaCant }));
        await firstValueFrom(this.comprasService.createCompra({
          id_producto: item.producto.id,
          cantidad: item.cantidad,
          precio_unitario: item.producto.precio
        }));
      } catch {
        return `Error al comprar ${item.producto.nombre}`;
      }
    }
    this.limpiar();
    return null;
  }
}
