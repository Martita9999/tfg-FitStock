import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { UsuarioService, Usuario } from '../../services/usuario';

@Component({
  selector: 'app-admin-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './admin-sidebar.html',
  styleUrl: './admin-sidebar.css'
})
export class AdminSidebarComponent implements OnInit {
  private usuarioService = inject(UsuarioService);
  private router = inject(Router);
  user: Usuario | null = null;

  ngOnInit() {
    this.usuarioService.checkSession();
    this.usuarioService.currentUser$.subscribe(u => this.user = u);
  }

  logout() {
    this.usuarioService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
