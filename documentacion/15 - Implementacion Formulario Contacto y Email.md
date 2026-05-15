# FitStock — Implementación del Formulario de Contacto y Envío de Email

## Resumen

Se implementó un formulario de contacto público en el portal web de FitStock que permite a los usuarios enviar un mensaje directamente a la dirección **infofitstock@gmail.com** a través de la API interna usando la librería **PHPMailer**.

---

## 1. Frontend: Componente Contacto

### Archivos nuevos

| Archivo | Descripción |
|---------|-------------|
| `src/app/components/contacto/contacto.ts` | Lógica del componente Angular |
| `src/app/components/contacto/contacto.html` | Plantilla del formulario |
| `src/app/components/contacto/contacto.css` | Estilos del formulario |

### contacto.ts

- Componente **standalone** que importa `FormsModule` y `HttpClient`.
- **@Input() embed**: permite renderizar el componente de dos formas:
  - `embed=true`: se incrusta en el portal (sin fondo de pantalla completa, sin enlace "Volver").
  - `embed=false`: se muestra como página independiente en `/contacto` (con fondo y enlace de vuelta).
- **Campos del formulario**:
  - `email` — correo electrónico del remitente (obligatorio).
  - `mensaje` — texto libre con la consulta (obligatorio).
- **Método `enviar()`**:
  - Valida que ambos campos estén completos.
  - Envía `POST /api/contacto` con `{ email, mensaje }` mediante `HttpClient`.
  - Muestra mensaje de éxito o error según la respuesta del servidor.
  - Deshabilita el botón durante el envío (`sending` flag).

### contacto.html

Estructura del formulario:

```html
<form (ngSubmit)="enviar()">
  <div class="field">
    <label>Tu correo electrónico</label>
    <input type="email" [(ngModel)]="email" name="email" required>
  </div>
  <div class="field">
    <label>Descripción de lo que necesitas</label>
    <textarea [(ngModel)]="mensaje" name="mensaje" required rows="6"></textarea>
  </div>
  <button type="submit" [disabled]="sending">Enviar Mensaje</button>
</form>
```

- Condicionales `@if` para mostrar mensajes de éxito/error.
- Condicional `@if (!embed)` para ocultar el fondo y el enlace "Volver al Portal" cuando se usa incrustado.

### contacto.css

- Diseño tipo **card centrada** con fondo oscuro semitransparente y `backdrop-filter: blur(12px)`.
- Sin estilos globales que interfieran con el portal.
- Responsive: los campos se adaptan a pantallas pequeñas.
- Loader visual: botón deshabilitado con texto "Enviando..." mientras se procesa la petición.

### Integración en el portal

- **portal.ts**: importa `ContactoComponent` en `imports`.
- **portal.html**: añade `<app-contacto [embed]="true">` dentro de una nueva sección `<section class="feature-block feature-block-contacto">` al final del landing, después del bloque de Dashboard.
- **portal.css**: añade estilo para `.feature-block-contacto` con fondo degradado azul oscuro `#0f172a → #1e293b`.

### Enrutamiento

En `app.routes.ts` se añadió:

```typescript
{ path: 'contacto', component: ContactoComponent }
```

Ruta independiente que permite acceder al formulario en `/contacto`.

---

## 2. Backend: API Endpoint `/api/contacto`

### Archivo modificado: `api/index.php`

Se añadió el caso `contacto` en el switch de `handleApi()`:

```php
case 'contacto':
    if ($method === 'POST') {
        $email = trim($data['email'] ?? '');
        $mensaje = trim($data['mensaje'] ?? '');
        // validaciones...
        // envío con PHPMailer...
    }
    break;
```

**Validaciones**:
- Campos obligatorios: ambos deben estar presentes y no vacíos.
- Email válido: `filter_var($email, FILTER_VALIDATE_EMAIL)`.
- Si alguna validación falla, responde con `400 Bad Request`.

**Envío SMTP mediante PHPMailer**:

| Parámetro | Valor |
|-----------|-------|
| Servidor | `smtp.gmail.com` |
| Puerto | `587` |
| Seguridad | `STARTTLS` (ENCRYPTION_STARTTLS) |
| Autenticación | `true` |
| Usuario | `infofitstock@gmail.com` |
| Contraseña | `getenv('MAIL_PASSWORD')` (desde `.env`) |
| Destino | `infofitstock@gmail.com` |
| Reply-To | El email del remitente |
| Asunto | `"Nuevo contacto desde FitStock"` |

