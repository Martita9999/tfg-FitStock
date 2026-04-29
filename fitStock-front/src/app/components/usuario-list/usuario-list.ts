import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AdminSidebarComponent } from '../admin-sidebar/admin-sidebar.component';
import { UsuarioService } from '../../services/usuario';

// Componente para listar usuarios del sistema
@Component({
  selector: 'app-usuario-list',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent],
  templateUrl: './usuario-list.html',
  styleUrl: './usuario-list.css'
})
export class UsuarioListComponent implements OnInit {
  // Lista de usuarios obtenida del backend
  listaUsuarios: any[] = [];

  constructor(private usuarioService: UsuarioService) { }

  // Carga los usuarios al inicializar el componente
  ngOnInit(): void {
    this.usuarioService.getUsuarios().subscribe((data: any) => {
      this.listaUsuarios = Array.isArray(data) ? data : [];
    });
  }
}
