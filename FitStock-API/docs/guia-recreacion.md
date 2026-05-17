# Guía de Recreación del Proyecto FitStock

## 1. Base de Datos

### 1.1 Crear la base de datos

Ejecutar `config/database.sql` completo en phpMyAdmin o MySQL CLI.
Crea las 6 tablas con datos de ejemplo.

**Tablas creadas:**
| Tabla | Líneas en database.sql |
|---|---|
| `usuarios` | 9-17 |
| `material` | 20-30 |
| `prestamos` | 35-44 |
| `productos_stock` | 49-57 |
| `compras` | 62-72 |
| `incidencias` | 76-87 |

**Datos de ejemplo insertados:**
| INSERT | Líneas |
|---|---|
| Usuarios (4) | 94-98 |
| Material prestable (24) | 101-125 |
| Máquinas (13) | 128-141 |
| Productos (8) | 150-158 |
| Incidencias (1) | 161-162 |
| Préstamos (4) | 165-169 |
| Compras (5) | 172-177 |

### 1.2 Usuario de BD

Al final del script (líneas 179-181) se crea el usuario `fitstock` con contraseña `Tokio2324` y se le asignan todos los permisos sobre la base `fitstock`.

---

## 2. API PHP

### 2.1 Estructura

```
FitStock-API/
├── router.php           → Front controller (punto de entrada único)
├── conexion.php         → Clase PDO singleton
├── api/index.php        → Enrutador + lógica de negocio
├── models/
│   ├── Usuario.php      → CRUD usuarios
│   ├── Material.php     → CRUD material/máquinas
│   ├── Prestamo.php     → CRUD préstamos
│   ├── Producto.php     → CRUD productos stock
│   ├── Compra.php       → CRUD compras
│   └── Incidencia.php   → CRUD incidencias
├── vendor/PHPMailer/    → Envío de correos SMTP
├── config/database.sql  → Script BD
├── docs/                → Documentación HTML
└── .env                 → Variables de entorno
```

### 2.2 Conexión a BD (`conexion.php`)

```
Línea 18: define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
Línea 19: define('DB_NAME', getenv('DB_NAME') ?: 'fitstock');
Línea 20: define('DB_USER', getenv('DB_USER') ?: 'fitstock');
Línea 21: define('DB_PASS', getenv('DB_PASS') ?: 'Tokio2324');
```

Uso de `getenv()` permite sobrescribir con variables de entorno en producción.

### 2.3 Variables de entorno (`.env`)

```
FRONTEND_URL=http://localhost:4200
DB_HOST=localhost
DB_NAME=fitstock
DB_USER=fitstock
DB_PASS=Tokio2324
MAIL_PASSWORD=contraseña_de_gmail
```

`FRONTEND_URL` → Controla CORS dinámico (router.php línea 22, api/index.php línea 25)
`MAIL_PASSWORD` → Contraseña SMTP de Gmail para formulario de contacto (api/index.php línea 991)

### 2.4 router.php — Front controller

| Función | Líneas |
|---|---|
| CORS dinámico | 22, 33-36 |
| Cabeceras de seguridad (CSP, HSTS, X-Frame-Options) | 58-64 |
| Preflight OPTIONS | 75-78 |
| Servir estáticos | 92-95 |
| Redirigir `/api` | 106-109 |
| Fallback docs | 117-119 |

### 2.5 api/index.php — Lógica de negocio

| Endpoint | Líneas | Métodos | Control de acceso |
|---|---|---|---|
| `case 'login'` | 281-316 | POST | Público (con rate limit) |
| `case 'registro'` | 332-352 | POST | Público |
| `case 'logout'` | 363-374 | POST | Autenticado |
| `case 'usuarios'` | 404-513 | GET, POST, PUT, DELETE | Admin/Entrenador |
| `case 'materiales'` | 528-577 | GET, POST, PUT, DELETE | Rol según acción |
| `case 'prestamos'` | 591-657 | GET, POST, PUT, DELETE | Rol según acción |
| `case 'productos'` | 678-754 | GET, POST, PUT, DELETE | Rol según acción |
| `case 'compras'` | 768-800 | GET, POST | Rol según acción |
| `case 'incidencias'` | 815-876 | GET, POST, PUT, DELETE | Rol según acción |
| `case 'resumen'` | 890-952 | GET | Autenticado |
| `case 'contacto'` | 971-1014 | POST | Público (rate limit 5/15min) |

**Funciones auxiliares importantes:**
- `jsonResponse($data, $code)` — línea 219
- `getJsonInput()` — línea 234
- `requireSession()` — línea 1034
- `checkRateLimit($ip)` — línea 101
- `clearRateLimit($ip)` — línea 131
- `checkRateLimitContacto($ip)` — línea 148

