import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { ProductosService } from '../../services/productos.service';
import { UsuarioService } from '../../services/usuario';
import { CartService } from '../../services/cart.service';
import { ProductoStock } from '../../interfaces/app.interfaces';

@Component({
  selector: 'app-producto-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './producto-list.html',
  styleUrl: './producto-list.css',
})
// Componente que lista, crea, edita y elimina productos del inventario
// con soporte de imágenes, carrito de compras y control de stock mínimo
export class ProductoList implements OnInit {
  private productosService = inject(ProductosService);   // Servicio de productos
  private usuarioService = inject(UsuarioService);       // Servicio de autenticación
  cartService = inject(CartService);                     // Servicio del carrito de compras
  lista: ProductoStock[] = [];     // Lista completa de productos desde la API
  userRole = '';                   // Rol del usuario autenticado

  showModal = false;                                                        // Control del modal de creación
  newProducto = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };  // Datos del nuevo producto
  selectedFile: File | null = null;    // Archivo de imagen seleccionado para subir
  previewUrl: string | null = null;    // URL de previsualización de la imagen
  error = '';                          // Mensaje de error
  successMsg = '';                     // Mensaje de éxito

  showEditModal = false;                                                    // Control del modal de edición
  editProducto: ProductoStock | null = null;                                // Producto en edición
  editProductoData = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };  // Datos editados

  // Al iniciar, se suscribe al usuario actual y carga la lista de productos
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

  // Track function para ngFor optimizado por ID
  trackById(index: number, p: ProductoStock) {
    return p.id;
  }

  // Agrega un producto al carrito de compras
  agregarAlCarrito(p: ProductoStock) {
    this.cartService.agregar(p);
  }

  // Abre el modal de creación de producto
  abrirModal() {
    this.newProducto = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };
    this.selectedFile = null;
    if (this.previewUrl) { URL.revokeObjectURL(this.previewUrl); this.previewUrl = null; }
    this.error = '';
    this.showModal = true;
  }

  // Cierra el modal de creación
  cerrarModal() {
    this.showModal = false;
    this.error = '';
    this.previewUrl = null;
  }

  // Envía los datos para crear un nuevo producto
  async crearProducto() {
    this.error = '';
    if (!this.newProducto.nombre) {              // Validación: nombre obligatorio
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
      // Si hay imagen seleccionada, espera a que termine la subida antes de recargar
      if (this.selectedFile) {
        await firstValueFrom(this.productosService.subirImagen(this.newProducto.nombre, this.selectedFile));
      }
      this.cerrarModal();
      this.loadProductos();
    } catch {
      this.error = 'Error al crear el producto';
    }
  }

  // Guarda el archivo seleccionado y genera una previsualización
  onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      this.selectedFile = input.files[0];
      // Limpia la URL anterior si existe
      if (this.previewUrl) {
        URL.revokeObjectURL(this.previewUrl);
      }
      this.previewUrl = URL.createObjectURL(this.selectedFile);
    }
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

  // Construye la URL absoluta de la imagen del producto a partir de su nombre
  getImagenUrl(nombre: string): string {
    return '/images/productos/' + encodeURIComponent(nombre) + '.jpg';
  }

  // Reintenta cargar la imagen tras 2s (por si aún se está subiendo), luego la oculta
  onImgError(event: Event) {
    const img = event.target as HTMLImageElement;
    if (!img.getAttribute('data-retried')) {
      img.setAttribute('data-retried', '1');
      setTimeout(() => { img.src = img.src; }, 2000);
    } else {
      img.style.display = 'none';
    }
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
