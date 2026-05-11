import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';

// Componente raíz de la aplicación Angular
@Component({
  selector: 'app-root',           // Selector usado en index.html
  imports: [RouterOutlet],        // Módulo de enrutamiento para cargar las rutas hijas
  templateUrl: './app.html',      // Plantilla HTML
  styleUrl: './app.css'           // Estilos globales del componente
})
export class App {
  protected readonly title = signal('fitStock-front');   // Título de la app (usado en tests)
}
