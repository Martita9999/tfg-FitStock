import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { routes } from './app.routes';
import { provideHttpClient, withFetch, withInterceptors } from '@angular/common/http';
import { authInterceptor } from './interceptors/auth.interceptor';

/*
 * appConfig: configuración global de Angular.
 * eventCoalescing: agrupa eventos para optimizar detección de cambios.
 * provideRouter: registra las rutas de app.routes.ts.
 * provideHttpClient(withFetch): usa fetch nativo del navegador.
 */
export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),               // Optimiza detección de cambios
    provideRouter(routes),                                               // Registra rutas
    provideHttpClient(withFetch(), withInterceptors([authInterceptor]))                                       // HTTP con fetch() nativo
  ]
};