### 2.6 Rate limiting

| Función | Intentos | Ventana | Líneas |
|---|---|---|---|
| `checkRateLimit` | 10 | 15 min | 81-123 |
| `checkRateLimitContacto` | 5 | 15 min | 146-170 |

---

## 3. Frontend Angular

### 3.1 Estructura

```
FitStock-APP/src/app/
├── app.ts                    → Componente raíz
├── app.config.ts             → Config global (HttpClient, rutas)
├── app.routes.ts             → Definición de rutas
├── app.html / app.css        → Template y estilos raíz
├── interfaces/
│   └── app.interfaces.ts     → Interfaces TypeScript
├── services/                 → 9 servicios HTTP
│   ├── usuario.ts            → Auth + usuarios
│   ├── productos.service.ts  → CRUD productos
│   ├── materiales.service.ts → CRUD materiales
│   ├── prestamos.service.ts  → CRUD préstamos
│   ├── incidencias.service.ts→ CRUD incidencias
│   ├── compras.service.ts    → CRUD compras
│   ├── resumen.service.ts    → Dashboard datos agregados
│   ├── cart.service.ts       → Carrito de compras
│   └── toast.service.ts      → Notificaciones toast
└── components/               → 12 componentes
    ├── admin-dashboard/      → Layout panel admin
    ├── admin-sidebar/        → Barra lateral por rol
    ├── dashboard-home/       → Dashboard principal
    ├── producto-list/        → CRUD productos + carrito
    ├── prestamo-list/        → CRUD préstamos
    ├── material-list/        → CRUD máquinas
    ├── incidencia-list/      → CRUD incidencias
    ├── usuario-list/         → CRUD usuarios
    ├── login/                → Inicio de sesión
    ├── registro/             → Registro público
    ├── portal/               → Landing page
    └── contacto/             → Formulario de contacto
```

### 3.2 Rutas (`app.routes.ts`)

| Ruta | Componente | Acceso |
|---|---|---|
| `/` | PortalComponent | Público |
| `/login` | LoginComponent | Público |
| `/registro` | RegistroComponent | Público |
| `/contacto` | ContactoComponent | Público |
| `/admin/home` | DashboardHomeComponent | Autenticado |
| `/admin/inventario` | ProductoList | Autenticado |
| `/admin/prestamos/:vista` | PrestamoList | Autenticado |
| `/admin/materiales/:vista` | MaterialList | Autenticado |
| `/admin/incidencias/:vista` | IncidenciaList | Autenticado |
| `/admin/usuarios/:rol` | UsuarioList | Admin/Entrenador |

### 3.3 API_URL en servicios

Cada servicio apunta a `http://localhost:8000/api`. Si se cambia la URL del backend, hay que actualizar la constante en cada servicio:

| Servicio | Línea |
|---|---|
| `usuario.ts` | 28 |
| `productos.service.ts` | 13 |
| `materiales.service.ts` | 12 |
| `prestamos.service.ts` | 8 |
| `incidencias.service.ts` | 14 |
| `compras.service.ts` | 13 |
| `resumen.service.ts` | 21 |

---

## 4. Despliegue

### 4.1 Local (XAMPP)

1. Colocar `FitStock-API/` en `C:\xampp\htdocs\fitstock-api`
2. Importar `config/database.sql` desde phpMyAdmin
3. Iniciar Apache y MySQL desde panel XAMPP
4. La API responde en `http://localhost:8000`
5. `FitStock-APP/` se sirve con `ng serve` en `http://localhost:4200`

### 4.2 Producción (Arsys Linux Hosting)

1. Subir `FitStock-API/` al servidor por FTP
2. Crear BD en el panel de Arsys e importar `database.sql`
3. Configurar variables de entorno:
   - `FRONTEND_URL` → URL del frontend desplegado
   - `MAIL_PASSWORD` → Contraseña SMTP Gmail
4. Configurar dominio para que apunte a `router.php` como punto de entrada
5. Construir frontend con `ng build --configuration production` y subir `dist/`

---

## 5. Dependencias

### 5.1 Frontend (npm)

```
npm install
```

Las dependencias están en `package.json`. Las principales:
- Angular 21.2
- jspdf + jspdf-autotable (exportación PDF)
- rxjs, zone.js

### 5.2 Backend (PHP)

No necesita Composer. PHPMailer está incluido en `vendor/` como copia directa.

### 5.3 Configuración del servidor

Apache necesita `mod_rewrite` activado para que `router.php` funcione como front controller. En Arsys viene activado por defecto.
