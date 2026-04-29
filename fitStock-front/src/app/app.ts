import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';

// Componente raíz de la aplicación Angular (punto de entrada)
@Component({
  selector: 'app-root',
  imports: [RouterOutlet],
  templateUrl: './app.html',
  styleUrl: './app.css'
})
export class App {
  protected readonly title = signal('fitStock-front');
}
