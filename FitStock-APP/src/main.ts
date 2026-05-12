// Punto de entrada principal de la aplicación Angular
import 'zone.js';                                           // Zona.js para detección de cambios
import { bootstrapApplication } from '@angular/platform-browser';  // Inicializador de la app
import { appConfig } from './app/app.config';               // Configuración global
import { App } from './app/app';                            // Componente raíz

// Inicia la aplicación con el componente App y la configuración definida
bootstrapApplication(App, appConfig)
  .catch((err) => console.error(err));                      // Captura errores de arranque