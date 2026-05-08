import { RenderMode, ServerRoute } from '@angular/ssr';

// Configuración de rutas para renderizado del lado del servidor (SSR)
export const serverRoutes: ServerRoute[] = [
  {
    path: '**',                             // Todas las rutas
    renderMode: RenderMode.Prerender        // Modo de renderizado: prerenderizado estático
  }
];
