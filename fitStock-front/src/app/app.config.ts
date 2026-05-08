import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { routes } from './app.routes';
import { provideHttpClient, withFetch } from '@angular/common/http';

// Configuración global de la aplicación Angular
export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),    // Optimización de detección de cambios
    provideRouter(routes),                                    // Proveedor de enrutamiento con las rutas definidas
    provideHttpClient(withFetch())                            // Cliente HTTP con API fetch
  ]
};
