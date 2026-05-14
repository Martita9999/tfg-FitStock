# FitStock — Documentación Completa de Cambios

## Archivos Nuevos

### FitStock-API/docs/docs.php
Genera dinámicamente la página de documentación de la API.
- Array `$resources` con todos los endpoints agrupados por recurso.
- Cada endpoint: `[Método, Ruta, Descripción, ClaveAuth]`.
- Arrays `$authLabels` y `$methodColors` para etiquetas y colores.
- Bucle que genera HTML para cada recurso y endpoint.
- Lee `docs.html` como plantilla y reemplaza `{{RESOURCES}}`.

### FitStock-API/docs/docs.html
Plantilla HTML5 de la documentación.
- Enlace a `docs.css`.
- Marcador `{{RESOURCES}}` para inyección dinámica.
- Footer.

### FitStock-API/docs/docs.css
Estilos de la documentación.
- Reset universal.
- Body con gradiente claro.
- Tarjetas `.resource` con sombra y bordes redondeados.
- Badges `.method` con colores por tipo HTTP.
- Badges `.auth` con colores por nivel de acceso.
- Media query responsive a 768px.

### FitStock-APP/src/app/components/dashboard-home/dashboard-home.ts
Componente del dashboard principal.
- Inyecta servicios: Resumen, Materiales, Préstamos, Compras, Usuario.
- `cargarResumen()`: obtiene resumen (admin/entrenador) con incidencias, stock bajo, máquinas, préstamos pendientes, total usuarios.
- `cargarDatosCliente()`: obtiene datos del cliente (máquinas, préstamos, compras).
- `cambiarPassword()`: cambio de contraseña para clientes.

### FitStock-APP/src/app/components/dashboard-home/dashboard-home.html
Template del dashboard.
- Admin/Entrenador: 5 tarjetas (Incidencias, Stock Bajo, Máquinas, Préstamos Pendientes, Usuarios).
- Cliente: 4 tarjetas (Mis Datos con cambio de contraseña, Máquinas, Mis Préstamos, Productos Comprados).
- Enlaces de navegación a cada sección.

### FitStock-APP/src/app/components/dashboard-home/dashboard-home.css
Estilos del dashboard.
- CSS Grid con `repeat(auto-fill, minmax(320px, 1fr))`.
- Tarjetas con bordes de colores y cabeceras con gradiente.
- Variables CSS para modo oscuro.

### FitStock-APP/src/app/services/toast.service.ts
Servicio de notificaciones toast.
- Métodos: `show()`, `success()`, `error()`, `info()`.
- Crea elementos toast dinámicamente con animaciones.
- Auto-dismiss después de 4 segundos.

### FitStock-APP/src/app/services/resumen.service.ts
Servicio para el resumen del dashboard.
- Obtiene datos agregados de `/api/resumen`.

---

## Archivos Modificados — Backend (FitStock-API)

### router.php
Router principal del servidor PHP.
```
Líneas 14-18: Servir archivos estáticos
  - Construye ruta absoluta del archivo solicitado
  - Si existe y no es la raíz, devuelve false para que PHP sirva el archivo con su MIME type
Líneas 20-23: Si la ruta empieza por /api, carga api/index.php
Líneas 25-28: Si no es API ni estático, muestra la documentación HTML via docs/docs.php
```

### models/Usuario.php
Modelo de usuario con nuevas funcionalidades.
```
Línea 11: Nueva propiedad privada $forzar_cambio_password
Línea 13: Constructor actualizado con parámetro forzar_cambio_password
Líneas 29, 40: obtenerPorId() y obtenerPorEmail() incluyen forzar_cambio_password
Líneas 45-57: NUEVO método obtenerSiguienteIdLibre()
  - Obtiene todos los IDs ordenados
  - Busca el primer hueco (gap) desde 1
  - Devuelve el ID libre para reutilizar
Líneas 59-65: crear() modificado
  - Calcula ID libre con obtenerSiguienteIdLibre()
  - Inserta con id_usuario explícito en lugar de AUTO_INCREMENT
Líneas 107-111: NUEVO método forzarCambioPassword($id)
  - UPDATE SET forzar_cambio_password = 1
Líneas 113-117: NUEVO método limpiarForzarCambioPassword($id)
  - UPDATE SET forzar_cambio_password = 0
```

### api/index.php
Router de la API REST con nuevos endpoints.
```
Línea 82: Login devuelve "forzar_cambio_password" con intval()
Línea 138: Cambiar-password resetea forzar_cambio_password = 0
Líneas 150-164: GET /api/usuarios devuelve forzar_cambio_password
Líneas 165-175: NUEVO POST /api/usuarios/forzar-cambio
  - Requiere admin o entrenador
  - Llama a Usuario::forzarCambioPassword($id)
  - Devuelve {success: true}
```

### config/database.sql
Esquema de base de datos actualizado.
```
Línea 19: Nueva columna forzar_cambio_password TINYINT(1) NOT NULL DEFAULT 0
```

