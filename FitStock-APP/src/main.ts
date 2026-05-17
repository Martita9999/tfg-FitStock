/*
 * main.ts: punto de entrada de la aplicación Angular.
 * 
 * bootstrapApplication arranca la app con el componente raíz
 * App y la configuración global de appConfig.
 * zone.js es necesario para la detección de cambios de Angular.
 */
import 'zone.js';
import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { App } from './app/app';

bootstrapApplication(App, appConfig)
  .catch((err) => console.error(err));