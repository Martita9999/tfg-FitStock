# Rol de Node.js en el Proyecto FitStock

## Fecha
Mayo 2026

## Contexto
El proyecto usa Node.js únicamente como **herramienta de construcción** para el frontend Angular. No es necesario en el servidor de producción (Arsys), donde solo se sirven archivos estáticos.

---

## ¿Para qué se usa Node.js en FitStock?

### 1. Gestor de paquetes (npm/pnpm)
Node.js incluye npm. Se usa para descargar y gestionar las dependencias del frontend Angular:
- `@angular/core`, `@angular/forms`, `@angular/router`, etc.
- `rxjs`, `zone.js`, `typescript`, `vitest`, etc.
- Todo queda dentro de `node_modules/` en local

### 2. Compilación de Angular (Angular CLI)
El comando `ng build` (o `pnpm run build`) ejecuta sobre Node.js:
1. **Compilar TypeScript** → JavaScript (con `typescript`)
2. **AOT (Ahead-of-Time)**: compila las plantillas HTML
3. **Empaquetado (bundling)**: junta todo en archivos optimizados
4. **Minificación**: reduce el tamaño de JS/CSS
5. **Tree-shaking**: elimina código no usado

**Salida**: archivos estáticos en `dist/fitStock-front/browser/`:
- `index.html`
- `main-*.js`, `polyfills-*.js`
- `styles-*.css`

### 3. Servidor de desarrollo (`ng serve`)
Para desarrollo local, Node.js levanta un servidor en `localhost:4200` con:
- Recarga en caliente (hot reload)
- Source maps para depuración
- Proxy a la API

### 4. Tests unitarios (`vitest`)
Node.js ejecuta los tests con `vitest` + `jsdom`, que simula un navegador en línea de comandos para probar los componentes Angular.

### 5. SSR (Server-Side Rendering) — Actualmente desactivado
Anteriormente, Angular SSR usaba Express (Node.js) para pre-renderizar páginas en servidor. Se desactivó porque:
- Arsys (plan compartido) **no tiene Node.js**
- La app requiere login → el SEO no es relevante

---

## ¿Por qué NO se necesita Node.js en producción?

| Fase | Node.js | Archivos generados |
|---|---|---|
| Desarrollo | ✅ Necesario (`ng serve`, `ng build`) | Código fuente `.ts`, `.html`, `.css` |
| Build | ✅ Necesario (`ng build`) | Compilado a `.js`, `.css` estáticos |
| Producción (Arsys) | ❌ **No necesario** | Solo `index.html`, `*.js`, `*.css` |

El servidor web de Arsys (Apache) sirve los archivos estáticos directamente. No necesita Node.js porque:
- Angular ya está compilado a JavaScript plano
- El enrutamiento lo gestiona el router PHP + `.htaccess`
- No hay SSR que requiera un servidor Node.js

---

## Diagrama del flujo

```
CÓDIGO FUENTE                     ← TypeScript, HTML, SCSS
       │
       ▼  (ng build → Node.js)
       │
ARCHIVOS COMPILADOS               ← index.html, main.js, styles.css
       │
       ▼  (subida por FTP)
       │
SERVIDOR ARSYS (Apache)           ← Sin Node.js
       │
       ▼
NAVEGADOR DEL USUARIO             ← Ejecuta el JS en cliente
```

---

## Resumen

Node.js es una **herramienta de desarrollo**, no un requisito del servidor. Sin él no podrías compilar ni desarrollar la app, pero el servidor de producción no lo necesita ni lo tiene.
