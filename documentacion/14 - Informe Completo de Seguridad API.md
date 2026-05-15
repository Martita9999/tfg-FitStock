# Informe Completo de Seguridad — FitStock API

## Resumen de cambios realizados

Se aplicaron **11 mejoras de seguridad** en 3 archivos PHP de la API, más la creación de un archivo `.env.example` y la actualización del `.gitignore`.

---

## Archivos modificados

| Archivo | Cambios aplicados |
|---|---|
| `FitStock-API/conexion.php` | Carga de credenciales desde `.env`, `die()` genérico |
| `FitStock-API/router.php` | CORS dinámico, cabeceras de seguridad HTTP |
| `FitStock-API/.gitignore` (raíz) | Añadida regla para ignorar `.env` |
| `FitStock-API/.env.example` | Archivo nuevo de plantilla para entorno |
| `FitStock-API/api/index.php` | Múltiples mejoras (detalladas abajo) |

---

## Detalle de cada mejora

### 1. Credenciales en archivo `.env`

**Archivo:** `FitStock-API/conexion.php` — Líneas 3-23

**Antes:** Las credenciales de la base de datos estaban hardcodeadas en texto plano:
```php
$DB_HOST = "127.0.0.1";
$DB_NAME = "fitstock";
$DB_USER = "fitstock";
$DB_PASS = "Tokio2324";
```

**Después:** Se cargan desde un archivo `.env` si existe, con fallback a valores por defecto:
```php
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    $env = parse_ini_file($envFile);
    $DB_HOST = $env['DB_HOST'] ?? "127.0.0.1";
    $DB_NAME = $env['DB_NAME'] ?? "fitstock";
    $DB_USER = $env['DB_USER'] ?? "fitstock";
    $DB_PASS = $env['DB_PASS'] ?? "";
}
```

**Archivo nuevo:** `FitStock-API/.env.example` — Plantilla para que cada desarrollador cree su propio `.env`.

**Archivo modificado:** `.gitignore` (raíz) — Se añadió la línea `.env` para evitar que se suba al repositorio.

---

### 2. Corrección de `die()` — No exponer errores de DB

**Archivo:** `FitStock-API/conexion.php` — Líneas 28-30

**Antes:** Mostraba el mensaje de error real de PDO al usuario:
```php
die("Error de conexión: " . $e->getMessage());
```

**Después:** Mensaje genérico + log interno:
```php
error_log("Error de conexión DB: " . $e->getMessage());
die("Error interno del servidor");
```

---

### 3. Cabeceras de seguridad HTTP

**Archivo:** `FitStock-API/router.php` — Líneas 26-38

Se añadieron 5 cabeceras de seguridad:

| Cabecera | Valor | Propósito |
|---|---|---|
| `Content-Security-Policy` | `default-src 'self'; ...` | Restringe qué recursos puede cargar el navegador (previene XSS) |
| `X-Content-Type-Options` | `nosniff` | Evita que el navegador adivine el MIME type |
| `X-Frame-Options` | `DENY` | Previene clickjacking (no se puede cargar en iframes) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Controla cuánta información se envía en Referer |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Obliga a usar HTTPS (solo cuando se sirve con HTTPS) |

**Código:**
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}
```

---

### 4. CORS dinámico

**Archivo:** `FitStock-API/router.php` — Línea 12
**Archivo:** `FitStock-API/api/index.php` — Líneas 11-13

**Antes:** El origen CORS estaba hardcodeado:
```php
header("Access-Control-Allow-Origin: http://localhost:4200");
```

**Después:** Se lee de variable de entorno con fallback:
```php
$allowedOrigin = getenv('FRONTEND_URL') ?: 'http://localhost:4200');
header("Access-Control-Allow-Origin: $allowedOrigin");
```

Esto permite cambiar el origen en producción simplemente definiendo `FRONTEND_URL` en el entorno del servidor, sin tocar código.



---

### 5. Parámetros seguros de sesión

**Archivo:** `FitStock-API/api/index.php` — Líneas 29-34

Se configuran antes de `session_start()`:

```php
session_set_cookie_params([
    'httponly' => true,    // La cookie de sesión no es accesible desde JavaScript
    'samesite' => 'Lax',   // No se envía en peticiones cross-site
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',  // Solo por HTTPS
]);
```

**Antes:** No se configuraban estos parámetros, se usaban los valores por defecto de PHP.

---

### 6. Regeneración de ID de sesión tras login

**Archivo:** `FitStock-API/api/index.php` — Línea 99

**Antes:** No se regeneraba el ID de sesión después del login.

**Después:**
```php
session_regenerate_id(true);
```

Esto previene **session fixation attacks** (cuando un atacante fuerza un ID de sesión conocido).

---



### 7. Rate Limiting (protección contra fuerza bruta)

**Archivo:** `FitStock-API/api/index.php` — Líneas 53-79

Se implementó un sistema de rate limiting basado en archivos que:
- Limita a **10 intentos** de login por IP
- En una ventana de **15 minutos** (900 segundos)
- Almacena los intentos en `sys_get_temp_dir()/fitstock_rate_limit/`
- Usa `LOCK_EX` para evitar condiciones de carrera
- Limpia los intentos tras un login exitoso

**Código:**
```php
define('RATE_LIMIT_DIR', sys_get_temp_dir() . '/fitstock_rate_limit');
define('RATE_LIMIT_MAX_ATTEMPTS', 10);
define('RATE_LIMIT_WINDOW', 900);

