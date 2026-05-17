# Documentación del Frontend - FitStock (Angular)

## Índice

1. [Introducción a Angular en FitStock](#1-introducción-a-angular-en-fitstock)
2. [Arquitectura de la Aplicación](#2-arquitectura-de-la-aplicación)
3. [Estructura de Directorios](#3-estructura-de-directorios)
4. [Componentes Principales](#4-componentes-principales)
5. [Servicios](#5-servicios)
6. [Interfaces y Modelos](#6-interfaces-y-modelos)
7. [Enrutamiento](#7-enrutamiento)
8. [Librerías Externas y Módulos](#8-librerías-externas-y-módulos)
9. [Estilos y Temas](#9-estilos-y-temas)
10. [Guía de Capturas de Código](#10-guía-de-capturas-de-código-imágenes-en-la-aplicación)
11. [Funcionalidades Clave](#11-funcionalidades-clave)

---

## 1. Introducción a Angular en FitStock

### 1.1 Definición del concepto

**Angular** es un framework de desarrollo web creado por Google, diseñado para construir aplicaciones de página única (SPA) robustas y escalables. En FitStock, Angular actúa como la capa de **frontend (interfaz de usuario)**, encargada de:
- Renderizar las pantallas que ve el usuario
- Gestionar la navegación entre páginas
- Comunicarse con el backend (Laravel) a través de peticiones HTTP
- Mantener el estado de la sesión del usuario
- Validar formularios y mostrar datos en tiempo real

### 1.2 Stack tecnológico del proyecto

FitStock está desarrollado con **Angular 21** utilizando el modelo de **componentes standalone** (independientes). Esto significa que cada componente declara sus propias importaciones en lugar de depender de un módulo compartido (`NgModule`). La aplicación sigue una arquitectura modular por funcionalidades, con un frontend que se comunica con una API REST backend en Laravel.

**Versiones principales:**
- Angular: ^21.2.0
- TypeScript: ~5.9.2
- RxJS: ~7.8.0
- Node.js: ^24.15.0

### 1.3 Características clave de Angular utilizadas en FitStock

1. **Componentes standalone** → Cada componente es autosuficiente, importa solo lo que necesita. Esto simplifica la estructura y evita módulos compartidos.
2. **RouterOutlet** → Permite la navegación jerárquica: el dashboard tiene un layout padre con sidebar y las subpáginas se renderizan dentro de un `<router-outlet>`.
3. **FormsModule + ngModel** → Formularios template-driven: se enlazan directamente propiedades del componente con inputs del HTML mediante `[(ngModel)]`.
4. **Inject** → Inyección de dependencias funcional: se obtienen servicios llamando `inject(Servicio)` sin necesidad de constructor.
5. **Server-Side Rendering (SSR)** → Angular Universal renderiza las páginas en el servidor antes de enviarlas al cliente, mejorando el SEO y la velocidad de carga inicial.
6. **HttpClient** → Módulo oficial para hacer peticiones HTTP (GET, POST, PUT, DELETE) al backend Laravel.
7. **BehaviorSubject** → Patrón reactivo para compartir el estado del usuario logueado entre todos los componentes de la aplicación.
8. **Lazy loading** → Las rutas hijas del dashboard solo se cargan cuando el usuario accede a ellas.

---

## 2. Arquitectura de la Aplicación

### 2.1 Definición del concepto

La **arquitectura** de una aplicación Angular define cómo se organizan sus componentes, servicios y rutas. En FitStock, la arquitectura sigue un patrón de **dos zonas bien diferenciadas**: una zona pública (sin autenticación) y una zona privada (con panel de administración). Esta separación permite controlar qué puede ver cada usuario según su rol.

### 2.2 Estructura general

La aplicación se divide en dos grandes zonas:

```
PÚBLICA (acceso sin autenticación)
├── Landing Page (PortalComponent)        → Página principal de presentación
├── Login (LoginComponent)                → Inicio de sesión
├── Registro (RegistroComponent)          → Registro de nuevos usuarios
└── Contacto (ContactoComponent)          → Formulario de contacto

PRIVADA (requiere autenticación - /admin/*)
├── AdminDashboardComponent               → Layout principal con sidebar y header
│   ├── DashboardHomeComponent            → Página de inicio con resumen y estadísticas
│   ├── ProductoList                      → CRUD de productos con carrito de compras
│   ├── PrestamoList                      → Gestión de préstamos de material
│   ├── IncidenciaList                    → Gestión de incidencias de máquinas
│   ├── MaterialList                      → Gestión de máquinas y equipamiento
│   └── UsuarioList                       → CRUD de usuarios con filtro por rol
```

### 2.3 Flujo de autenticación (paso a paso)

1. El usuario introduce email y contraseña en `LoginComponent` y pulsa "Iniciar Sesión"
2. El componente llama a `UsuarioService.login(email, password)` que hace un POST a `/api/login`
3. Si el servidor valida las credenciales, devuelve un objeto `Usuario` con sus datos (id, nombre, email, rol)
4. `UsuarioService` guarda ese objeto en `localStorage` para mantener la sesión aunque se recargue la página
5. También actualiza un `BehaviorSubject<Usuario | null>` para que cualquier componente pueda suscribirse y saber quién es el usuario actual en tiempo real
6. Si las credenciales son incorrectas, se muestra un mensaje de error en el formulario
7. No se usan guards de ruta; la autorización se maneja manualmente comprobando `userRole` en cada componente

**Concepto importante:** No hay guards de ruta porque cada componente verifica el rol del usuario por sí mismo y muestra u oculta elementos según corresponda. Por ejemplo, un cliente no ve el botón "Crear Producto".

---

## 3. Estructura de Directorios

```
FitStock-APP/src/app/
│
├── app.ts                          # Componente raíz
├── app.html                        # Template raíz (<router-outlet>)
├── app.css                         # Estilos raíz
├── app.config.ts                   # Configuración de providers
├── app.routes.ts                   # Definiciones de rutas
│
├── interfaces/
│   └── app.interfaces.ts           # Interfaces compartidas de TypeScript
│
├── services/
│   ├── usuario.ts                  # Autenticación y CRUD de usuarios
│   ├── productos.service.ts        # Gestión de productos/stock
│   ├── prestamos.service.ts        # Gestión de préstamos
│   ├── materiales.service.ts       # Gestión de equipamiento
│   ├── incidencias.service.ts      # Gestión de incidencias
│   ├── compras.service.ts          # Registro de compras
│   ├── cart.service.ts             # Carrito de compras en memoria
│   ├── toast.service.ts            # Notificaciones toast
│   └── resumen.service.ts          # Datos de resumen del dashboard
│
└── components/
    ├── portal/                     # Landing page pública
    ├── login/                      # Inicio de sesión
    ├── registro/                   # Registro de usuarios
    ├── contacto/                   # Formulario de contacto
    ├── admin-dashboard/            # Layout del panel admin
    ├── admin-sidebar/              # Barra de navegación lateral
    ├── dashboard-home/             # Página de inicio del dashboard
    ├── producto-list/              # Gestión de productos
    ├── prestamo-list/              # Gestión de préstamos
    ├── incidencia-list/            # Gestión de incidencias
    ├── material-list/              # Gestión de máquinas
    └── usuario-list/               # Gestión de usuarios
```

**Total: 45 archivos** (18 TypeScript, 13 HTML, 13 CSS, 1 interfaces)

---

## 4. Componentes Principales

### 4.1 PortalComponent (Landing Page)

**Ubicación:** `components/portal/portal.ts`

Componente público que muestra la página de aterrizaje de FitStock.

```typescript
@Component({
  selector: 'app-portal',
  standalone: true,
  imports: [ContactoComponent],
  templateUrl: './portal.html',
  styleUrl: './portal.css',
})
export class PortalComponent {
  constructor(private router: Router) {}

  irALogin() {
    this.router.navigate(['/login']);
  }
}
```

**Secciones del template:**
- **Hero section**: Título "FitStock" con degradado, botón "Acceder al Panel", sección "Quiénes Somos"
- **6 bloques de funcionalidad**: Productos, Gestión de Máquinas, Préstamos, Incidencias, Usuarios y Roles, Dashboard
- **Contacto**: Formulario de contacto embebido
- **Footer**: Copyright

Cada bloque de funcionalidad contiene:
- Icono SVG inline representativo
- Título de la funcionalidad
- Descripción detallada

### 4.2 LoginComponent

**Ubicación:** `components/login/login.ts`

Maneja la autenticación de usuarios.

```typescript
@Component({
  selector: 'app-login',
  imports: [FormsModule, CommonModule],
  templateUrl: './login.html',
  styleUrl: './login.css'
})
export class LoginComponent {
  email = '';
  password = '';
  error = '';
  darkMode = false;
  showPasswordModal = false;

  login() { /* autentica al usuario */ }
  cambiarPassword() { /* cambio forzado de password */ }
  goToRegistro() { /* navega a /registro */ }
  goToPortal() { /* navega a / */ }
}
```

**Funcionalidades clave:**
- Inicio de sesión con email y contraseña
- Modal de cambio forzado de contraseña (`forzar_cambio_password = 1`)
- Toggle de modo oscuro/claro
- Enlaces a registro y portal web
- Recuperación de sesión desde localStorage

### 4.3 AdminDashboardComponent

**Ubicación:** `components/admin-dashboard/admin-dashboard.ts`

Layout principal del panel de administración que envuelve todas las vistas internas.

```typescript
@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, AdminSidebarComponent, RouterOutlet],
  templateUrl: './admin-dashboard.html',
  styleUrl: './admin-dashboard.css'
})
export class AdminDashboardComponent implements OnInit {
  user: Usuario | null = null;
  sidebarCollapsed = false;
  darkMode = false;
  showCart = false;

  toggleSidebar() { }
  toggleDarkMode() { }
  getRoleLabel(): string { }
  logout() { }
  toggleCart() { }
  async comprar() { }
}
```

**Secciones del template:**
- **Sidebar** (`<app-admin-sidebar>`): Navegación colapsable
- **Header**: Logo, carrito de compras, toggle dark mode, botón de logout
- **Contenido** (`<router-outlet>`): Renderiza las subvistas
- **Footer**: Marca, tagline y email de contacto
- **Toast container**: Notificaciones flotantes

### 4.4 DashboardHomeComponent

**Ubicación:** `components/dashboard-home/dashboard-home.ts`

Página de inicio del dashboard con vistas diferenciadas por rol.

**Vista para Admin/Entrenador:**
- Tarjeta de bienvenida con nombre de usuario y rol
- Grid de 6 tarjetas de resumen: Pendientes, Stock Bajo, Máquinas, Gasto Total, Préstamos Pendientes, Usuarios
- Cada tarjeta tiene un color distintivo y cuenta con enlace a la sección correspondiente

**Vista para Cliente:**
- Tarjeta de bienvenida
- Sección "Mis Datos" con formulario de cambio de contraseña
- Máquinas (operativas + averiadas/en reparación)
- Mis Préstamos activos
- Productos comprados

### 4.5 ProductoList

**Ubicación:** `components/producto-list/producto-list.ts`

CRUD completo de productos/stock con integración de carrito de compras.

```typescript
export class ProductoList implements OnInit {
  lista: ProductoStock[] = [];
  userRole = '';
  showModal = false;
  newProducto = { nombre: '', descripcion: '', cantidad: 0, stock_minimo: 0, precio: 0 };
  selectedFile: File | null = null;

  loadProductos() { }
  agregarAlCarrito(p) { }
  async crearProducto() { }
  onFileSelected(event) { }
  guardarEditar() { }
  borrarProducto(id, nombre) { }
}
```

**Funcionalidades:**
- Grid de productos con imágenes, precios e indicadores de stock
- Botón "Agregar al carrito" para clientes
- Modal de creación con subida de imagen
- Modal de edición
- Eliminación con confirmación
- Manejo de errores de carga de imágenes con reintento

### 4.6 PrestamoList

**Ubicación:** `components/prestamo-list/prestamo-list.ts`

Sistema completo de préstamos con selección por agrupación.

```typescript
export class PrestamoList implements OnInit {
  materiales: Material[] = [];
  prestamos: Prestamo[] = [];
  userRole = '';
  selecciones: { [nombre: string]: number } = {};

  get grupos(): MaterialGroup[] { }
  incrementar(g) { }
  decrementar(g) { }
  prestarSeleccion() { }
  devolverPrestamo(id) { }
  borrarPrestamo(id) { }
}
```

**Funcionalidades:**
- Visualización de préstamos activos (con usuario, fechas, estado)
- Materiales agrupados por nombre con contadores de disponibilidad
- Selectores de cantidad (+/-) con límite de stock disponible
- Barra inferior fija con resumen de selección
- Modal de selección de usuario (admin) para asignar préstamo
- Modal de edición de fecha de devolución
- Modal de edición de grupo de materiales
- Creación de nuevas unidades de material

### 4.7 IncidenciaList

**Ubicación:** `components/incidencia-list/incidencia-list.ts`

Gestión de incidencias con clasificación por estado y prioridad.

```typescript
export class IncidenciaList implements OnInit {
  lista: Incidencia[] = [];
  materiales: Material[] = [];
  userRole = '';

  get incidenciasActivas(): Incidencia[] { }
  get incidenciasResueltas(): Incidencia[] { }
  getEstadoLabel(valor): string { }
  crearIncidencia() { }
  guardarEditar() { }
  borrarIncidencia(id) { }
}
```

**Funcionalidades:**
- Tabla de incidencias activas (Abierta/En Proceso)
- Tabla de incidencias resueltas
- Badges de prioridad (alta, media, baja) y estado con colores
- Modal de creación con selector de máquina
- Modal de edición con cambio de estado y prioridad
- Vinculación con máquinas a través de `id_material`

### 4.8 MaterialList

**Ubicación:** `components/material-list/material-list.ts`

Gestión de máquinas/equipamiento del gimnasio.

```typescript
export class MaterialList implements OnInit {
  lista: Material[] = [];
  incidencias: Incidencia[] = [];
  userRole = '';

  get maquinasOperativas(): Material[] { }
  get maquinasNoOperativas(): Material[] { }
  getIncidenciaActiva(idMaterial): Incidencia | undefined { }
  crearMaterial() { }
  guardarEditar() { }
}
```

**Funcionalidades:**
- Vista de máquinas no operativas con incidencia activa asociada
- Vista de máquinas operativas
- Estados disponibles: operativo, averiado, mantenimiento, en_proceso, saliendo, en_reparacion, baja
- Modal de creación y edición con campos de nombre, ubicación, ID tag y descripción
- Actualización automática de `ultima_rev` al editar

### 4.9 UsuarioList

**Ubicación:** `components/usuario-list/usuario-list.ts`

Gestión de usuarios con filtrado por rol.

```typescript
export class UsuarioList implements OnInit {
  lista: Usuario[] = [];
  listaFiltrada: Usuario[] = [];
  filtroRol = '';

  get tituloPagina(): string { }
  loadUsuarios() { }
  crearUsuario() { }
  guardarEditar() { }
  forzarCambioPassword(u) { }
  borrarUsuario(id, nombre) { }
}
```

**Funcionalidades:**
- Título dinámico según el rol (Administradores, Entrenadores, Clientes, Usuarios)
- Filtrado por rol mediante parámetro de ruta (`/admin/usuarios/:rol`)
- Tabla con columnas: ID, Nombre, Email, Rol, Acciones
- Badges de rol con colores distintivos (admin, entrenador, cliente)
- Modal de creación y edición
- Botón para forzar cambio de contraseña
- Badge "Pendiente" para usuarios con `forzar_cambio_password = 1`
- Confirmación antes de eliminar

### 4.10 ContactoComponent

**Ubicación:** `components/contacto/contacto.ts`

Formulario de contacto embebible.

```typescript
@Component({
  selector: 'app-contacto',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './contacto.html',
  styleUrl: './contacto.css',
})
export class ContactoComponent {
  @Input() embed = false;
  email = '';
  mensaje = '';
  error = '';
  success = false;
  sending = false;

  irAPortal() { }
  enviar() { }
}
```

**Inputs:**
- `embed`: booleano que oculta el fondo y el enlace de vuelta al portal cuando está embebido

### 4.11 AdminSidebarComponent

**Ubicación:** `components/admin-sidebar/admin-sidebar.ts`

Barra de navegación lateral con menús adaptados por rol.

```typescript
@Component({
  selector: 'app-admin-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './admin-sidebar.html',
  styleUrl: './admin-sidebar.css'
})
export class AdminSidebarComponent implements OnInit {
  @Input() collapsed = false;
  @Output() toggleSidebar = new EventEmitter<void>();
  @Output() closeSidebar = new EventEmitter<void>();
  usuariosSubmenuOpen = false;

  toggleDarkMode() { }
  toggleUsuariosSubmenu() { }
  logout() { }
}
```

**Menú por rol:**

| Opción | Admin | Entrenador | Cliente |
|--------|-------|------------|---------|
| Dashboard | ✓ | ✓ | ✓ |
| Productos | ✓ | ✓ | ✓ |
| Préstamos | ✓ | ✓ | ✓ |
| Incidencias | ✓ | ✓ | ✓ |
| Máquinas | ✓ | ✓ | ✓ |
| Usuarios > Admins | ✓ | ✗ | ✗ |
| Usuarios > Entrenadores | ✓ | ✗ | ✗ |
| Usuarios > Clientes | ✓ | ✓ | ✗ |

---

## 5. Servicios

### 5.1 UsuarioService (`services/usuario.ts`)

**Propósito:** Autenticación, gestión de sesión y CRUD de usuarios.

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `login(email, password)` | POST /api/login | Autenticación de usuarios |
| `registro(nombre, email, password)` | POST /api/registro | Registro de nuevos usuarios |
| `logout()` | POST /api/logout | Cierre de sesión |
| `getUsuarios()` | GET /api/usuarios | Listar todos los usuarios |
| `createUsuario(data)` | POST /api/usuarios | Crear usuario |
| `updateUsuario(id, data)` | PUT /api/usuarios/:id | Actualizar usuario |
| `deleteUsuario(id)` | DELETE /api/usuarios/:id | Eliminar usuario |
| `cambiarPassword(old, new)` | PUT /api/usuarios/cambiar-password | Cambiar contraseña |
| `forzarCambioPassword(id_usuario)` | POST /api/usuarios/forzar-cambio | Forzar cambio de contraseña |
| `checkSession()` | - | Recupera sesión desde localStorage |

**Estado compartido:** `currentUser$: BehaviorSubject<Usuario | null>`

### 5.2 ProductosService (`services/productos.service.ts`)

**Propósito:** Gestión de productos/stock.

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `getProductos()` | GET /api/productos | Listar productos |
| `createProducto(data)` | POST /api/productos | Crear producto |
| `updateProducto(id, data)` | PUT /api/productos/:id | Actualizar producto |
| `deleteProducto(id)` | DELETE /api/productos/:id | Eliminar producto |
| `subirImagen(nombre, file)` | POST /api/productos/subir-imagen | Subir imagen (FormData) |

### 5.3 PrestamosService (`services/prestamos.service.ts`)

**Propósito:** Gestión de préstamos de material.

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `getPrestamos()` | GET /api/prestamos | Listar préstamos |
| `createPrestamo(data)` | POST /api/prestamos | Crear préstamo |
| `updatePrestamo(id, data)` | PUT /api/prestamos/:id | Actualizar préstamo |
| `devolverPrestamo(id)` | PUT /api/prestamos/:id (body vacío) | Marcar como devuelto |
| `deletePrestamo(id)` | DELETE /api/prestamos/:id | Eliminar préstamo |

### 5.4 MaterialesService (`services/materiales.service.ts`)

**Propósito:** Gestión de máquinas y equipamiento.

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `getMateriales(tipo?)` | GET /api/materiales?tipo= | Listar materiales |
| `createMaterial(data)` | POST /api/materiales | Crear material |
| `updateMaterial(id, data)` | PUT /api/materiales/:id | Actualizar material |
| `deleteMaterial(id)` | DELETE /api/materiales/:id | Eliminar material |

### 5.5 IncidenciasService (`services/incidencias.service.ts`)

**Propósito:** Gestión de incidencias de máquinas.

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `getIncidencias()` | GET /api/incidencias | Listar incidencias |
| `createIncidencia(data)` | POST /api/incidencias | Crear incidencia |
| `updateIncidencia(id, data)` | PUT /api/incidencias/:id | Actualizar incidencia |
| `deleteIncidencia(id)` | DELETE /api/incidencias/:id | Eliminar incidencia |

### 5.6 ComprasService (`services/compras.service.ts`)

**Propósito:** Registro de compras de productos.

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `getCompras(id_usuario?)` | GET /api/compras?id_usuario= | Listar compras |
| `createCompra(data)` | POST /api/compras | Crear compra |

### 5.7 CartService (`services/cart.service.ts`)

**Propósito:** Carrito de compras en memoria.

| Propiedad/Método | Descripción |
|------------------|-------------|
| `carrito: CartItem[]` | Array de items en el carrito |
| `totalCarrito` (getter) | Suma total del carrito |
| `totalItems` (getter) | Cantidad total de unidades |
| `agregar(producto)` | Agrega 1 unidad (respeta stock) |
| `quitar(item)` | Quita 1 unidad o elimina si es la última |
| `eliminar(item)` | Elimina el item completamente |
| `limpiar()` | Vacía el carrito |
| `comprar()` | Valida stock, registra compra, limpia carrito |

### 5.8 ToastService (`services/toast.service.ts`)

**Propósito:** Notificaciones toast dinámicas.

| Método | Descripción |
|--------|-------------|
| `show(message, type, duration?)` | Muestra toast con auto-destrucción |
| `success(message)` | Atajo para toast de éxito |
| `error(message)` | Atajo para toast de error |
| `info(message)` | Atajo para toast informativo |

### 5.9 ResumenService (`services/resumen.service.ts`)

**Propósito:** Obtener datos de resumen para el dashboard.

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `obtenerResumen()` | GET /api/resumen | Resumen de incidencias, stock, máquinas, gastos |

---

## 6. Interfaces y Modelos

### `interfaces/app.interfaces.ts`

```typescript
export interface Usuario {
  id: number;
  nombre: string;
  email: string;
  rol: string;              // 'admin' | 'entrenador' | 'cliente'
  forzar_cambio_password?: number;
}

export interface Material {
  id: number;
  nombre: string;
  descripcion?: string;
  ubicacion?: string;
  estado: string;           // 'operativo' | 'averiado' | 'mantenimiento' | ...
  tipo: string;             // 'prestable' | 'maquina'
  id_tag_material?: string;
  ultima_rev?: string | null;
}

export interface Prestamo {
  id: number;
  id_usuario?: number;
  id_material?: number;
  usuario: string;          // nombre del usuario
  material: string;         // nombre del material
  fecha: string;
  devolucion: string | null;
}

export interface ProductoStock {
  id: number;
  nombre: string;
  descripcion?: string;
  cantidad: number;
  stock_minimo: number;
  precio: number;
}

export interface Compra {
  id: number;
  id_usuario: number;
  id_producto: number;
  nombre_producto: string;
  cantidad: number;
  precio_unitario: number;
  total: number;
  fecha_compra: string;
}

export interface Incidencia {
  id: number;
  id_material?: number;
  id_user_rep?: number;
  descripcion: string;
  prioridad: string;       // 'alta' | 'media' | 'baja'
  estado: string;          // 'abierta' | 'en_proceso' | 'resuelta'
  created_at?: string | null;
  fecha_resolucion?: string | null;
  nombre_material?: string;
  id_tag_material?: string;
  ubicacion?: string;
}
```

### Interfaces internas de servicios:

| Interface | Archivo | Campos |
|-----------|---------|--------|
| `LoginResponse` | `usuario.ts` | `success, user?, error?` |
| `CartItem` | `cart.service.ts` | `producto: ProductoStock, cantidad: number` |
| `Toast` | `toast.service.ts` | `message: string, type: 'success' \| 'error' \| 'info'` |
| `ResumenData` | `resumen.service.ts` | `incidencias, stock_bajo, maquinas, gastos` |
| `MaterialGroup` | `prestamo-list.ts` | `nombre, descripcion, total, disponibles, seleccionados, idsDisponibles` |

---

## 7. Enrutamiento

### `app.routes.ts`

```typescript
export const routes: Routes = [
  { path: '', component: PortalComponent },                          // Landing page
  { path: 'login', component: LoginComponent },                      // Inicio de sesión
  { path: 'registro', component: RegistroComponent },                // Registro
  { path: 'contacto', component: ContactoComponent },                // Contacto
  {
    path: 'admin',
    component: AdminDashboardComponent,                               // Layout del dashboard
    children: [
      { path: 'home', component: DashboardHomeComponent },            // Resumen
      { path: 'inventario', component: ProductoList },                // Productos
      { path: 'prestamos', component: PrestamoList },                 // Préstamos
      { path: 'incidencias', component: IncidenciaList },             // Incidencias
      { path: 'materiales', component: MaterialList },                // Máquinas
      { path: 'usuarios/:rol', component: UsuarioList },              // Usuarios por rol
      { path: 'usuarios', component: UsuarioList },                   // Todos los usuarios
      { path: '', redirectTo: 'home', pathMatch: 'full' },            // Redirección por defecto
    ],
  },
];
```

### `app.routes.server.ts`

Configuración para Server-Side Rendering:
```typescript
export const serverRoutes: ServerRoute[] = [
  { path: '**', renderMode: RenderMode.Prerender },
];
```

---

## 8. Librerías Externas y Módulos

### Dependencias de producción

| Librería | Versión | Propósito |
|----------|---------|-----------|
| `@angular/core` | ^21.2.0 | Framework principal |
| `@angular/common` | ^21.2.0 | Directivas y pipes comunes |
| `@angular/forms` | ^21.2.0 | Formularios template-driven (ngModel) |
| `@angular/router` | ^21.2.0 | Enrutamiento y navegación |
| `@angular/platform-browser` | ^21.2.0 | Renderizado en navegador |
| `@angular/platform-server` | ^21.2.0 | Renderizado en servidor (SSR) |
| `@angular/ssr` | ^21.2.7 | Utilidades SSR |
| `@angular/build` | ^21.2.7 | Build system con Vite/esbuild |
| `rxjs` | ~7.8.0 | Programación reactiva (BehaviorSubject, Observables) |
| `zone.js` | ^0.16.1 | Zonas de Angular para detección de cambios |
| `tslib` | ^2.3.0 | Helpers de TypeScript |
| `express` | ^5.1.0 | Servidor web para SSR |

### Dependencias de desarrollo

| Librería | Versión | Propósito |
|----------|---------|-----------|
| `typescript` | ~5.9.2 | Lenguaje de programación |
| `@angular/cli` | ^21.2.7 | Herramientas de línea de comandos |
| `@angular/compiler-cli` | ^21.2.0 | Compilación AOT |
| `vitest` | ^4.0.8 | Framework de testing unitario |
| `prettier` | ^3.8.1 | Formateo de código |
| `jsdom` | - | Entorno DOM para tests |
| `playwright` | - | Testing E2E |
| `@types/express` | - | Tipados para Express |
| `@types/jasmine` | - | Tipados para Jasmine |
| `@types/jest` | - | Tipados para Jest |

### APIs del Navegador Utilizadas

- **LocalStorage**: Persistencia de sesión y preferencia de tema oscuro
- **FormData**: Subida de imágenes de productos
- **Fetch/XMLHttpRequest**: A través de `HttpClient` de Angular

---

## 9. Estilos y Temas

### Sistema de temas (modo oscuro/claro)

Definido en `src/styles.css` mediante variables CSS:

```css
:root {
  --bg: #0f172a;              /* Fondo oscuro */
  --card-bg: #1e293b;         /* Fondo de tarjetas */
  --text: #f1f5f9;            /* Texto principal */
  --text-muted: #94a3b8;      /* Texto secundario */
  --border: #334155;          /* Bordes */
  --orange: #f97316;          /* Naranja primario */
  --orange-dark: #ea580c;     /* Naranja oscuro */
  --blue: #3b82f6;            /* Azul primario */
  --hover: #334155;           /* Hover de elementos */
}

[data-theme="light"] {
  --bg: #f8fafc;              /* Fondo claro */
  --card-bg: #ffffff;         /* Tarjetas claras */
  --text: #0f172a;            /* Texto oscuro */
  --text-muted: #64748b;      /* Texto secundario */
  --border: #e2e8f0;          /* Bordes claros */
  --hover: #f1f5f9;           /* Hover claro */
}
```

### Componentes de UI compartidos (globales en styles.css)

- **Botones**: `.btn-edit` (azul), `.btn-create` (verde), `.btn-delete` (rojo), `.btn-cancel` (gris), `.btn-save` (naranja)
- **Modales**: `.modal-overlay`, `.modal-card`, `.modal-header`, `.modal-body`, `.modal-footer`
- **Formularios**: `.field` (con `label + input/select`), `.field-row` (layout horizontal)
- **Tablas**: `.data-table` con estilo consistente
- **Estados vacíos**: `.empty-state` con icono y mensaje
- **Animaciones**: `btnPulse` (pulso en botones de guardar), `fadeIn` (transiciones)

---

## 10. Guía de Capturas de Código (imágenes en la aplicación)

Esta sección indica exactamente qué líneas del código debes capturar (screenshot) para documentar visualmente cómo se referencian las imágenes en la aplicación. No se incluyen imágenes recreadas, solo la ubicación precisa del código original.

### 10.1 Definición del concepto

En Angular, las imágenes pueden referenciarse de tres formas:
- **CSS:** mediante `url('/ruta/imagen.jpg')` en archivos `.css` (fondos decorativos)
- **HTML:** mediante `<img src="...">` en archivos `.html` (imágenes visibles en la página)
- **TypeScript:** mediante métodos que construyen dinámicamente la ruta de la imagen

Cada una tiene una utilidad distinta y se captura de una parte diferente del código.

---

### 10.2 Captura 1: Fondo del Hero Section (Landing Page)

**Qué capturar:** La línea del CSS que asigna la imagen de fondo principal de la landing page.

| Archivo | Línea | Código a capturar |
|---------|-------|-------------------|
| `portal.css` | 21 | `url('/images/portal-web/portal.jpg')` |

**Cómo encontrarlo:** Abrir `FitStock-APP/src/app/components/portal/portal.css` y hacer scroll hasta la línea 21. Capturar toda la regla `.hero-section` (líneas 10-25) para ver el contexto completo del degradado + imagen.

**Explicación:** El `hero-section` es la primera pantalla que ve el usuario al entrar a la web. La imagen `portal.jpg` se muestra como fondo con un degradado oscuro encima para que el texto blanco sea legible.

---

### 10.3 Captura 2: Fondos de cada bloque funcional (Landing Page)

**Qué capturar:** Las reglas CSS que asignan una imagen de fondo distinta a cada bloque de funcionalidad.

| Archivo | Líneas | Bloque |
|---------|--------|--------|
| `portal.css` | 107-113 | Productos → `Productos.png` |
| `portal.css` | 115-121 | Máquinas → `Cinta de correr.jpg` |
| `portal.css` | 125-131 | Préstamos → `mancuerda.jpg` |
| `portal.css` | 134-140 | Incidencias → `incidencias.jpg` |
| `portal.css` | 143-149 | Usuarios → `usuarios y roles.jpg` |
| `portal.css` | 152-158 | Dashboard → `dashboard.jpg` |

**Cómo capturarlo:** En `portal.css`, hacer scroll desde la línea 106 hasta la 158. Capturar el bloque completo que va desde `/* ─── BLOQUES CON FONDO DE IMAGEN ─── */` hasta la última regla `.feature-block-dashboard`.

**Explicación:** Cada bloque de funcionalidad tiene una imagen de fondo única que representa visualmente el contenido (ej: una cinta de correr para "Gestión de Máquinas"). El degradado de color que las acompaña cambia según el bloque (naranja para productos, azul para máquinas, rojo para incidencias, etc.).

---

### 10.4 Captura 3: Imagen de productos en el grid

**Qué capturar:** Las tres partes del código que hacen que las imágenes de productos se muestren en pantalla.

| Captura | Archivo | Línea | Qué hace |
|---------|---------|-------|----------|
| 3a | `producto-list.html` | 25-26 | Etiqueta `<img>` que muestra la imagen del producto en la tarjeta |
| 3b | `producto-list.ts` | 157-158 | Método `getImagenUrl()` que construye la ruta de la imagen |
| 3c | `producto-list.ts` | 162-170 | Método `onImgError()` que reintenta si la imagen no carga |

**Cómo capturarlo:**
- **3a:** Abrir `producto-list.html`, capturar las líneas 22-30 (desde `<div class="productos-grid">` hasta el cierre de la tarjeta)
- **3b:** Abrir `producto-list.ts`, capturar las líneas 156-159
- **3c:** En el mismo archivo, capturar las líneas 161-170

**Explicación:** El método `getImagenUrl()` toma el nombre del producto y construye la ruta absoluta a la carpeta `/images/productos/`. La plantilla HTML usa `[src]="getImagenUrl(p.nombre)"` para enlazar esa ruta al atributo `src` de la etiqueta `<img>`. Si la imagen falla (ej: aún se está subiendo al servidor), el método `onImgError()` espera 2 segundos y reintenta, y si vuelve a fallar, oculta la imagen.

---

### 10.5 Captura 4: Subida de imágenes (modal de crear producto)

**Qué capturar:** El flujo completo de subida de una imagen para un producto nuevo.

| Captura | Archivo | Línea | Qué hace |
|---------|---------|-------|----------|
| 4a | `producto-list.html` | 93-99 | Input file + previsualización en el modal |
| 4b | `producto-list.ts` | 28-29 | Propiedades `selectedFile` y `previewUrl` |
| 4c | `producto-list.ts` | 103-113 | Método `onFileSelected()` que maneja la selección |
| 4d | `producto-list.ts` | 92-93 | Llamada a `subirImagen()` al crear el producto |
| 4e | `productos.service.ts` | 34-41 | Servicio que envía la imagen al backend |

**Cómo capturarlo:**
- **4a:** En `producto-list.html`, capturar las líneas 93-100
- **4b-c:** En `producto-list.ts`, capturar las líneas 26-30 y 103-113
- **4d:** En el mismo archivo, capturar las líneas 88-93
- **4e:** Abrir `productos.service.ts`, capturar las líneas 33-41

**Explicación del flujo completo:**
1. El usuario pulsa "Crear Producto" → se abre un modal con un formulario
2. En el campo "Imagen", el usuario selecciona un archivo `.jpg` o `.png` de su ordenador
3. `onFileSelected()` guarda el archivo en `selectedFile` y genera una URL de previsualización con `URL.createObjectURL()` para que el usuario vea la imagen antes de guardar
4. Al pulsar "Guardar", se crea el producto y, si hay archivo seleccionado, se llama a `subirImagen()`
5. `subirImagen()` construye un `FormData` con el nombre del producto y el archivo, y lo envía via POST a `/api/productos/subir-imagen`

---

### 10.6 Captura 5: Icono de la aplicación (favicon y logo)

**Qué capturar:** Las referencias al icono de la aplicación, usado como favicon en el navegador y como logo en el dashboard.

| Captura | Archivo | Línea | Qué hace |
|---------|---------|-------|----------|
| 5a | `index.html` | 8-9 | Favicon del navegador |
| 5b | `admin-dashboard.html` | 13-14 | Logo en el header del dashboard |

**Cómo capturarlo:**
- **5a:** Abrir `src/index.html`, capturar las líneas 8-9
- **5b:** Abrir `admin-dashboard.html`, capturar las líneas 12-15

**Explicación:** `icono.jpg` es la imagen del logo de FitStock. Se referencia de dos formas: como favicon (el icono que aparece en la pestaña del navegador) y como logo (la imagen que aparece en la esquina superior izquierda del panel de administración).

---

### 10.7 Captura 6: Fondo del formulario de contacto

**Qué capturar:** La regla CSS que asigna la imagen de fondo a la página de contacto.

| Archivo | Línea | Código a capturar |
|---------|-------|-------------------|
| `contacto.css` | 23 | `url('/images/portal-web/portal.jpg')` |

**Cómo capturarlo:** Abrir `contacto.css`, capturar las líneas 18-27 (regla `.contacto-bg`).

**Explicación:** La página de contacto reutiliza la misma imagen de fondo que el hero section de la landing page (`portal.jpg`), pero con un degradado más oscuro para que el formulario destaque mejor.

---

## 11. Funcionalidades Clave

### 10.1 Landing Page Pública
- Hero section con parallax y título degradado
- 6 bloques de funcionalidad con iconos SVG, títulos y descripciones
- Cada bloque tiene imagen de fondo única con overlay de color
- Formulario de contacto embebido
- Diseño responsive con breakpoint a 768px

### 10.2 Autenticación y Roles
- Tres roles: **admin**, **entrenador**, **cliente**
- Cambio forzado de contraseña en primer inicio
- Persistencia de sesión en localStorage
- Interfaz adaptada según el rol del usuario

### 10.3 Dashboard con Vista por Roles
- Admin/Entrenador: tarjetas de resumen con métricas clave
- Cliente: vista personal con sus máquinas, préstamos y compras
- Cada tarjeta de resumen enlaza a la sección correspondiente

### 10.4 Gestión de Productos con Carrito
- CRUD completo de productos con subida de imágenes
- Grid responsive de productos con indicadores de stock
- Carrito de compras en memoria con persistencia por sesión
- Validación de stock al comprar
- Vista de productos comprados por usuario

### 10.5 Sistema de Préstamos
- Materiales agrupados por nombre con disponibilidad en tiempo real
- Selectores de cantidad con límite de stock
- Modal de selección de usuario para administradores
- Registro de fechas de devolución
- Diferencia entre materiales "prestables" y "máquinas"

### 10.6 Gestión de Incidencias
- Incidencias vinculadas a máquinas específicas
- Clasificación por prioridad (alta, media, baja) y estado (abierta, en_proceso, resuelta)
- Vista separada de incidencias activas y resueltas
- Badges de colores según estado y prioridad

### 10.7 Gestión de Máquinas/Equipamiento
- Catálogo completo de máquinas con estados
- Vista de máquinas no operativas con incidencia activa asociada
- Seguimiento de última revisión
- IDs de identificación física (`id_tag_material`)

### 10.8 Gestión de Usuarios
- CRUD completo con filtrado por rol mediante parámetros de ruta
- Títulos dinámicos según el rol seleccionado
- Badges de rol con colores distintivos
- Acción para forzar cambio de contraseña
- Badge de estado "Pendiente"

### 10.9 Server-Side Rendering (SSR)
- Renderizado previo en servidor para todas las rutas
- Servidor Express en puerto 4000 (configurable)
- Mejora de SEO y tiempo de carga inicial

### 10.10 Modo Oscuro/Claro
- Toggle global con persistencia en localStorage
- Sistema de variables CSS para temas
- Aplicado tanto en la página pública como en el dashboard

---

## Apéndice: Guía de Comandos

```bash
# Desarrollo
npm start                    # Iniciar servidor de desarrollo
npm run build                # Compilación de producción
npm run watch                # Compilación en modo watch

# Testing
npm test                     # Ejecutar tests unitarios (vitest)

# Linting/Formato
npx prettier --write .       # Formatear todo el código

# SSR
npm run build                # Build incluye SSR automáticamente
node dist/fitStock-front/server/server.mjs  # Iniciar servidor SSR
```
