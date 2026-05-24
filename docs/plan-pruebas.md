# Plan de Pruebas — FitStock

## Convención
```
[OK] = Funciona correctamente
[FAIL] = No funciona / Error
[?] = No probado / Duda
```

---

## 1. AUTENTICACIÓN Y SESIÓN

### 1.1 Registro
| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 1.1.1 | Registro exitoso | Ir a `/registro`, rellenar todos los campos, enviar | Redirige a login, mensaje de éxito |
| 1.1.2 | Email duplicado | Registrar con email ya existente | Error: "El email ya está registrado" |
| 1.1.3 | Password corto | Contraseña de < 8 caracteres | Error: "La contraseña debe tener al menos 8 caracteres" |
| 1.1.4 | Campos vacíos | Enviar formulario sin rellenar | Error de validación |
| 1.1.5 | Email inválido | `usuario@invalido` | Error: "Email inválido" |

### 1.2 Login
| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 1.2.1 | Login correcto | Email + contraseña válidos | Redirige a dashboard según rol |
| 1.2.2 | Contraseña incorrecta | Email correcto + pass incorrecta | Error: "Credenciales inválidas" |
| 1.2.3 | Usuario inexistente | Email no registrado | Error: "Credenciales inválidas" (mismo mensaje, no revelar existencia) |
| 1.2.4 | Sesión persistente | Login → cerrar pestaña → abrir `/admin/home` | Sigue logueado (cookie de sesión) |
| 1.2.5 | Logout | Clic en "Cerrar Sesión" | Redirige a login, no puede volver atrás |

### 1.3 Rate Limiting (seguridad)
| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 1.3.1 | 10 intentos de login | Introducir 10 veces credenciales incorrectas seguidas | A partir del 11º intento: "Demasiados intentos. Intenta de nuevo en 15 minutos." (HTTP 429) |
| 1.3.2 | Reset tras login exitoso | Hacer 5 intentos fallidos → 1 exitoso → 5 fallidos más | NO debe bloquear (el rate limit se resetea al hacer login) |
| 1.3.3 | Rate limit contacto | Enviar formulario de contacto 6 veces seguidas | 6º mensaje: "Has superado el límite de mensajes. Intenta de nuevo en 15 minutos." |

---

## 2. CONTROL DE ACCESO POR ROLES

Probar cada funcionalidad con 3 cuentas: **admin**, **entrenador**, **cliente**.

| # | Prueba | Admin | Entrenador | Cliente |
|---|---|---|---|---|
| 2.1 | Ver lista usuarios | ✅ Ve todos | ✅ Ve todos (admin ocultos en UI) | ❌ Redirigido / error |
| 2.2 | Crear usuario | ✅ Botón visible | ❌ Botón oculto | ❌ Botón oculto |
| 2.3 | Editar usuario | ✅ Puede editar cualquiera | ✅ Solo no-admin | ❌ No puede |
| 2.4 | Borrar usuario | ✅ Botón visible | ❌ Botón oculto | ❌ Botón oculto |
| 2.5 | Forzar cambio password | ✅ | ✅ (no admins) | ❌ |
| 2.6 | Crear producto | ✅ | ✅ | ❌ |
| 2.7 | Editar producto | ✅ | ✅ | ❌ |
| 2.8 | **Borrar producto** | ✅ | ❌ (oculto + 403 API) | ❌ |
| 2.9 | Crear máquina | ✅ | ✅ | ❌ |
| 2.10 | **Borrar máquina** | ✅ | ❌ (oculto + 403 API) | ❌ |
| 2.11 | Ver incidencias | ✅ Todas | ✅ Todas | ❌ Solo las suyas |
| 2.12 | **Borrar incidencia** | ✅ | ❌ (oculto + 403 API) | ❌ |
| 2.13 | Ver préstamos | ✅ Todos | ✅ Todos | ❌ Solo los suyos |
| 2.14 | Aprobar préstamo | ✅ | ✅ | ❌ |
| 2.15 | **Borrar préstamo** | ✅ | ❌ (oculto + 403 API) | ❌ |
| 2.16 | Ver dashboard | ✅ Completo | ✅ Completo | ❌ Solo panel cliente |
| 2.17 | Comprar productos | ✅ | ✅ | ✅ |
| 2.18 | Ver gastos totales | ✅ | ✅ | ❌ Solo su gasto |

