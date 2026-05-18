import { Injectable, inject } from '@angular/core';
import { Subject, firstValueFrom } from 'rxjs';
import { ProductoStock } from '../interfaces/app.interfaces';
import { ProductosService } from './productos.service';
import { ComprasService } from './compras.service';
import { UsuarioService } from './usuario';

export interface CartItem {
  producto: ProductoStock;
  cantidad: number;
}

/*
 * CartService: carrito de compras con persistencia por usuario en localStorage.
 * Se guarda automáticamente tras cada cambio y se restaura al iniciar sesión.
 * Al cerrar sesión se limpia de memoria pero no se borra de localStorage.
 * Al volver a entrar con el mismo usuario se recupera lo que tenía.
 */
@Injectable({ providedIn: 'root' })
export class CartService {
  private productosService = inject(ProductosService);
  private comprasService = inject(ComprasService);
  private usuarioService = inject(UsuarioService);

  carrito: CartItem[] = [];

  private currentUserId: number | null = null;

  private openCartSubject = new Subject<void>();
  openCart$ = this.openCartSubject.asObservable();

  constructor() {
    this.usuarioService.currentUser$.subscribe(user => {
      if (user) {
        this.currentUserId = user.id;
        this.cargarCarrito();
      } else {
        this.currentUserId = null;
        this.carrito = [];
      }
    });
  }

  private getStorageKey(): string {
    return `cart_${this.currentUserId}`;
  }

  private guardarCarrito() {
    if (this.currentUserId === null) return;
    const data = this.carrito.map(item => ({
      id: item.producto.id,
      cantidad: item.cantidad
    }));
    localStorage.setItem(this.getStorageKey(), JSON.stringify(data));
  }

  private cargarCarrito() {
    if (this.currentUserId === null) {
      this.carrito = [];
      return;
    }
    const raw = localStorage.getItem(this.getStorageKey());
    if (!raw) {
      this.carrito = [];
      return;
    }
    try {
      const data: { id: number; cantidad: number }[] = JSON.parse(raw);
      this.productosService.getProductos().subscribe(productos => {
        this.carrito = data
          .map(d => {
            const producto = productos.find(p => p.id === d.id);
            return producto ? { producto, cantidad: Math.min(d.cantidad, producto.cantidad) } : null;
          })
          .filter((item): item is CartItem => item !== null);
      });
    } catch {
      this.carrito = [];
    }
  }

  triggerOpenCart() {
    this.openCartSubject.next();
  }

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
    this.guardarCarrito();
  }

  quitar(item: CartItem) {
    if (item.cantidad > 1) {
      item.cantidad--;
    } else {
      this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    }
    this.guardarCarrito();
  }

  setCantidad(item: CartItem, nueva: number) {
    if (nueva <= 0) {
      this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    } else {
      item.cantidad = Math.min(nueva, item.producto.cantidad);
    }
    this.guardarCarrito();
  }

  eliminar(item: CartItem) {
    this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    this.guardarCarrito();
  }

  limpiar() {
    this.carrito = [];
    this.guardarCarrito();
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
