# Estructura de la API REST (PHP)

```
FitStock-API/
├── api/
│   └── index.php          → Enrutador interno. Recibe peticiones /api y las distribuye
│                              por recurso (login, usuarios, materiales, prestamos, etc.)
│                              mediante handleApi(). Contiene toda la lógica de negocio
│                              y responde siempre en JSON con códigos HTTP.
├── config/
│   └── database.sql       → Script SQL completo: CREATE DATABASE, tablas, datos de ejemplo
├── docs/
│   ├── docs.css
│   ├── docs.html
│   └── docs.php           → Documentación HTML de la API (se muestra al acceder a la raíz)
├── models/
│   ├── Compra.php         → CRUD de compras (consultas SQL)
│   ├── Incidencia.php     → CRUD de incidencias
│   ├── Material.php       → CRUD de material/maquinas
│   ├── Prestamo.php       → CRUD de préstamos
│   ├── Producto.php       → CRUD de productos en stock
│   └── Usuario.php        → CRUD de usuarios
├── vendor/
│   └── PHPMailer/         → Librería para envío de correos SMTP (formulario de contacto)
├── .env                   → Variables de entorno (FRONTEND_URL, MAIL_PASSWORD...)
├── conexion.php           → Clase Conexion con PDO (MySQL). Singleton.
└── router.php             → Front controller. Configura CORS, seguridad, sirve estáticos,
                              redirige /api a api/index.php y muestra docs como fallback.
```

## Flujo de una petición

```
Navegador/Angular
       ↓
   router.php              → CORS, seguridad, OPTIONS preflight
       ↓
   ¿/api/recurso?          → Sí → api/index.php → handleApi() → switch($resource)
       ↓                                                        ↓
   No → ¿archivo estático? → Sí → return false (Apache sirve directo)
       ↓
   No → docs/docs.php       (documentación)
```

## Recursos disponibles en la API

| Endpoint | Métodos | Acceso |
|---|---|---|
| `/api/login` | POST | Público |
| `/api/registro` | POST | Público |
| `/api/logout` | POST | Autenticado |
| `/api/usuarios` | GET, POST, PUT, DELETE | Admin/Entrenador |
| `/api/usuarios/cambiar-password` | PUT | Autenticado |
| `/api/usuarios/forzar-cambio` | POST | Admin/Entrenador |
| `/api/materiales` | GET, POST, PUT, DELETE | Autenticado (según rol) |
| `/api/prestamos` | GET, POST, PUT, DELETE | Autenticado (según rol) |
| `/api/productos` | GET, POST, PUT, DELETE | Autenticado (según rol) |
| `/api/productos/subir-imagen` | POST | Admin/Entrenador |
| `/api/compras` | GET, POST | Autenticado (según rol) |
| `/api/incidencias` | GET, POST, PUT, DELETE | Autenticado (según rol) |
| `/api/resumen` | GET | Autenticado |
| `/api/contacto` | POST | Público (con rate limit) |

## Patrón usado

**Front Controller + DAO** (no MVC estricto):

- `router.php` → punto de entrada único (front controller)
- `api/index.php` → controlador con toda la lógica de negocio
- `models/*.php` → DAOs con consultas SQL, sin lógica de negocio
- `conexion.php` → PDO con prepared statements (anti-SQL injection)
