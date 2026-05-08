import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MaterialesService } from '../../services/materiales.service';
import { UsuarioService } from '../../services/usuario';
import { Material } from '../../interfaces/app.interfaces';

// Componente que lista y gestiona los materiales/equipamiento deportivo
@Component({
  selector: 'app-material-list',
  standalone: true,
  imports: [CommonModule, FormsModule],    // Módulos: directivas comunes + formularios
  templateUrl: './material-list.html',
  styleUrl: './material-list.css',
})
export class MaterialList implements OnInit {
  private materialesService = inject(MaterialesService);   // Servicio de materiales
  private usuarioService = inject(UsuarioService);         // Servicio de usuario (para el rol)
  lista: Material[] = [];                                   // Lista de materiales
  userRole = '';                                            // Rol del usuario actual

  showModal = false;                                        // Control de visibilidad del modal
  newMaterial = { nombre: '', descripcion: '', estado: 'operativo', qr: '' };   // Datos del nuevo material
  error = '';                                               // Mensaje de error

  // Al iniciar, obtiene el rol y carga los materiales
  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadMateriales();
  }

  // Carga la lista de máquinas desde la API
  loadMateriales() {
    this.materialesService.getMateriales('maquina').subscribe(data => this.lista = data);
  }

  // Abre el modal de creación de material
  abrirModal() {
    this.newMaterial = { nombre: '', descripcion: '', estado: 'operativo', qr: '' };
    this.error = '';
    this.showModal = true;
  }

  // Cierra el modal de creación
  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  // Envía los datos para crear un nuevo material
  crearMaterial() {
    this.error = '';
    if (!this.newMaterial.nombre) {                 // Validación: nombre obligatorio
      this.error = 'El nombre es obligatorio';
      return;
    }
    this.materialesService.createMaterial(this.newMaterial).subscribe({
      next: () => { this.cerrarModal(); this.loadMateriales(); },   // Éxito: cierra modal y recarga
      error: () => { this.error = 'Error al crear el material'; }
    });
  }

  // Confirma y elimina un material
  borrarMaterial(id: number, nombre: string) {
    if (!confirm(`¿Borrar material "${nombre}"?`)) return;    // Confirmación del usuario
    this.materialesService.deleteMaterial(id).subscribe({
      next: () => this.loadMateriales(),                      // Éxito: recarga la lista
      error: () => alert('Error al borrar el material')
    });
  }
}
