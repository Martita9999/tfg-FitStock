# Guía de Instalación - FitStock

## Requisitos previos

| Software | Versión | Descarga |
|----------|---------|----------|
| XAMPP | 8.x | https://www.apachefriends.org/es/index.html |
| Node.js | 22.x LTS | https://nodejs.org/ |
| Git | Última | https://git-scm.com/ |
| Visual Studio Code | (opcional) | https://code.visualstudio.com/ |

## 1. Clonar el repositorio

```bash
git clone <URL_DEL_REPOSITORIO>
cd tfg-FitStock
```

O descargar el ZIP y extraerlo.

---

## 2. Configurar la base de datos (MySQL con XAMPP)

### 2.1 Iniciar MySQL en XAMPP
1. Abrir **XAMPP Control Panel**
2. Hacer clic en **Start** junto a **Apache** y **MySQL**
3. Verificar que aparezca el puerto 3306 en MySQL

### 2.2 Acceder a phpMyAdmin
1. Abrir navegador → `http://localhost/phpmyadmin`
2. Usuario: `root` | Contraseña: (dejar vacío)

### 2.3 Importar la base de datos
1. En phpMyAdmin, clic en **Nueva** para crear BD
2. Nombre: `fitstock`, cotejamiento: `utf8mb4_unicode_ci`
3. Seleccionar la BD `fitstock` → pestaña **SQL**
4. Copiar el contenido del archivo `FitStock-API\config\database.sql` y pegarlo
5. Clic en **Continuar**

### 2.4 Crear el usuario de aplicación (alternativa si el SQL falla)
Ejecutar esta consulta SQL en phpMyAdmin:
```sql
CREATE USER IF NOT EXISTS 'fitstock'@'localhost' IDENTIFIED BY 'Tokio2324';
GRANT ALL ON fitstock.* TO 'fitstock'@'localhost';
FLUSH PRIVILEGES;
```

---

## 3. Configurar la conexión de la API

El archivo `FitStock-API\conexion.php` ya viene configurado para XAMPP:
```php
$DB_HOST = "127.0.0.1";
$DB_NAME = "fitstock";
$DB_USER = "fitstock";
$DB_PASS = "Tokio2324";
```

Si usas XAMPP con MySQL sin contraseña para root, cambia a:
```php
$DB_USER = "root";
$DB_PASS = "";
```

---

## 4. Instalar Node.js y Angular CLI

### 4.1 Instalar Node.js
1. Descargar Node.js 22.x LTS desde https://nodejs.org/
2. Ejecutar el instalador (marcar todas las opciones por defecto)
3. Verificar la instalación:
   ```bash
   node --version
   npm --version
   ```

### 4.2 Instalar Angular CLI globalmente
```bash
npm install -g @angular/cli
```

Verificar:
```bash
ng version
```

---

## 5. Instalar dependencias del frontend

```bash
cd FitStock-APP
npm install
```

Este comando instalará todas las dependencias del `package.json` (Angular y librerías).

---

## 6. Ejecutar la aplicación

### 6.1 Iniciar la API (PHP con Apache de XAMPP)

**Opción A — Usando Apache de XAMPP (recomendado):**
1. Copiar la carpeta `FitStock-API` dentro de `C:\xampp\htdocs\`
2. La API estará disponible en: `http://localhost/FitStock-API/api/...`
3. **IMPORTANTE**: Actualizar `usuario.ts` con la nueva URL:
   ```
   // Cambiar de:
   private API_URL = 'http://localhost:8000/api';
   // A:
   private API_URL = 'http://localhost/FitStock-API/api';
   ```
4. También actualizar `resumen.service.ts` con la misma URL

**Opción B — Usando servidor PHP integrado (si prefieres):**
```bash
cd FitStock-API
php -S localhost:8000 router.php
```

### 6.2 Iniciar el frontend (Angular)
```bash
cd FitStock-APP
ng serve
```

Esto iniciará Angular en: `http://localhost:4200`

---

## 7. Acceder a la aplicación

- **Frontend**: `http://localhost:4200`
- **API** (opción A): `http://localhost/FitStock-API/api`
- **API** (opción B): `http://localhost:8000/api`
- **phpMyAdmin**: `http://localhost/phpmyadmin`

### Usuarios de prueba
| Email | Contraseña | Rol |
|-------|-----------|-----|
| admin@fitstock.com | password | Admin |
| carlos@gym.com | password | Entrenador |
| ilsa@fitstock.com | password | Cliente |
| marta@fitstock.com | password | Entrenador |

---

## 8. Solución de problemas

### Error "No se puede conectar a la base de datos"
- Verificar que MySQL esté iniciado en XAMPP
- Comprobar que el puerto 3306 no esté ocupado
- Probar con usuario `root` y contraseña vacía en `conexion.php`

### Error "npm install" falla
- Actualizar npm: `npm install -g npm@latest`
- Borrar `node_modules` y `package-lock.json`, luego reintentar

### Error CORS (API no responde desde Angular)
- Si usas Apache, las cabeceras CORS ya están en `index.php`
- Si el problema persiste, abrir `http://localhost/FitStock-API/api/` en el navegador para verificar que la API responde

### Puerto 4200 ocupado
```bash
ng serve --port 4201
```

---

## 9. Resumen de comandos rápidos (cada vez que quieras iniciar)

```bash
# 1. Iniciar Apache y MySQL desde XAMPP Control Panel

# 2. Terminal 1 — API
cd C:\xampp\htdocs\FitStock-API
php -S localhost:8000 router.php

# 3. Terminal 2 — Frontend
cd ruta\a\tfg-FitStock\FitStock-APP
ng serve

# 4. Abrir http://localhost:4200
```
