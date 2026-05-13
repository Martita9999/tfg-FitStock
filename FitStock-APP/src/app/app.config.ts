import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { routes } from './app.routes';
import { provideHttpClient, withFetch } from '@angular/common/http';

// Configuración global de la aplicación Angular
export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),    // Optimización de detección de cambios (reduce número de detecciones)
    provideRouter(routes),                                    // Proveedor de enrutamiento con las rutas definidas
    provideHttpClient(withFetch())                            // Proveedor del cliente HTTP con API fetch para realizar peticiones al backend
  ]
};
