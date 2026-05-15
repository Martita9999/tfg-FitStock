<?php
/**
 * Genera la página de documentación de la API FitStock.
 * Define todos los recursos, endpoints y métodos HTTP disponibles,
 * y los renderiza sobre una plantilla HTML.
 */

// Array principal que agrupa todos los recursos de la API.
// Cada entrada tiene:
//   - 'base': ruta base del grupo (vacío si no aplica)
//   - 'endpoints': lista de arrays [método, ruta, descripción, auth]
$resources = [
  // Grupo: Autenticación — endpoints públicos sin ruta base compartida
  '🔐 Autenticación' => [
    'base' => '',
    'endpoints' => [
      ['POST', '/api/login', 'Iniciar sesión con email y contraseña', 'any'],
      ['POST', '/api/logout', 'Cerrar sesión', 'all'],
      ['POST', '/api/registro', 'Registrar nuevo usuario (rol cliente)', 'any'],
    ]
  ],
    // Grupo: Usuarios — gestión de cuentas y roles
    '👥 Usuarios' => [
    'base' => '/api/usuarios',
    'endpoints' => [
      ['GET', '/api/usuarios', 'Obtener todos los usuarios', 'all'],
      ['POST', '/api/usuarios', 'Crear un nuevo usuario', 'admin'],
      ['PUT', '/api/usuarios/{id}', 'Actualizar usuario (nombre, email, rol, password)', 'admin-ent'],
      ['PUT', '/api/usuarios/cambiar-password', 'Cambiar propia contraseña (con old_password)', 'all'],
      ['POST', '/api/usuarios/forzar-cambio', 'Forzar cambio de contraseña de un usuario', 'admin-ent'],
      ['DELETE', '/api/usuarios/{id}', 'Eliminar un usuario', 'admin'],
    ]
  ],
    // Grupo: Materiales — máquinas y material prestable del gimnasio
    '📦 Materiales' => [
    'base' => '/api/materiales',
    'endpoints' => [
      ['GET', '/api/materiales', 'Obtener todos los materiales', 'all'],
      ['GET', '/api/materiales?tipo=maquina', 'Filtrar por tipo (maquina, prestable)', 'all'],
      ['POST', '/api/materiales', 'Crear material(es)', 'admin-ent'],
      ['PUT', '/api/materiales/{id}', 'Actualizar un material', 'admin-ent'],
      ['DELETE', '/api/materiales/{id}', 'Eliminar un material', 'admin-ent'],
    ]
  ],
    // Grupo: Préstamos — registro y control de material prestado
    '🤝 Préstamos' => [
    'base' => '/api/prestamos',
    'endpoints' => [
      ['GET', '/api/prestamos', 'Obtener todos los préstamos', 'all'],
      ['POST', '/api/prestamos', 'Crear uno o varios préstamos', 'all'],
      ['PUT', '/api/prestamos/{id}', 'Actualizar fecha devolución o marcar como devuelto', 'all'],
      ['DELETE', '/api/prestamos/{id}', 'Eliminar un préstamo', 'admin-ent'],
    ]
  ],
    // Grupo: Incidencias — reporte de averías y problemas
    '⚠️ Incidencias' => [
    'base' => '/api/incidencias',
    'endpoints' => [
      ['GET', '/api/incidencias', 'Obtener todas las incidencias', 'all'],
      ['POST', '/api/incidencias', 'Crear una incidencia', 'all'],
      ['PUT', '/api/incidencias/{id}', 'Actualizar estado o datos de una incidencia', 'admin-ent'],
      ['DELETE', '/api/incidencias/{id}', 'Eliminar una incidencia', 'admin'],
    ]
  ],
    // Grupo: Productos — artículos disponibles para compra en la tienda
    '📦 Productos' => [
    'base' => '/api/productos',
    'endpoints' => [
      ['GET', '/api/productos', 'Obtener todos los productos', 'all'],
      ['POST', '/api/productos', 'Crear un producto', 'admin-ent'],
      ['PUT', '/api/productos/{id}', 'Actualizar un producto', 'admin-ent'],
      ['DELETE', '/api/productos/{id}', 'Eliminar un producto', 'admin'],
    ]
  ],
    // Grupo: Compras — procesamiento de pedidos desde el carrito
    '🛒 Compras' => [
    'base' => '/api/compras',
    'endpoints' => [
      ['GET', '/api/compras', 'Obtener todas las compras', 'all'],
      ['POST', '/api/compras', 'Realizar una compra (vacía el carrito)', 'all'],
    ]
  ],
    // Grupo: Contacto — formulario público de contacto
    '📧 Contacto' => [
    'base' => '/api/contacto',
    'endpoints' => [
      ['POST', '/api/contacto', 'Enviar mensaje de contacto (email + mensaje)', 'any'],
    ]
  ],
    // Grupo: Resumen — datos agregados para el dashboard principal
    '📊 Resumen' => [
    'base' => '/api/resumen',
    'endpoints' => [
      ['GET', '/api/resumen', 'Obtener resumen del dashboard (incidencias, stock bajo, máquinas)', 'admin-ent'],
    ]
  ],
];

/**
 * Etiquetas de nivel de autenticación para mostrar en los badges.
 * Clave → [texto visible, clase CSS]
 *   any      → sin autenticación requerida
 *   all      → cualquier usuario autenticado
 *   admin    → solo administradores
 *   admin-ent → administradores y entrenadores
 */
$authLabels = [
  'any' => ['Sin auth', 'auth-any'],
  'all' => ['Autenticado', 'auth-all'],
  'admin' => ['Admin', 'auth-admin'],
  'admin-ent' => ['Admin/Entrenador', 'auth-admin'],
];

// Asocia cada método HTTP con su clase de color CSS
$methodColors = ['GET' => 'get', 'POST' => 'post', 'PUT' => 'put', 'DELETE' => 'delete'];

$rows = '';
foreach ($resources as $title => $res) {
  $eps = '';
  foreach ($res['endpoints'] as $ep) {
    [$method, $route, $desc, $auth] = $ep;
    $color = $methodColors[$method];
    [$authLabel, $authClass] = $authLabels[$auth];
    $eps .= <<<ROW
      <div class="endpoint"><span class="method {$color}">{$method}</span><span class="route">{$route}</span><span class="desc">{$desc}</span><span class="tags"><span class="auth {$authClass}">{$authLabel}</span></span></div>
ROW;
  }
  $base = $res['base'] ? " <small>{$res['base']}</small>" : '';
  $rows .= <<<RES
  <div class="resource">
    <h2>{$title}{$base}</h2>
    {$eps}
  </div>

RES;
}

// Carga la plantilla HTML y reemplaza el marcador {{RESOURCES}} con el contenido generado
$html = file_get_contents(__DIR__ . '/docs.html');
echo str_replace('{{RESOURCES}}', $rows, $html);