### Cómo probar que un botón NO está visible
1. Iniciar sesión como `entrenador`
2. Ir a la sección correspondiente
3. Verificar que el botón "Borrar" no aparece en ninguna fila

### Cómo probar 403 API (bypass directo)
1. Abrir DevTools → Network
2. Hacer clic en borrar (si el botón está, pero no debería)
3. O directamente: `curl -X DELETE http://localhost:8000/api/productos/1 -b <cookie>`
4. Debe responder: `{"error": "Acceso denegado"}` (HTTP 403)

---

## 3. PRODUCTOS

| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 3.1 | Listar productos | Entrar a /admin/inventario | Se ven todos los productos con imagen, nombre, precio, stock |
| 3.2 | Crear producto (admin) | Rellenar formulario + imagen | Producto creado, imagen visible |
| 3.3 | Crear producto sin nombre | Dejar nombre vacío | Error: "El nombre es obligatorio" |
| 3.4 | Editar producto | Cambiar nombre, precio, stock | Se actualiza en la lista |
| 3.5 | Borrar producto (admin) | Clic en borrar → confirmar modal | Producto eliminado de la lista |
| 3.6 | Subir imagen | Seleccionar `.jpg` | Imagen visible en la card |
| 3.7 | Subir archivo no imagen | Seleccionar `.pdf` o `.exe` | Error, no se sube |
| 3.8 | Stock mínimo | Producto con stock < mínimo | Aparece en dashboard como "Stock Bajo" |
| 3.9 | Stock 0 | Producto sin stock | Aparece como "Sin stock", no se puede añadir al carrito |

---

## 4. CARRITO Y COMPRAS

### 4.1 Carrito
| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 4.1.1 | Añadir producto | Clic en imagen del producto | Se añade al carrito, la imagen cambia a descripción |
| 4.1.2 | Añadir producto sin stock | Producto con cantidad 0 | No se añade |
| 4.1.3 | Ver carrito | Clic en icono carrito | Se abre dropdown con productos, cantidades, total |
| 4.1.4 | Cambiar cantidad | + / - o escribir número | Se actualiza total |
| 4.1.5 | Eliminar producto del carrito | Clic en papelera | Producto eliminado, total actualizado |
| 4.1.6 | Persistencia carrito | Añadir productos → recargar página | Los productos siguen en el carrito |
| 4.1.7 | Carrito por usuario | Login usuario A → añadir → logout → login usuario B | El carrito de B está vacío (no hereda el de A) |
| 4.1.8 | Scroll carrito | Añadir 10+ productos | El dropdown hace scroll vertical |
| 4.1.9 | Responsive carrito | Reducir ventana a 480px | Carrito ocupa todo el ancho desde abajo |

### 4.2 Pago
| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 4.2.1 | Iniciar compra | "Comprar Ahora" → formulario Stripe | Aparece formulario de tarjeta |
| 4.2.2 | Pago exitoso | Tarjeta `4242 4242 4242 4242`, cualquier fecha futura, CVC 123 | "Compra realizada con éxito", carrito vacío |
| 4.2.3 | Pago rechazado | Tarjeta `4000 0000 0000 0002` | Error: "Tu tarjeta fue rechazada" |
| 4.2.4 | Pago requiere 3DS | Tarjeta `4000 0025 0000 3155` | Aparece ventana 3DS, se completa al autenticar |
| 4.2.5 | Compra sin stock | Dos usuarios compran el último producto simultáneamente | Segundo recibe error "Stock insuficiente" |
| 4.2.6 | Total en histórico | Ir a Productos Comprados (cliente) o vista admin | La compra aparece con fecha, producto, total |
| 4.2.7 | **Seguridad: modificar total** | DevTools → modificar total en petición a 0.01€ | Stripe cobra el total REAL (calculado por backend), no el manipulado |

