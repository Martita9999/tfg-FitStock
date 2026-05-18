# Estructura de la API REST (PHP) — MVC

```
FitStock-API/
├── api/
│   └── index.php          → Enrutador puro. Recibe peticiones /api, mapea el recurso
│                              a su controlador y lo ejecuta. Sin lógica de negocio.
├── controllers/
│   ├── AuthController.php     → Login, registro, logout
│   ├── UsuarioController.php  → CRUD usuarios + cambiar password
│   ├── MaterialController.php → CRUD materiales
│   ├── PrestamoController.php → CRUD préstamos + aprobar/devolver
│   ├── ProductoController.php → CRUD productos + subir imagen
│   ├── CompraController.php   → CRUD compras
│   ├── IncidenciaController.php → CRUD incidencias
│   ├── ResumenController.php  → Dashboard resumen
│   ├── ContactoController.php → Formulario contacto (PHPMailer)
│   └── StripeController.php   → Payment Intent Stripe
├── helpers/
│   ├── json.php            → jsonResponse(), getJsonInput()
│   ├── auth.php            → requireSession()
│   └── ratelimit.php       → Rate limiting (login + contacto)
├── models/
│   ├── Compra.php         → CRUD compras (consultas SQL)
│   ├── Incidencia.php     → CRUD incidencias
│   ├── Material.php       → CRUD material/maquinas
│   ├── Prestamo.php       → CRUD préstamos
│   ├── Producto.php       → CRUD productos en stock
│   └── Usuario.php        → CRUD usuarios
├── config/
│   └── database.sql       → Script SQL completo: CREATE DATABASE, tablas, datos de ejemplo
├── docs/
│   ├── docs.css
│   ├── docs.html
│   └── docs.php           → Documentación HTML de la API
├── vendor/
│   └── PHPMailer/         → Librería SMTP (formulario de contacto)
├── .env                   → Variables de entorno (FRONTEND_URL, MAIL_PASSWORD, STRIPE_SECRET_KEY)
├── conexion.php           → Clase Conexion con PDO (MySQL). Singleton.
└── router.php             → Front controller. CORS, seguridad, sirve estáticos,
                              redirige /api a api/index.php y muestra docs como fallback.
```

## Flujo de una petición

```
Navegador/Angular
       ↓
   router.php              → CORS, seguridad, OPTIONS preflight
       ↓
   ¿/api/recurso?          → Sí → api/index.php (router)
       ↓                                       ↓
   Mapea recurso a controller                recurso existe?
       ↓                                       ↓
   Controller::handle()       Sí → ejecuta → jsonResponse()
       ↓
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
| `/api/crear-payment-intent` | POST | Autenticado |

## Patrón usado

**MVC (Modelo-Vista-Controlador)**:

- `router.php` → punto de entrada único (front controller)
- `api/index.php` → enrutador, mapea recursos a controladores
- `controllers/*.php` → lógica de negocio por recurso
- `models/*.php` → DAOs con consultas SQL, sin lógica de negocio
- `helpers/*.php` → funciones auxiliares (respuestas, sesión, rate limiting)
- `conexion.php` → PDO con prepared statements (anti-SQL injection)
