import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ProductosService } from '../../services/productos.service';
import { ComprasService } from '../../services/compras.service';
import { UsuarioService } from '../../services/usuario';
import { ProductoStock } from '../../interfaces/app.interfaces';

// Interfaz interna para los items del carrito de compras
interface CartItem {
  producto: ProductoStock;   // Producto seleccionado
  cantidad: number;          // Cantidad a comprar
}

@Component({
  selector: 'app-producto-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './producto-list.html',
  styleUrl: './producto-list.css',
})
// Componente que lista los productos en stock con carrito de compras y CRUD
export class ProductoList implements OnInit {
  private productosService = inject(ProductosService);
  private comprasService = inject(ComprasService);
  private usuarioService = inject(UsuarioService);
  lista: ProductoStock[] = [];                             // Lista de productos cargados
  userRole = '';                                           // Rol del usuario actual (para permisos)

  carrito: CartItem[] = [];   // Items en el carrito de compras
  showCart = false;            // Control de visibilidad del panel carrito

  showModal = false;                                          // Control del modal de creación
  newProducto = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };  // Datos del nuevo producto
  error = '';              // Mensaje de error
  successMsg = '';         // Mensaje de éxito

  showEditModal = false;                                           // Control del modal de edición
  editProducto: ProductoStock | null = null;                        // Producto en edición
  editProductoData = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };  // Datos editados

  // Al iniciar, se suscribe al usuario y carga los productos
  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadProductos();
  }

  // Carga la lista de productos desde la API
  loadProductos() {
    this.productosService.getProductos().subscribe(data => {
      this.lista = data;
    });
  }

  // Función trackBy para optimizar el renderizado de la lista
  trackById(index: number, p: ProductoStock) {
    return p.id;
  }

  // Calcula el precio total del carrito
  get totalCarrito(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad * item.producto.precio, 0);
  }

  // Calcula el número total de items en el carrito
  get totalItems(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad, 0);
  }

  // Añade un producto al carrito (incrementa cantidad si ya existe)
  agregarAlCarrito(p: ProductoStock) {
    if (p.cantidad <= 0) return;
    const existente = this.carrito.find(item => item.producto.id === p.id);
    if (existente) {
      if (existente.cantidad < p.cantidad) existente.cantidad++;
    } else {
      this.carrito.push({ producto: p, cantidad: 1 });
    }
  }

  // Reduce la cantidad de un item en el carrito (lo elimina si llega a 0)
  quitarDelCarrito(item: CartItem) {
    if (item.cantidad > 1) {
      item.cantidad--;
    } else {
      this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    }
  }

  // Elimina un item completamente del carrito
  eliminarDelCarrito(item: CartItem) {
    this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
  }

  // Alterna la visibilidad del panel del carrito
  toggleCart() {
    this.showCart = !this.showCart;
  }

  // Cierra el panel del carrito
  cerrarCart() {
    this.showCart = false;
  }

  async comprar() {
    this.error = '';
    this.successMsg = '';
    for (const item of this.carrito) {
      const nuevaCant = item.producto.cantidad - item.cantidad;
      if (nuevaCant < 0) {
        this.error = `Stock insuficiente para ${item.producto.nombre}`;
        return;
      }
      try {
        await firstValueFrom(this.productosService.updateProducto(item.producto.id, { cantidad: nuevaCant }));
        await firstValueFrom(this.comprasService.createCompra({
          id_producto: item.producto.id,
          cantidad: item.cantidad,
          precio_unitario: item.producto.precio
        }));
      } catch {
        this.error = `Error al comprar ${item.producto.nombre}`;
        return;
      }
    }
    this.successMsg = 'Compra realizada con éxito';
    this.carrito = [];
    this.showCart = false;
    this.loadProductos();
    setTimeout(() => this.successMsg = '', 60000);
  }

  // Abre el modal de creación de producto
  abrirModal() {
    this.newProducto = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };
    this.error = '';
    this.showModal = true;
  }

  // Cierra el modal de creación
  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  // Envía los datos para crear un nuevo producto
  crearProducto() {
    this.error = '';
    if (!this.newProducto.nombre) {              // Validación: nombre obligatorio
      this.error = 'El nombre es obligatorio';
      return;
    }
    this.productosService.createProducto({
      nombre: this.newProducto.nombre,
      descripcion: this.newProducto.descripcion,
      cantidad: this.newProducto.cantidad,
      stock_minimo: this.newProducto.stock_minimo,
      precio: this.newProducto.precio,
    }).subscribe({
      next: () => { this.cerrarModal(); this.loadProductos(); },
      error: () => { this.error = 'Error al crear el producto'; }
    });
  }

  // Abre el modal de edición con los datos del producto seleccionado
  abrirEditar(p: ProductoStock) {
    this.editProducto = p;
    this.editProductoData = {
      nombre: p.nombre,
      descripcion: p.descripcion || '',
      cantidad: p.cantidad,
      stock_minimo: p.stock_minimo,
      precio: p.precio
    };
    this.error = '';
    this.showEditModal = true;
  }

  // Cierra el modal de edición
  cerrarEditar() {
    this.showEditModal = false;
    this.editProducto = null;
    this.error = '';
  }

  // Guarda los cambios del producto editado
  guardarEditar() {
    if (!this.editProducto) return;
    this.error = '';
    if (!this.editProductoData.nombre) {              // Validación: nombre obligatorio
      this.error = 'El nombre es obligatorio';
      return;
    }
    this.productosService.updateProducto(this.editProducto.id, {
      nombre: this.editProductoData.nombre,
      descripcion: this.editProductoData.descripcion,
      cantidad: this.editProductoData.cantidad,
      stock_minimo: this.editProductoData.stock_minimo,
      precio: this.editProductoData.precio,
    }).subscribe({
      next: () => { this.cerrarEditar(); this.loadProductos(); },
      error: () => { this.error = 'Error al actualizar el producto'; }
    });
  }

  // Construye la URL de la imagen del producto a partir de su nombre
  getImagenUrl(nombre: string): string {
    return 'images/productos/' + encodeURIComponent(nombre) + '.jpg';
  }

  // Oculta la imagen si ocurre un error al cargarla (imagen no encontrada)
  onImgError(event: Event) {
    const img = event.target as HTMLImageElement;
    img.style.display = 'none';
  }

  // Confirma y elimina un producto por su ID
  borrarProducto(id: number, nombre: string) {
    if (!confirm(`¿Borrar producto "${nombre}"?`)) return;    // Confirmación del usuario
    this.productosService.deleteProducto(id).subscribe({
      next: () => this.loadProductos(),
      error: () => alert('Error al borrar el producto')
    });
  }
}
