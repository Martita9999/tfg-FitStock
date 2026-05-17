import { mergeApplicationConfig, ApplicationConfig } from '@angular/core';
import { provideServerRendering, withRoutes } from '@angular/ssr';
import { appConfig } from './app.config';
import { serverRoutes } from './app.routes.server';

/*
 * serverConfig: configuración específica para SSR.
 * provideServerRendering habilita el renderizado en servidor
 * (Node.js) usando las rutas definidas en serverRoutes.
 * 
 * mergeApplicationConfig fusiona la configuración general
 * (appConfig) con la del servidor para producción.
 */
const serverConfig: ApplicationConfig = {
  providers: [
    provideServerRendering(withRoutes(serverRoutes))
  ]
};

export const config = mergeApplicationConfig(appConfig, serverConfig);
