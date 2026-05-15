import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { ProductoStock } from '../interfaces/app.interfaces';
import { ProductosService } from './productos.service';
import { ComprasService } from './compras.service';

export interface CartItem {
  producto: ProductoStock;
  cantidad: number;
}

@Injectable({ providedIn: 'root' })
export class CartService {
  private productosService = inject(ProductosService);
  private comprasService = inject(ComprasService);

  carrito: CartItem[] = [];

  get totalCarrito(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad * item.producto.precio, 0);
  }

  get totalItems(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad, 0);
  }

  agregar(producto: ProductoStock) {
    if (producto.cantidad <= 0) return;
    const existente = this.carrito.find(item => item.producto.id === producto.id);
    if (existente) {
      if (existente.cantidad < producto.cantidad) existente.cantidad++;
    } else {
      this.carrito.push({ producto, cantidad: 1 });
    }
  }

  quitar(item: CartItem) {
    if (item.cantidad > 1) {
      item.cantidad--;
    } else {
      this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    }
  }

  eliminar(item: CartItem) {
    this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
  }

  limpiar() {
    this.carrito = [];
  }

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
