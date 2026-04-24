import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-admin-sidebar',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './admin-sidebar.html',
  styleUrl: './admin-sidebar.css'      
})
// 👇 ¡ESTA LÍNEA ES LA CLAVE! Asegúrate de que tenga el 'export'
export class AdminSidebarComponent { 
    // Aquí puedes poner lógica más adelante
}