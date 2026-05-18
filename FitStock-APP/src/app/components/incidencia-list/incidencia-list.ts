import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { IncidenciasService } from '../../services/incidencias.service';
import { MaterialesService } from '../../services/materiales.service';
import { UsuarioService } from '../../services/usuario';
import { Incidencia, Material } from '../../interfaces/app.interfaces';
import { ConfirmService } from '../../services/confirm.service';
import { ToastService } from '../../services/toast.service';

/*
 * IncidenciaList: CRUD de incidencias reportadas en máquinas.
 * Separa activas (abierta/en_proceso) de resueltas.
 * Al crear, backend marca máquina como 'averiado'.
 * Al editar, cambia prioridad y estado.
 */
@Component({
  selector: 'app-incidencia-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './incidencia-list.html',
  styleUrl: './incidencia-list.css',
})
export class IncidenciaList implements OnInit {
  private incidenciasService = inject(IncidenciasService);
  private materialesService = inject(MaterialesService);
  private usuarioService = inject(UsuarioService);
  private route = inject(ActivatedRoute);
  private confirmService = inject(ConfirmService);
  private toastService = inject(ToastService);
  lista: Incidencia[] = [];
  materiales: Material[] = [];
  userRole = '';
  vista: 'completa' | 'activas' | 'resueltas' = 'completa';

  showModal = false;
  newIncidencia = { id_material: 0, descripcion: '', prioridad: 'media' };
  error = '';

  showEditModal = false;
  editIncidencia: Incidencia | null = null;
  editData = { descripcion: '', prioridad: '', estado: '' };

  estadosDisponibles = [
    { value: 'abierta', label: 'Averiado' },
    { value: 'en_proceso', label: 'Mantenimiento' },
  ];

  private sortByIdTag(a: Material, b: Material): number {
    const tagA = a.id_tag_material || '';
    const tagB = b.id_tag_material || '';
    return tagA.localeCompare(tagB, undefined, { numeric: true });
  }

  ngOnInit() {
    this.usuarioService.currentUser$.subscribe(u => this.userRole = u?.rol ?? '');
    this.loadIncidencias();
    this.materialesService.getMateriales('maquina').subscribe(data => this.materiales = data.sort(this.sortByIdTag));
    this.route.paramMap.subscribe(params => {
      const v = params.get('vista');
      if (v === 'activas' || v === 'resueltas') {
        this.vista = v;
      } else {
        this.vista = 'completa';
      }
    });
  }

  loadIncidencias() {
    this.incidenciasService.getIncidencias().subscribe(data => this.lista = data);
  }

  /* incidenciasActivas: filtro para mostrar solo abiertas/en_proceso */
  get incidenciasActivas(): Incidencia[] {
    return this.lista.filter(i => i.estado !== 'resuelta');
  }

  get incidenciasResueltas(): Incidencia[] {
    return this.lista.filter(i => i.estado === 'resuelta');
  }

  getEstadoLabel(valor: string): string {
    if (valor === 'abierta') return 'Averiado';
    if (valor === 'en_proceso') return 'Mantenimiento';
    return 'Resuelta';
  }

  abrirModal() {
    this.newIncidencia = { id_material: 0, descripcion: '', prioridad: 'media' };
    this.error = '';
    this.showModal = true;
  }

  cerrarModal() {
    this.showModal = false;
    this.error = '';
  }

  crearIncidencia() {
    this.error = '';
    if (!this.newIncidencia.id_material || !this.newIncidencia.descripcion) {
      this.error = 'Selecciona un material y escribe una descripción';
      return;
    }
    this.incidenciasService.createIncidencia(this.newIncidencia).subscribe({
      next: () => { this.cerrarModal(); this.loadIncidencias(); },
      error: () => { this.error = 'Error al crear la incidencia'; }
    });
  }

  abrirEditar(inc: Incidencia) {
    this.editIncidencia = inc;
    this.editData = { descripcion: inc.descripcion, prioridad: inc.prioridad, estado: inc.estado };
    this.error = '';
    this.showEditModal = true;
  }

  cerrarEditar() {
    this.showEditModal = false;
    this.editIncidencia = null;
    this.error = '';
  }

  guardarEditar() {
    if (!this.editIncidencia) return;
    this.error = '';
    this.incidenciasService.updateIncidencia(this.editIncidencia.id, this.editData).subscribe({
      next: () => { this.cerrarEditar(); this.loadIncidencias(); },
      error: () => { this.error = 'Error al actualizar la incidencia'; }
    });
  }

  async borrarIncidencia(id: number) {
    const ok = await this.confirmService.confirm('¿Borrar esta incidencia?');
    if (!ok) return;
    this.incidenciasService.deleteIncidencia(id).subscribe({
      next: () => this.loadIncidencias(),
      error: () => this.toastService.show('Error al borrar la incidencia', 'error')
    });
  }

  exportarCSV() {
    const filas = this.incidenciasResueltas.map(inc => [
      inc.id,
      inc.nombre_material,
      inc.ubicacion,
      inc.descripcion,
      inc.prioridad,
      inc.estado,
      inc.created_at ? new Date(inc.created_at).toLocaleDateString() : '',
      inc.fecha_resolucion ? new Date(inc.fecha_resolucion).toLocaleDateString() : ''
    ]);
    const cabeceras = ['ID', 'Máquina', 'Ubicación', 'Descripción', 'Prioridad', 'Estado', 'Inicio', 'Fin'];
    const csv = [cabeceras.join(','), ...filas.map(f => f.map(v => `"${v}"`).join(','))].join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `incidencias_resueltas_${new Date().toISOString().slice(0,10)}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
  }

  exportarPDF() {
    const doc = new jsPDF();
    const filas = this.incidenciasResueltas.map(inc => [
      inc.id.toString(),
      inc.nombre_material || '—',
      inc.ubicacion || '—',
      inc.descripcion,
      inc.prioridad,
      inc.estado,
      inc.created_at ? new Date(inc.created_at).toLocaleDateString() : '',
      inc.fecha_resolucion ? new Date(inc.fecha_resolucion).toLocaleDateString() : ''
    ]);
    autoTable(doc, {
      head: [['ID', 'Máquina', 'Ubicación', 'Descripción', 'Prioridad', 'Estado', 'Inicio', 'Fin']],
      body: filas,
      styles: { fontSize: 8 }
    });
    doc.save(`incidencias_resueltas_${new Date().toISOString().slice(0,10)}.pdf`);
  }
}
