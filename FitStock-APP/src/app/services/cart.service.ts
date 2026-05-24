import { Injectable, inject, OnDestroy } from '@angular/core';
import { Subject, firstValueFrom } from 'rxjs';
import { ProductoStock } from '../interfaces/app.interfaces';
import { ProductosService } from './productos.service';
import { ComprasService } from './compras.service';
import { UsuarioService } from './usuario';

export type CompraResult = string | null | '__NEED_PAYMENT__';

export interface CartItem {
  producto: ProductoStock;
  cantidad: number;
}

/*
 * Clave para guardar el carrito en localStorage.
 * Se usa un prefijo fijo + el id del usuario para que cada usuario
 * tenga su propio carrito independiente.
 * Ejemplo: fitstock_carrito_3 para el usuario con id=3.
 */
const CART_PREFIX = 'fitstock_carrito_';

/*
 * CartService: servicio central del carrito de compras.
 *
 * Responsabilidades principales:
 * - Mantener el carrito en memoria (this.carrito)
 * - Persistir el carrito en localStorage por usuario
 * - Gestionar las operaciones (agregar, quitar, eliminar, limpiar)
 * - Iniciar el flujo de pago con Stripe
 * - Ejecutar la compra final (descontar stock + registrar en BD)
 *
 * Cada usuario autenticado tiene su propio carrito. Cuando el usuario
 * cierra sesión y otro inicia, se guarda el carrito del anterior y
 * se carga el del nuevo automáticamente.
 *
 * El carrito persiste en localStorage incluso si se cierra el navegador,
 * pero solo para el último usuario que estuvo logueado en ese navegador.
 */
@Injectable({ providedIn: 'root' })
export class CartService implements OnDestroy {
  private productosService = inject(ProductosService);
  private comprasService = inject(ComprasService);
  private usuarioService = inject(UsuarioService);

  /* Array público con los items del carrito. Los componentes lo leen directamente. */
  carrito: CartItem[] = [];

  /*
   * userId: id del usuario actual. Se actualiza cuando el usuario cambia.
   * Se usa como parte de la clave de localStorage para separar carritos.
   * null significa que no hay sesión activa y no se persiste nada.
   */
  private userId: number | null = null;

  /*
   * Constructor: se suscribe al observable currentUser$ del UsuarioService.
   * Cada vez que el usuario cambia (login, logout, recarga de página):
   * 1. Guarda el carrito del usuario anterior en localStorage
   * 2. Actualiza el userId con el nuevo usuario
   * 3. Carga el carrito del nuevo usuario desde localStorage
   *
   * Así aseguramos que cada usuario vea SOLO sus propios productos.
   */
  constructor() {
    this.usuarioService.currentUser$.subscribe(u => {
      const newId = u?.id ?? null;
      if (newId !== this.userId) {
        if (this.userId !== null) this.guardarCarrito(this.userId);
        this.userId = newId;
        this.cargarCarrito();
      }
    });
  }

  /*
   * ngOnDestroy: al destruirse el servicio (raro en Angular, pero posible),
   * guardamos el carrito por si acaso para no perder los datos.
   */
  ngOnDestroy() {
    if (this.userId !== null) this.guardarCarrito(this.userId);
  }

  /* storageKey: genera la clave de localStorage para un usuario concreto */
  private storageKey(id: number): string {
    return CART_PREFIX + id;
  }

  /*
   * cargarCarrito: carga el carrito del usuario actual desde localStorage.
   * Si el JSON está corrupto (try-catch), empezamos con carrito vacío.
   * Si no hay usuario logueado (userId === null), el carrito se queda vacío.
   */
  private cargarCarrito() {
    this.carrito = [];
    if (this.userId === null) return;
    const guardado = localStorage.getItem(this.storageKey(this.userId));
    if (guardado) {
      try {
        this.carrito = JSON.parse(guardado);
      } catch {
        this.carrito = [];
      }
    }
  }

  /*
   * guardarCarrito: persiste el carrito de un usuario en localStorage.
   * Si el carrito está vacío, eliminamos la clave para no acumular basura.
   * Si no está vacío, lo guardamos como JSON.
   */
  private guardarCarrito(uid: number) {
    if (this.carrito.length > 0) {
      localStorage.setItem(this.storageKey(uid), JSON.stringify(this.carrito));
    } else {
      localStorage.removeItem(this.storageKey(uid));
    }
  }