---

## Archivos Modificados — Frontend (FitStock-APP)

### src/index.html
Inicialización temprana del tema oscuro.
```
Líneas 9-13: Script inline en <head>
  - Lee localStorage antes de que Angular cargue
  - Si darkMode === 'true', establece data-theme="dark"
  - Evita el flash de modo claro al recargar
```

### src/styles.css
Variables globales y modo oscuro.
```
:root (modo claro):
  --bg: #f1f5f9 (más gris para contraste)
  --border: #cbd5e1 (bordes más visibles)
  Nuevas variables semánticas: --error-bg, --error-color, --error-border,
    --success-bg, --success-color, --success-border,
    --warning-bg, --warning-color
[data-theme="dark"]:
  --bg: #0f172a, --card: #1e293b, --text: #f1f5f9
  --border: #334155
  body: transition background/color 0.3s
  new: .btn-edit:hover, .btn-delete:hover, .data-table tbody tr:hover
  shared: .modal-card, .field input, .btn-cancel usan var(--card)
  color: var(--text) añadido a inputs
```

### login/login.ts
Toggle de modo oscuro en login.
```
Línea 20: darkMode = false
Líneas 27-32: Constructor: init desde localStorage y aplicar tema
Líneas 34-42: toggleDarkMode(): invertir, persistir, actualizar data-theme
Línea 49: Forzar cambio para cliente O entrenador
```

### login/login.html
Botón de tema y modal de cambio de contraseña.
```
Línea 2: <button class="theme-toggle"> con 🌙/☀️
Líneas 31-59: Modal de cambio de contraseña obligatorio
```

### login/login.css
Estilos del login con soporte dark mode.
```
.theme-toggle: botón en esquina superior derecha
  position: absolute, rgba(255,255,255,0.1), border-radius 10px
  hover: rgba(255,255,255,0.2), scale(1.05)
.field input: background var(--card), color var(--text)
.login-card: background var(--card)
.modal-card: background var(--card)
.error-msg: usa variables semánticas
```

### admin-sidebar/admin-sidebar.ts
Sidebar con toggle de modo oscuro.
```
Línea 17: darkMode = false
Líneas 19-21: @Input collapsed, @Output toggleSidebar/closeSidebar
Líneas 26-28: ngOnInit: leer localStorage, applyTheme()
Líneas 30-34: toggleDarkMode(): invertir, persistir, aplicar
Líneas 36-42: applyTheme(): data-theme="dark" o removeAttribute()
```

### admin-dashboard/admin-dashboard.css
Fondo adaptable a modo oscuro.
```
.dashboard-wrapper: gradient fijo → var(--bg-gradient)
.data-table tbody tr:hover: #fff7ed → var(--bg)
.menu-toggle: var(--orange), box-shadow
.toast-container, .toast: estilos de notificaciones
```

### usuario-list/usuario-list.ts
Forzar cambio de contraseña.
```
Líneas 104-113: forzarCambioPassword(u)
  - Llama a usuarioService.forzarCambioPassword(id)
  - Actualiza u.forzar_cambio_password a 1 en la lista
```

### usuario-list/usuario-list.html
Botón 🔑 para entrenadores.
```
Línea 32: Condición ampliada
  - Antes: u.rol === 'cliente'
  - Ahora: (userRole === 'admin' || u.rol === 'cliente') && u.rol !== 'admin'
Línea 33: Badge ⏳ Pendiente si forzar_cambio_password activo
```

### producto-list/producto-list.html
Texto de stock simplificado.
```
Línea 35: 'Stock OK' → 'Stock'
```

### services/usuario.ts
Nuevos métodos en el servicio.
```
Línea 10: forzar_cambio_password?: number en interface Usuario
Líneas 65-67: forzarCambioPassword(id): POST /api/usuarios/forzar-cambio
Líneas 73-75: cambiarPassword(old, new): PUT /api/usuarios/cambiar-password
```

### interfaces/app.interfaces.ts
Interfaz Usuario actualizada.
```
Línea 6: forzar_cambio_password?: number
```

---

## Resumen de Funcionalidades Nuevas

| Funcionalidad | Backend | Frontend |
|---|---|---|
| Documentación API en localhost:8000 | docs.php + docs.html + docs.css | — |
| Servir archivos estáticos | router.php | — |
| Modo oscuro | — | index.html, styles.css, sidebar, login |
| Dashboard con préstamos y usuarios | — | dashboard-home (ts/html/css) |
| Forzar cambio contraseña | index.php, Usuario.php | usuario-list, login |
| Reutilizar IDs de usuarios | Usuario.php (obtenerSiguienteIdLibre) | — |
| Toast notifications | — | toast.service.ts |
| Botón Devuelto para todos | — | prestamo-list.html |
| Inputs adaptativos dark mode | — | styles.css, login.css, registro.css, usuario-list.css, dashboard-home.css |
| Variables semánticas de estado | — | styles.css |
