import { Injectable, inject } from '@angular/core';
import { Subject, firstValueFrom } from 'rxjs';
import { ProductoStock } from '../interfaces/app.interfaces';
import { ProductosService } from './productos.service';
import { ComprasService } from './compras.service';

export interface CartItem {
  producto: ProductoStock;
  cantidad: number;
}

/*
 * CartService: carrito de compras en memoria (sin persistencia).
 * Al comprar: verifica stock, descuenta inventario y registra en compras.
 * NOTA: no se guarda en localStorage, se pierde al recargar la página.
 */
@Injectable({ providedIn: 'root' })
export class CartService {
  private productosService = inject(ProductosService);
  private comprasService = inject(ComprasService);

  carrito: CartItem[] = [];

  /* openCartSubject: señal para que el dashboard abra el carrito automáticamente */
  private openCartSubject = new Subject<void>();
  openCart$ = this.openCartSubject.asObservable();

  triggerOpenCart() {
    this.openCartSubject.next();
  }                                                  // Array de items en el carrito

  /* totalCarrito: suma de (cantidad * precio) de todos los items */
  get totalCarrito(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad * item.producto.precio, 0);
  }

  /* totalItems: suma total de unidades en el carrito */
  get totalItems(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad, 0);
  }

  /* agregar: añade 1 unidad. Si ya existe, incrementa sin superar stock real. No permite stock 0. */
  agregar(producto: ProductoStock) {
    if (producto.cantidad <= 0) return;                                      // No agregamos si no hay stock
    const existente = this.carrito.find(item => item.producto.id === producto.id);
    if (existente) {
      if (existente.cantidad < producto.cantidad) existente.cantidad++;      // Incrementamos sin superar stock
    } else {
      this.carrito.push({ producto, cantidad: 1 });                         // Nuevo item con cantidad 1
    }
  }

  /* quitar: reduce una unidad. Si llega a 0, elimina el item. */
  quitar(item: CartItem) {
    if (item.cantidad > 1) {
      item.cantidad--;
    } else {
      this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    }
  }

  /* setCantidad: asigna una cantidad específica a un item (validada entre 1 y stock máximo) */
  setCantidad(item: CartItem, nueva: number) {
    if (nueva <= 0) {
      this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);  // Elimina si 0 o negativo
    } else {
      item.cantidad = Math.min(nueva, item.producto.cantidad);                       // No supera el stock real
    }
  }

  eliminar(item: CartItem) {
    this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
  }

  limpiar() {
    this.carrito = [];
  }

  /* comprar: procesa toda la compra. Por cada item verifica stock, descuenta y registra compra.
     Si todo ok: vacía carrito y devuelve null. Si error: devuelve mensaje sin vaciar. */
  async comprar(): Promise<string | null> {
    for (const item of this.carrito) {
      const nuevaCant = item.producto.cantidad - item.cantidad;              // Stock resultante
      if (nuevaCant < 0) {
        return `Stock insuficiente para ${item.producto.nombre}`;            // Error: no hay suficiente stock
      }
      try {
        await firstValueFrom(this.productosService.updateProducto(item.producto.id, { cantidad: nuevaCant }));  // Descontamos stock
        await firstValueFrom(this.comprasService.createCompra({
          id_producto: item.producto.id,
          cantidad: item.cantidad,
          precio_unitario: item.producto.precio
        }));                                                                 // Registramos la compra
      } catch {
        return `Error al comprar ${item.producto.nombre}`;                  // Error: devolvemos mensaje
      }
    }
    this.limpiar();                                                          // Todo ok: vaciamos carrito
    return null;
  }
}