  /*
   * openCartSubject: señal interna para que el dashboard abra el carrito
   * automáticamente cuando se añade un producto desde cualquier componente.
   * El dashboard se suscribe a openCart$ y muestra el dropdown del carrito.
   */
  private openCartSubject = new Subject<void>();
  openCart$ = this.openCartSubject.asObservable();

  /* triggerOpenCart: el componente producto-list la llama al añadir un producto */
  triggerOpenCart() {
    this.openCartSubject.next();
  }

  /* totalCarrito: suma el precio de todos los items del carrito */
  get totalCarrito(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad * item.producto.precio, 0);
  }

  /* totalItems: suma la cantidad total de unidades en el carrito */
  get totalItems(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad, 0);
  }

  /*
   * agregar: añade una unidad de un producto al carrito.
   * - Si el producto ya está en el carrito, incrementa la cantidad
   *   sin superar el stock disponible.
   * - Si no está, lo añade con cantidad 1.
   * - Si el stock es 0, no hace nada.
   * Después de modificar, persiste en localStorage.
   */
  agregar(producto: ProductoStock) {
    if (producto.cantidad <= 0) return;
    const existente = this.carrito.find(item => item.producto.id === producto.id);
    if (existente) {
      if (existente.cantidad < producto.cantidad) existente.cantidad++;
    } else {
      this.carrito.push({ producto, cantidad: 1 });
    }
    if (this.userId !== null) this.guardarCarrito(this.userId);
  }

  /*
   * quitar: reduce una unidad de un item del carrito.
   * Si la cantidad llega a 0, elimina el item del carrito.
   */
  quitar(item: CartItem) {
    if (item.cantidad > 1) {
      item.cantidad--;
    } else {
      this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    }
    if (this.userId !== null) this.guardarCarrito(this.userId);
  }

  /*
   * setCantidad: asigna una cantidad específica a un item.
   * Se usa cuando el usuario edita manualmente la cantidad.
   * Valida que no supere el stock disponible.
   * Si la cantidad es 0 o negativa, elimina el item.
   */
  setCantidad(item: CartItem, nueva: number) {
    if (nueva <= 0) {
      this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    } else {
      item.cantidad = Math.min(nueva, item.producto.cantidad);
    }
    if (this.userId !== null) this.guardarCarrito(this.userId);
  }

  /* eliminar: borra un item completo del carrito sin importar su cantidad */
  eliminar(item: CartItem) {
    this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    if (this.userId !== null) this.guardarCarrito(this.userId);
  }

  /*
   * limpiar: vacía el carrito completamente.
   * También borra la clave de localStorage para el usuario actual.
   * Se llama después de una compra exitosa.
   */
  limpiar() {
    this.carrito = [];
    if (this.userId !== null) {
      localStorage.removeItem(this.storageKey(this.userId));
    }
  }

  /*
   * comprar: inicia el flujo de pago con Stripe.
   *
   * Primero verifica que todos los productos tengan stock suficiente
   * (que nadie haya comprado mientras el usuario tenía el carrito abierto).
   *
   * Si todo está bien, devuelve '__NEED_PAYMENT__' para que el componente
   * dashboard sepa que debe mostrar el formulario de tarjeta.
   *
   * Si hay problemas de stock, devuelve un mensaje de error descriptivo.
   */
  async comprar(): Promise<CompraResult> {
    for (const item of this.carrito) {
      const nuevaCant = item.producto.cantidad - item.cantidad;
      if (nuevaCant < 0) {
        return `Stock insuficiente para ${item.producto.nombre}`;
      }
    }
    return '__NEED_PAYMENT__';
  }

  /*
   * finalizarCompra: ejecuta la compra después del pago exitoso.
   *
   * Por cada producto en el carrito:
   * 1. Calcula el nuevo stock (actual - comprado)
   * 2. Actualiza el stock en la BD (ProductosService)
   * 3. Registra la compra en la BD (ComprasService)
   *
   * Si todo sale bien, limpia el carrito y devuelve null.
   * Si algo falla, devuelve un mensaje de error.
   *
   * IMPORTANTE: solo se llama DESPUÉS de que Stripe haya confirmado el pago.
   */
  async finalizarCompra(): Promise<string | null> {
    for (const item of this.carrito) {
      const nuevaCant = item.producto.cantidad - item.cantidad;
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
