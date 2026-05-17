# Librerías Externas

## Resumen

Este documento recoge todas las librerías externas utilizadas en la aplicación FitStock-APP, excluyendo las dependencias indirectas.

---

## 1. Dependencias de Producción (Runtime)

### Angular Core y Módulos Oficiales

| Librería | Versión | Propósito |
|---|---|---|
| `@angular/core` | ^21.2.0 | Framework principal: componentes, inyección de dependencias, ciclo de vida |
| `@angular/common` | ^21.2.8 | Directivas comunes (ngIf, ngFor, AsyncPipe, etc.) |
| `@angular/forms` | ^21.2.0 | Formularios reactivos y template-driven (FormsModule, ngModel) |
| `@angular/router` | ^21.2.0 | Enrutamiento cliente (Router, RouterOutlet, ActivatedRoute) |
| `@angular/platform-browser` | ^21.2.0 | Renderizado en navegador (bootstrapApplication) |
| `@angular/platform-server` | ^21.2.0 | Renderizado en servidor (SSR) |
| `@angular/ssr` | ^21.2.7 | Utilidades de Server-Side Rendering |
| `@angular/compiler` | ^21.2.0 | Compilación de templates |

### Terceros (Runtime)

| Librería | Versión | Propósito | Dónde se usa |
|---|---|---|---|
| **jspdf** | ^4.2.1 | Generación de documentos PDF | `incidencia-list.ts`, `prestamo-list.ts` |
| **jspdf-autotable** | ^5.0.7 | Plugin para tablas automáticas en PDF | `incidencia-list.ts`, `prestamo-list.ts` |
| **express** | ^5.1.0 | Servidor HTTP para SSR (Node.js) | `server.ts` |
| **rxjs** | ~7.8.0 | Programación reactiva (Observables, Subjects) | Todos los componentes, servicios |
| **tslib** | ^2.3.0 | Funciones auxiliares de TypeScript | Uso interno del compilador |
| **zone.js** | ^0.16.1 | Zonas de Angular para detección de cambios | `main.ts`, `polyfills.ts` |

---

## 2. Dependencias de Desarrollo

| Librería | Versión | Propósito |
|---|---|---|
| `@angular/build` | ^21.2.7 | Sistema de build de Angular |
| `@angular/cli` | ^21.2.7 | CLI de Angular (ng serve, ng build, etc.) |
| `@angular/compiler-cli` | ^21.2.0 | Compilador AOT de Angular |
| `typescript` | ~5.9.2 | Lenguaje TypeScript |
| `prettier` | ^3.8.1 | Formateador de código |
| `vitest` | ^4.0.8 | Ejecutor de tests unitarios |
| `@vitest/browser-playwright` | ^4.1.6 | Tests en navegador con Playwright |
| `playwright` | ^1.60.0 | Automatización de navegador para tests |
| `jsdom` | ^28.0.0 | Entorno DOM simulado para tests |
| `@types/express` | ^5.0.1 | Tipos TypeScript para Express |
| `@types/jasmine` | ^6.0.0 | Tipos TypeScript para Jasmine |
| `@types/jest` | ^30.0.0 | Tipos TypeScript para Jest |
| `@types/node` | ^20.17.19 | Tipos TypeScript para Node.js |

---

## 3. Recursos Externos (CDN)

| Recurso | URL | Dónde se usa |
|---|---|---|
| **Google Fonts: Inter** | `https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap` | `src/styles.css` (importado vía `@import`) |

La fuente **Inter** en pesos 400, 500, 600, 700 y 800 se usa como tipografía principal de toda la aplicación.

---

## 4. Módulos Propios de Node.js

| Módulo | Propósito | Dónde se usa |
|---|---|---|
| `node:path` | Resolución de rutas de archivos | `server.ts` |

---

## 5. Instalación

Para instalar todas las dependencias:

```bash
npm install
```

Para añadir una nueva librería:

```bash
npm install <libreria>
```

Para instalar una dependencia de desarrollo:

```bash
npm install -D <libreria>
```

---

## 6. Resumen de Librerías Específicas de la Aplicación

Las únicas librerías externas que NO pertenecen al ecosistema Angular y que se usan directamente en el código de la aplicación son:

| Librería | Categoría | Función |
|---|---|---|
| **jspdf** | Utilidad | Generar PDFs con datos de la aplicación |
| **jspdf-autotable** | Utilidad | Dibujar tablas en los PDFs generados |
| **express** | Servidor | Servir la aplicación con SSR |

El resto son parte del ecosistema Angular o herramientas de desarrollo.