### Cómo probar 4.2.7 (seguridad)
```javascript
// En consola del navegador, interceptar la petición:
// 1. Abrir DevTools → Network
// 2. Clic en "Comprar Ahora"
// 3. Ver petición a /api/crear-payment-intent
// 4. Copiar como fetch, modificar total
fetch("/api/crear-payment-intent", {
  method: "POST",
  body: JSON.stringify({ items: [{id:1, cantidad:1, precio:0.01}] }),
  credentials: "include",
  headers: {"Content-Type": "application/json"}
}).then(r => r.json()).then(console.log)
// El backend recalcula el precio real desde la BD
```

---

## 5. SEGURIDAD

| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 5.1 | **SQL Injection en login** | Email: `' OR 1=1 --` | Login falla (no entra) |
| 5.2 | **SQL Injection en campos** | En cualquier input: `'; DROP TABLE usuarios; --` | No ejecuta, error controlado |
| 5.3 | **XSS en nombre producto** | Crear producto: `<script>alert('XSS')</script>` | Se muestra como texto, no se ejecuta |
| 5.4 | **XSS en descripción** | Editar descripción con HTML | Se escapa, no se renderiza |
| 5.5 | **Acceso sin sesión** | Abrir `/api/productos` sin cookie | `{"error": "No autenticado"}` (HTTP 401) |
| 5.6 | **CSRF** | Enviar petición POST desde otro sitio | Bloqueado por SameSite=Lax + CORS |
| 5.7 | **Subida de .php** | Intentar subir `shell.php` como imagen | Rechazado por validación MIME (`finfo`) |
| 5.8 | **Subida de archivo grande** | Subir imagen de 100MB | Debería rechazar o dar error (actualmente sin límite) |
| 5.9 | **CORS desde otro origen** | `curl -H "Origin: https://evil.com" ...` | No se permiten credenciales |
| 5.10 | **Contraseña en texto plano?** | Ver BD: `SELECT password FROM usuarios` | Los hash empiezan con `$2y$...` (bcrypt) |

---

## 6. MÁQUINAS (MATERIALES)

| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 6.1 | Listar máquinas | Vista completa, operativas, incidencias | Filtros funcionan |
| 6.2 | Crear máquina | Rellenar formulario | Aparece en la lista |
| 6.3 | Editar máquina | Cambiar estado a "averiado" | Aparece en incidencias activas |
| 6.4 | Borrar máquina (admin) | Confirmar borrado | Desaparece de la lista |
| 6.5 | Campos opcionales | Crear sin ubicación ni revisión | Se crea con valores por defecto |

---

## 7. INCIDENCIAS

| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 7.1 | Crear incidencia | Rellenar formulario | Aparece en "Activas" |
| 7.2 | Cambiar estado | Editar → "en_proceso" → "resuelta" | Se mueve entre pestañas |
| 7.3 | Exportar Excel/PDF | Clic en exportar | Descarga archivo |
| 7.4 | Prioridades | Crear con prioridad alta/media/baja | Color diferente en la tabla |

---

## 8. PRÉSTAMOS

| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 8.1 | Solicitar préstamo (cliente) | Seleccionar material, enviar | Aparece como "pendiente" |
| 8.2 | Aprobar préstamo (admin) | Aceptar solicitud pendiente | Cambia a "activo" |
| 8.3 | Marcar devolución | Confirmar devolución | Pasa a "completado" |
| 8.4 | Material no disponible | Prestar misma unidad 2 veces | Error / controlado |
| 8.5 | Exportar CSV/PDF | Clic en exportar | Descarga archivo |

