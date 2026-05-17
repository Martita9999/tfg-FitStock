# Futuras Mejoras — FitStock

## 1. Backend (API PHP)

### 1.1 Migrar de sesiones a JWT
- Sustituir `$_SESSION` por tokens JWT en header `Authorization`
- Beneficio: API stateless, escalable, desacoplada del navegador
- Archivos a tocar: `api/index.php` (funciones `requireSession`, login, logout), todos los servicios Angular (quitar `withCredentials`)

### 1.2 Testing automatizado (PHPUnit)
- Tests unitarios para modelos (CRUD)
- Tests de integración para endpoints (login, usuarios, préstamos...)
- Beneficio: evitar regresiones al añadir features

### 1.3 Middleware de autenticación
- Extraer la comprobación de sesión/roles a un middleware en lugar de repetir `if ($_SESSION['usuario_rol'] === 'cliente')` en cada endpoint

### 1.4 Paginación en endpoints GET
- Añadir `?page=1&limit=20` a `/api/prestamos`, `/api/usuarios`, `/api/compras`
- Beneficio: rendimiento con muchos registros

### 1.5 WebSockets para notificaciones en tiempo real
- Notificar al admin cuando un cliente crea un préstamo o reporta una incidencia
- Tecnología: Ratchet (PHP WebSockets) o SSE (Server-Sent Events)

### 1.6 Logging centralizado
- Monolog o logging propio para registrar errores, intentos de login, cambios de estado
- Beneficio: depuración en producción

---

## 2. Frontend (Angular)

### 2.1 SSR con Angular Universal
- Mejorar SEO y primera carga (puede reintentarse ahora que se ha limpiado)
- Beneficio: las landing públicas (portal, contacto) se renderizan en servidor

### 2.2 Modo offline / PWA
- Service Worker con Angular Service Worker
- Cachear data de catálogo (productos, máquinas) para consulta sin conexión

### 2.3 Testing (Vitest + Playwright)
- Tests unitarios para componentes y servicios
- Tests e2e para flujos críticos (login → comprar producto, crear préstamo)

### 2.4 Panel de administración avanzado
- Gráficas con Chart.js o ngx-charts para el dashboard (ventas mensuales, máquinas más usadas)
- Exportación de informes con filtros de fecha

### 2.5 Notificaciones push
- Con la PWA + Firebase Cloud Messaging, notificar al cliente cuando su préstamo es aprobado o su incidencia resuelta

### 2.6 Mejora de UX
- Skeletons de carga mientras se obtienen datos
- Búsqueda con autocomplete en listados largos (usuarios, productos)
- Filtros combinados en préstamos e incidencias

### 2.7 Internacionalización (i18n)
- ngx-translate para soporte multiidioma
- Beneficio: si el gimnasio tiene clientes extranjeros

---

## 3. Base de Datos

### 3.1 Soft deletes
- Añadir `deleted_at TIMESTAMP NULL` en lugar de DELETE físico
- Beneficio: recuperación de datos eliminados por error

### 3.2 Histórico de cambios
- Tabla `log_cambios` que registre quién y cuándo modificó cada recurso
- Beneficio: auditoría para el gerente del gimnasio

### 3.3 Migraciones (Phinx o similar)
- Versionar los cambios de esquema en lugar de editar `database.sql` manualmente

---

## 4. Infraestructura

### 4.1 CI/CD
- GitHub Actions para ejecutar tests automáticos al hacer push
- Despliegue automático a Arsys tras pasar tests

### 4.2 Contenedores (Docker)
- Docker Compose con PHP + Apache, MySQL y Angular sirviendo desde Nginx
- Beneficio: entorno idéntico en desarrollo y producción

### 4.3 Monitorización
- Sentry para errores en frontend y backend
- Alertas ante caídas del servidor