**Manejo de errores**:
- Captura `PHPMailer\PHPMailer\Exception`: devuelve el mensaje de error en la respuesta JSON con código 500.
- Captura `Exception` genérica: devuelve un mensaje genérico con código 500.

---

## 3. Librería PHPMailer

### Instalación

PHPMailer v7.1 se descargó manualmente desde el repositorio oficial de GitHub (sin Composer, ya que no estaba disponible en el entorno de desarrollo).

**Archivos descargados** en `FitStock-API/vendor/PHPMailer/src/`:
- `PHPMailer.php` — clase principal para construir y enviar emails.
- `SMTP.php` — clase para conexión SMTP directa.
- `Exception.php` — clase de excepción específica de PHPMailer.

**Inclusión en `api/index.php`**:

```php
require_once __DIR__ . "/../vendor/PHPMailer/src/Exception.php";
require_once __DIR__ . "/../vendor/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/../vendor/PHPMailer/src/SMTP.php";
```

### Dependencias del sistema

- **PHP extension openssl**: necesaria para la conexión TLS con Gmail. Se activó en `C:\php\php.ini` descomentando `extension=openssl`.

---

## 4. Configuración SMTP (Gmail)

### Contraseña de aplicación

Google no permite usar la contraseña normal de la cuenta para SMTP. Es necesario generar una **contraseña de aplicación**:

1. Activar **Verificación en dos pasos** en [myaccount.google.com/security](https://myaccount.google.com/security).
2. Ir a **Contraseñas de aplicaciones**.
3. Seleccionar "Correo" como aplicación y "Windows Computer" como dispositivo.
4. Generar y copiar la contraseña de 16 caracteres (sin espacios).

### Archivo `.env`

Se copió `.env.example` a `.env` y se añadió:

```
MAIL_PASSWORD="contraseña-de-aplicacion"
```

### Carga de variables de entorno

Se modificó `conexion.php` para reemplazar `parse_ini_file()` por una lectura línea por línea, ya que `parse_ini_file()` fallaba con los comentarios que contenían `=` en el `.env`.

El nuevo parser:
1. Lee el `.env` línea por línea.
2. Ignora líneas vacías o que empiezan con `#`.
3. Divide cada línea por el primer `=`.
4. Limpia comillas dobles de los valores.
5. Llama a `putenv()` para hacer cada variable disponible vía `getenv()`.

---

## 5. Documentación de la API

Se añadió el endpoint `/api/contacto` a la documentación automática en `docs/docs.php`:

```php
'📧 Contacto' => [
    'base' => '/api/contacto',
    'endpoints' => [
        ['POST', '/api/contacto', 'Enviar mensaje de contacto (email + mensaje)', 'any'],
    ]
],
```

La documentación se genera dinámicamente y se visualiza en `http://localhost:8000`.

---

## 6. Resumen de archivos modificados/creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `FitStock-APP/src/app/components/contacto/contacto.ts` | Nuevo | Componente Angular del formulario |
| `FitStock-APP/src/app/components/contacto/contacto.html` | Nuevo | Template del formulario |
| `FitStock-APP/src/app/components/contacto/contacto.css` | Nuevo | Estilos del formulario |
| `FitStock-APP/src/app/app.routes.ts` | Modificado | Ruta `/contacto` añadida |
| `FitStock-APP/src/app/components/portal/portal.ts` | Modificado | Importa ContactoComponent |
| `FitStock-APP/src/app/components/portal/portal.html` | Modificado | Sección de contacto añadida |
| `FitStock-APP/src/app/components/portal/portal.css` | Modificado | Estilo para bloque de contacto |
| `FitStock-API/api/index.php` | Modificado | Endpoint `POST /api/contacto` |
| `FitStock-API/conexion.php` | Modificado | Parser de .env mejorado |
| `FitStock-API/vendor/PHPMailer/src/*` | Nuevo | Librería PHPMailer (3 archivos) |
| `FitStock-API/.env` | Nuevo | Configuración con MAIL_PASSWORD |
| `FitStock-API/.env.example` | Modificado | Documentación de MAIL_PASSWORD |
| `FitStock-API/docs/docs.php` | Modificado | Endpoint de contacto en docs |
| `C:\php\php.ini` | Modificado | `extension=openssl` activada |

---

## 7. Tecnologías y librerías utilizadas

| Tecnología | Versión | Propósito |
|-----------|---------|-----------|
| Angular | 21.2 | Frontend framework |
| PHPMailer | 7.1 | Envío de emails SMTP |
| PHP | 8.4 | Backend API |
| OpenSSL | (PHP ext) | Cifrado TLS para SMTP |
| Gmail SMTP | — | Servidor de correo saliente |
