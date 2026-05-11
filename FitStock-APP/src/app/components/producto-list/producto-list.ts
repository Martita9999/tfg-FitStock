import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ProductosService } from '../../services/productos.service';
import { UsuarioService } from '../../services/usuario';
import { ProductoStock } from '../../interfaces/app.interfaces';

interface CartItem {
  producto: ProductoStock;
  cantidad: number;
}

@Component({
  selector: 'app-producto-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './producto-list.html',
  styleUrl: './producto-list.css',
})
export class ProductoList implements OnInit {
  private productosService = inject(ProductosService);
  private usuarioService = inject(UsuarioService);
  lista: ProductoStock[] = [];
  userRole = '';

  carrito: CartItem[] = [];
  showCart = false;

  showModal = false;
  newProducto = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };
  error = '';
  successMsg = '';

  showEditModal = false;
  editProducto: ProductoStock | null = null;
  editProductoData = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };

  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadProductos();
  }

  loadProductos() {
    this.productosService.getProductos().subscribe(data => {
      this.lista = data;
      console.log('Productos cargados:', data);
    });
  }

  trackById(index: number, p: ProductoStock) {
    return p.id;
  }

  get totalCarrito(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad * item.producto.precio, 0);
  }

  get totalItems(): number {
    return this.carrito.reduce((sum, item) => sum + item.cantidad, 0);
  }

  agregarAlCarrito(p: ProductoStock) {
    if (p.cantidad <= 0) return;
    const existente = this.carrito.find(item => item.producto.id === p.id);
    if (existente) {
      if (existente.cantidad < p.cantidad) existente.cantidad++;
    } else {
      this.carrito.push({ producto: p, cantidad: 1 });
    }
  }

  quitarDelCarrito(item: CartItem) {
    if (item.cantidad > 1) {
      item.cantidad--;
    } else {
      this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
    }
  }

  eliminarDelCarrito(item: CartItem) {
    this.carrito = this.carrito.filter(i => i.producto.id !== item.producto.id);
  }

  toggleCart() {
    this.showCart = !this.showCart;
  }

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
      } catch {
        this.error = `Error al comprar ${item.producto.nombre}`;
        return;
      }
    }
    this.successMsg = 'Compra realizada con éxito';
    console.log('✅ Compra realizada con éxito');
    this.carrito = [];
    this.showCart = false;
    this.loadProductos();
    setTimeout(() => this.successMsg = '', 60000);
  }

  abrirModal() {
    this.newProducto = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };
    this.error = '';
    this.showModal = true;
  }

  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  crearProducto() {
    this.error = '';
    if (!this.newProducto.nombre) {
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

  cerrarEditar() {
    this.showEditModal = false;
    this.editProducto = null;
    this.error = '';
  }

  guardarEditar() {
    if (!this.editProducto) return;
    this.error = '';
    if (!this.editProductoData.nombre) {
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

  getImagenUrl(nombre: string): string {
    return 'images/productos/' + encodeURIComponent(nombre) + '.jpg';
  }

  onImgError(event: Event) {
    const img = event.target as HTMLImageElement;
    img.style.display = 'none';
  }

  borrarProducto(id: number, nombre: string) {
    if (!confirm(`¿Borrar producto "${nombre}"?`)) return;
    this.productosService.deleteProducto(id).subscribe({
      next: () => this.loadProductos(),
      error: () => alert('Error al borrar el producto')
    });
  }
}
