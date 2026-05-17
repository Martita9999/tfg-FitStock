import { RenderMode, ServerRoute } from '@angular/ssr';

/*
 * serverRoutes: configuración de SSR (Server-Side Rendering).
 * RenderMode.Prerender genera HTML estático para todas las rutas
 * en tiempo de build, mejorando el SEO y la velocidad de carga.
 */
export const serverRoutes: ServerRoute[] = [
  {
    path: '**',
    renderMode: RenderMode.Prerender
  }
];
