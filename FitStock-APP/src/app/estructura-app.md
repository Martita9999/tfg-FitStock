# Estructura de la Aplicación Angular

```
FitStock-APP/src/app/
├── components/
│   ├── admin-dashboard/       → Layout principal del panel admin (sidebar + header + router-outlet)
│   ├── admin-sidebar/         → Barra lateral con menú dinámico por rol (admin/entrenador/cliente)
│   ├── contacto/              → Formulario de contacto público
│   ├── dashboard-home/        → Dashboard principal con tarjetas (incidencias, gastos, préstamos)
│   ├── incidencia-list/       → CRUD de incidencias con filtro por estado (activas/resueltas)
│   ├── login/                 → Formulario de inicio de sesión
│   ├── material-list/         → CRUD de máquinas y material (operativas, incidencias activas)
│   ├── portal/                → Landing page pública
│   ├── prestamo-list/         → CRUD de préstamos (activos, material disponible, completados, pendientes)
│   ├── producto-list/         → CRUD de productos en stock + carrito de compras
│   ├── registro/              → Formulario de registro público
│   └── usuario-list/          → CRUD de usuarios con filtro por rol
├── interfaces/
│   └── app.interfaces.ts      → Interfaces TypeScript (Usuario, Producto, Prestamo, Incidencia, etc.)
├── services/
│   ├── cart.service.ts        → Servicio del carrito de compras
│   ├── compras.service.ts     → Peticiones HTTP a /api/compras
│   ├── incidencias.service.ts → Peticiones HTTP a /api/incidencias
│   ├── materiales.service.ts  → Peticiones HTTP a /api/materiales
│   ├── prestamos.service.ts   → Peticiones HTTP a /api/prestamos
│   ├── productos.service.ts   → Peticiones HTTP a /api/productos
│   ├── resumen.service.ts     → Peticiones HTTP a /api/resumen
│   ├── toast.service.ts       → Notificaciones toast (feedback visual)
│   └── usuario.ts             → Servicio de autenticación y usuarios (login, registro, logout, sesión)
├── app.config.server.ts       → Configuración para Angular Universal (SSR)
├── app.config.ts              → Configuración global (HttpClient, rutas, etc.)
├── app.css                    → Estilos globales del componente raíz
├── app.html                   → Template raíz con <router-outlet>
├── app.routes.server.ts       → Rutas para SSR
├── app.routes.ts              → Definición de rutas (públicas y protegidas bajo /admin)
└── app.ts                     → Componente raíz (bootstrap)
```

## Estructura de cada componente

Cada componente sigue el mismo patrón de 3 archivos:

```
nombre-componente/
├── nombre-componente.css   → Estilos específicos del componente
├── nombre-componente.html  → Template HTML
└── nombre-componente.ts    → Lógica del componente (clase con @Component)
```

Todos los componentes son **standalone** (no dependen de NgModule).

## Sistema de rutas

```
/                   → Portal (landing pública)
/login              → Login
/registro           → Registro
/contacto           → Contacto
/admin              → Layout protegido con sidebar
  /home             → Dashboard principal
  /inventario       → Productos en stock
  /prestamos/:vista → Préstamos (activos, materiales, completados, pendientes)
  /materiales/:vista→ Máquinas (operativas, incidencias activas)
  /incidencias/:vista→ Incidencias (activas, resueltas)
  /usuarios/:rol    → Usuarios filtrados por rol
```

## Comunicación con la API

- Todos los `services/*.ts` usan `HttpClient` de Angular
- Las peticiones incluyen `withCredentials: true` para enviar cookies de sesión
- La API responde siempre JSON, que los servicios tipan con las interfaces de `app.interfaces.ts`

## Patrón usado

**Vista-Controlador** (View-Controller):

- **Vista** → Angular (componentes, templates HTML, estilos CSS)
- **Controlador** → PHP API (backend)
- Los modelos (interfaces TypeScript y tablas MySQL) son compartidos conceptualmente pero no hay una capa Model separada en el servidor