function checkRateLimit($ip) {
    // ... crea directorio, lee archivo, verifica intentos
    if ($data['attempts'] > RATE_LIMIT_MAX_ATTEMPTS) {
        jsonResponse(["error" => "Demasiados intentos. Intenta de nuevo en 15 minutos."], 429);
    }
}
```

---

### 8. Política de contraseñas (mínimo 8 caracteres)

**Archivo:** `FitStock-API/api/index.php`

Se añadió validación de longitud mínima en 4 puntos:

| Endpoint | Línea | Código |
|---|---|---|
| `POST /api/registro` | ~123 | `if (strlen($password) < 8)` |
| `POST /api/usuarios` (admin) | ~216 | `if (strlen($password) < 8)` |
| `PUT /api/usuarios/{id}` | ~230 | `if ($password !== null && strlen($password) < 8)` |
| `PUT /api/usuarios/cambiar-password` | ~156 | `if (strlen($new_password) < 8)` |

Todas devuelven `400` con el mensaje `"La contraseña debe tener al menos 8 caracteres"`.

---

### 9. Validación de email con `filter_var`

**Archivo:** `FitStock-API/api/index.php`

Se añadió validación de formato de email en 4 puntos:

| Endpoint | Línea | Código |
|---|---|---|
| `POST /api/login` | ~90 | `if (!filter_var($email, FILTER_VALIDATE_EMAIL))` |
| `POST /api/registro` | ~108 | `filter_var($email, FILTER_VALIDATE_EMAIL)` en el if |
| `POST /api/usuarios` (admin) | ~200 | `if (!filter_var($email, FILTER_VALIDATE_EMAIL))` |
| `PUT /api/usuarios/{id}` | ~215 | `if (!filter_var($email, FILTER_VALIDATE_EMAIL))` |

---

### 10. Validación de tipo MIME real con `finfo`

**Archivo:** `FitStock-API/api/index.php` — Líneas 396-401

**Antes:** Se confiaba en `$_FILES['imagen']['type']`, que es un valor enviado por el cliente y puede ser falseado:
```php
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($imagen['type'], $allowedTypes)) { ... }
```

**Después:** Se leen los primeros bytes reales del archivo con `finfo`:
```php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeReal = finfo_file($finfo, $imagen['tmp_name']);
finfo_close($finfo);

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mimeReal, $allowedTypes)) {
    jsonResponse(["error" => "Formato no válido. Usa JPG, PNG, GIF o WebP"], 400);
}
```

---

### 11. Permisos de directorio de uploads

**Archivo:** `FitStock-API/api/index.php` — Línea 404

**Antes:** `0777` — permisos de lectura, escritura y ejecución para todos:
```php
mkdir($uploadDir, 0777, true);
```

**Después:** `0755` — el propietario tiene todos los permisos, el resto solo lectura/ejecución:
```php
mkdir($uploadDir, 0755, true);
```

---

### 12. Logout seguro

**Archivo:** `FitStock-API/api/index.php` — Líneas 138-147

**Antes:** Solo se destruía la sesión, la cookie del cliente quedaba intacta:
```php
session_destroy();
```

**Después:** También se limpia la cookie del cliente:
```php
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
$_SESSION = [];
session_destroy();
```

---

## Resumen de archivos y líneas

| # | Mejora | Archivo | Líneas |
|---|---|---|---|
| 1 | Credenciales desde `.env` | `conexion.php` | 3-23 |
| 2 | `die()` genérico | `conexion.php` | 28-30 |
| 3 | Cabeceras de seguridad HTTP | `router.php` | 26-38 |
| 4 | CORS dinámico | `router.php`, `api/index.php` | 12, 11-13 |
| 5 | Parámetros seguros de sesión | `api/index.php` | 29-34 |
| 6 | Regeneración ID sesión | `api/index.php` | 99 |
| 7 | Rate limiting | `api/index.php` | 53-79, 104, 113 |
| 8 | Política de contraseñas | `api/index.php` | 123, 156, 216, 230 |
| 9 | Validación de email | `api/index.php` | 90, 108, 200, 215 |
| 10 | Validación MIME real | `api/index.php` | 396-401 |
| 11 | Permisos 0755 uploads | `api/index.php` | 404 |
| 12 | Logout seguro | `api/index.php` | 138-147 |
| — | `.env.example` creado | `.env.example` | Nuevo |
| — | `.env` ignorado en git | `.gitignore` | 6 |

---

## Notas para el frontend

Tras estos cambios, el frontend Angular debe:

1. **Password policy**: Las contraseñas deben tener al menos 8 caracteres.
2. **Email validation**: Los emails deben tener formato válido.

---

## Próximos pasos recomendados

- Configurar **HTTPS** en el servidor de producción (certificado SSL + redirección)
- Añadir **pruebas automatizadas** de seguridad
- Configurar **monitoreo de logs** para detectar ataques de fuerza bruta