---

## 9. PERFIL

| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 9.1 | Cambiar contraseña | Contraseña actual + nueva (8+ chars) | "Contraseña cambiada con éxito" |
| 9.2 | Contraseña incorrecta | Poner contraseña actual mal | Error: "La contraseña actual no es correcta" |
| 9.3 | Forzar cambio (admin) | Admin fuerza cambio a usuario | Usuario redirigido a cambiar contraseña al loguearse |

---

## 10. CONTACTO

| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 10.1 | Enviar mensaje | Rellenar nombre, email, mensaje | "Mensaje enviado correctamente" |
| 10.2 | Email inválido | `email@invalido` | Error de validación |
| 10.3 | Rate limit | Enviar 6 mensajes seguidos | 6º bloqueado: "Has superado el límite de mensajes" |

---

## 11. RESPONSIVE / UI

| # | Prueba | Pasos | Resultado esperado |
|---|---|---|---|
| 11.1 | Móvil (< 480px) | Reducir ventana | Sidebar colapsado, tablas responsivas, carrito desde abajo |
| 11.2 | Tablet (768px) | Ventana media | Layout adaptado |
| 11.3 | Modo oscuro | Toggle en header | Toda la app cambia a tema oscuro |
| 11.4 | Scroll en carrito | 10+ productos | Scroll vertical funcional |
| 11.5 | Scroll en modal gastos | Muchas compras | Scroll vertical funcional |
| 11.6 | Imagen no encontrada | Producto sin imagen | No rompe el layout (se oculta) |
| 11.7 | Toast notificaciones | Realizar acciones | Aparecen toasts con animación |

---

## 12. FLUJOS COMPLETOS (E2E)

### 12.1 Flujo completo: Cliente
```
1. Registro → Login
2. Ver productos disponibles
3. Añadir 3 productos al carrito (clic en imagen)
4. Ver carrito → ver total
5. Clic "Comprar Ahora"
6. Introducir tarjeta 4242 4242 4242 4242
7. Pago exitoso → toast + carrito vacío
8. Ver "Productos Comprados" → aparece la compra
9. Cerrar sesión
```

### 12.2 Flujo completo: Admin
```
1. Login como admin
2. Dashboard → ver incidencias, stock bajo, máquinas, gastos
3. Crear producto nuevo con imagen
4. Editar stock de un producto existente
5. Ir a Usuarios → crear un entrenador
6. Ir a Máquinas → crear máquina nueva
7. Ir a Incidencias → resolver una
8. Ir a Préstamos → aprobar uno pendiente
9. Cambiar a modo oscuro
10. Cerrar sesión
```

### 12.3 Flujo de seguridad: Entrenador no puede borrar
```
1. Login como entrenador
2. Ir a Productos → NO hay botón borrar
3. Ir a Máquinas → NO hay botón borrar
4. Ir a Incidencias → NO hay botón borrar
5. Ir a Usuarios → NO hay botón borrar
6. Ir a Préstamos → NO hay botón borrar
7. Ir a Usuarios → NO hay botón "Crear usuario"
```

### 12.4 Ataque simulado: Modificar precio
```
1. Login como cualquier usuario
2. DevTools → Network
3. Añadir producto al carrito
4. Clic "Comprar Ahora"
5. Ver petición POST /api/crear-payment-intent
6. Copiar como fetch, cambiar items[0].precio a 0.01
7. Ejecutar → backend devuelve clientSecret
8. Confirmar pago → Stripe cobra el precio REAL, no 0.01€
   (Verificar en dashboard de Stripe)
```

---

## Resumen para el tribunal

| Tipo | Total pruebas |
|---|---|
| Funcionales | ~60 |
| Seguridad | 10 |
| Responsive/UI | 7 |
| Flujos E2E | 4 |
| **Total** | **~80** |
