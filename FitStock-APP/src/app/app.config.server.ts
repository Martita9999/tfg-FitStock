import { mergeApplicationConfig, ApplicationConfig } from '@angular/core';
import { provideServerRendering, withRoutes } from '@angular/ssr';
import { appConfig } from './app.config';
import { serverRoutes } from './app.routes.server';

// Configuración específica para el servidor (SSR)
const serverConfig: ApplicationConfig = {
  providers: [
    provideServerRendering(withRoutes(serverRoutes))    // Habilita renderizado del lado del servidor
  ]
};

// Combina la configuración general (appConfig) con la del servidor
export const config = mergeApplicationConfig(appConfig, serverConfig);
