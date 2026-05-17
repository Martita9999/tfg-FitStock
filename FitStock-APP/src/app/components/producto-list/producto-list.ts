import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ProductosService } from '../../services/productos.service';
import { UsuarioService } from '../../services/usuario';
import { CartService } from '../../services/cart.service';
import { ProductoStock } from '../../interfaces/app.interfaces';

/*
 * ProductoList: CRUD de productos del inventario.
 * Incluye subida de imágenes con previsualización, carrito de compras,
 * control de stock mínimo y manejo de errores de imagen con reintento.
 */
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
  cartService = inject(CartService);
  lista: ProductoStock[] = [];
  userRole = '';
  selectedProductId: number | null = null;

  showModal = false;
  newProducto = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };
  selectedFile: File | null = null;
  previewUrl: string | null = null;
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
    });
  }

  trackById(index: number, p: ProductoStock) {
    return p.id;
  }

  toggleDescripcion(p: ProductoStock) {
    this.selectedProductId = this.selectedProductId === p.id ? null : p.id;  // Toggle descripción
    this.cartService.agregar(p);
    this.cartService.triggerOpenCart();                                   // Abre el carrito automáticamente
  }

  abrirModal() {
    this.newProducto = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };
    this.selectedFile = null;
    if (this.previewUrl) { URL.revokeObjectURL(this.previewUrl); this.previewUrl = null; }
    this.error = '';
    this.showModal = true;
  }

  cerrarModal() {
    this.showModal = false;
    this.error = '';
    this.previewUrl = null;
  }

  async crearProducto() {
    this.error = '';
    if (!this.newProducto.nombre) {
      this.error = 'El nombre es obligatorio';
      return;
    }
    try {
      await firstValueFrom(this.productosService.createProducto({
        nombre: this.newProducto.nombre,
        descripcion: this.newProducto.descripcion,
        cantidad: this.newProducto.cantidad,
        stock_minimo: this.newProducto.stock_minimo,
        precio: this.newProducto.precio,
      }));
      if (this.selectedFile) {
        await firstValueFrom(this.productosService.subirImagen(this.newProducto.nombre, this.selectedFile));  // Sube imagen tras crear
      }
      this.cerrarModal();
      this.loadProductos();
    } catch {
      this.error = 'Error al crear el producto';
    }
  }

  onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      this.selectedFile = input.files[0];
      if (this.previewUrl) {
        URL.revokeObjectURL(this.previewUrl);
      }
      this.previewUrl = URL.createObjectURL(this.selectedFile);             // Previsualización local
    }
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
    return '/images/productos/' + encodeURIComponent(nombre) + '.jpg';
  }

  /* onImgError: reintenta cargar imagen 1 vez, si falla oculta el elemento */
  onImgError(event: Event) {
    const img = event.target as HTMLImageElement;
    if (!img.getAttribute('data-retried')) {
      img.setAttribute('data-retried', '1');
      setTimeout(() => { img.src = img.src; }, 2000);                       // Reintenta tras 2s
    } else {
      img.style.display = 'none';                                            // Oculta si sigue fallando
    }
  }

  borrarProducto(id: number, nombre: string) {
    if (!confirm(`¿Borrar producto "${nombre}"?`)) return;
    this.productosService.deleteProducto(id).subscribe({
      next: () => this.loadProductos(),
      error: () => alert('Error al borrar el producto')
    });
  }
}
