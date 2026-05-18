<?php
/*
 * Generador de la página de documentación de la API FitStock.
 * 
 * Este archivo define todos los recursos, endpoints y métodos HTTP
 * disponibles en la API, y los renderiza dentro de una plantilla HTML.
 * 
 * Os explico cómo funciona para que veáis cómo se genera la documentación
 * automáticamente a partir de un array de datos.
 */

/*
 * $resources: array principal que agrupa todos los recursos de la API.
 * Cada grupo tiene:
 *   - 'base': ruta base del grupo (vacío si los endpoints no comparten prefijo)
 *   - 'endpoints': lista de arrays con 4 elementos:
 *       [0] método HTTP (GET, POST, PUT, DELETE)
 *       [1] ruta completa del endpoint
 *       [2] descripción de lo que hace
 *       [3] nivel de autenticación requerido: 'any', 'all', 'admin', 'admin-ent'
 */
$resources = [
  '🔐 Autenticación' => [
    'base' => '',
    'endpoints' => [
      ['POST', '/api/login', 'Iniciar sesión con email y contraseña', 'any'],
      ['POST', '/api/logout', 'Cerrar sesión', 'all'],
      ['POST', '/api/registro', 'Registrar nuevo usuario (rol cliente)', 'any'],
    ]
  ],
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
    '🤝 Préstamos' => [
    'base' => '/api/prestamos',
    'endpoints' => [
      ['GET', '/api/prestamos', 'Obtener todos los préstamos', 'all'],
      ['POST', '/api/prestamos', 'Crear uno o varios préstamos', 'all'],
      ['PUT', '/api/prestamos/{id}', 'Actualizar fecha devolución o marcar como devuelto', 'all'],
      ['DELETE', '/api/prestamos/{id}', 'Eliminar un préstamo', 'admin-ent'],
    ]
  ],
    '⚠️ Incidencias' => [
    'base' => '/api/incidencias',
    'endpoints' => [
      ['GET', '/api/incidencias', 'Obtener todas las incidencias', 'all'],
      ['POST', '/api/incidencias', 'Crear una incidencia', 'all'],
      ['PUT', '/api/incidencias/{id}', 'Actualizar estado o datos de una incidencia', 'admin-ent'],
      ['DELETE', '/api/incidencias/{id}', 'Eliminar una incidencia', 'admin'],
    ]
  ],
    '📦 Productos' => [
    'base' => '/api/productos',
    'endpoints' => [
      ['GET', '/api/productos', 'Obtener todos los productos', 'all'],
      ['POST', '/api/productos', 'Crear un producto', 'admin-ent'],
      ['PUT', '/api/productos/{id}', 'Actualizar un producto', 'admin-ent'],
      ['DELETE', '/api/productos/{id}', 'Eliminar un producto', 'admin'],
    ]
  ],
    '🛒 Compras' => [
    'base' => '/api/compras',
    'endpoints' => [
      ['GET', '/api/compras', 'Obtener todas las compras', 'all'],
      ['POST', '/api/compras', 'Realizar una compra (vacía el carrito)', 'all'],
    ]
  ],
    '📧 Contacto' => [
    'base' => '/api/contacto',
    'endpoints' => [
      ['POST', '/api/contacto', 'Enviar mensaje de contacto (email + mensaje)', 'any'],
    ]
  ],
    '📊 Resumen' => [
    'base' => '/api/resumen',
    'endpoints' => [
      ['GET', '/api/resumen', 'Obtener resumen del dashboard (incidencias, stock bajo, máquinas)', 'admin-ent'],
    ]
  ],
    '💳 Pagos' => [
    'base' => '/api/crear-payment-intent',
    'endpoints' => [
      ['POST', '/api/crear-payment-intent', 'Crear un PaymentIntent de Stripe para pagar el carrito', 'all'],
    ]
  ],
];

/*
 * $authLabels: etiquetas de nivel de autenticación para los badges.
 * Cada entrada tiene [texto visible, clase CSS]:
 *   'any'      → sin autenticación (público)
 *   'all'      → cualquier usuario autenticado
 *   'admin'    → solo administradores
 *   'admin-ent' → administradores y entrenadores
 */
$authLabels = [
  'any' => ['Sin auth', 'auth-any'],
  'all' => ['Autenticado', 'auth-all'],
  'admin' => ['Admin', 'auth-admin'],
  'admin-ent' => ['Admin/Entrenador', 'auth-admin'],
];

/*
 * $methodColors: asociamos cada método HTTP con una clase CSS
 * para que cada uno tenga su color característico:
 *   GET    → azul
 *   POST   → verde
 *   PUT    → amarillo/naranja
 *   DELETE → rojo
 */
$methodColors = ['GET' => 'get', 'POST' => 'post', 'PUT' => 'put', 'DELETE' => 'delete'];

/*
 * Generación del HTML:
 * Iteramos sobre cada grupo de recursos y construimos el HTML
 * de sus endpoints. Luego inyectamos todo en la plantilla docs.html
 * reemplazando el marcador {{RESOURCES}}.
 */
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

/*
 * Cargamos la plantilla HTML y reemplazamos {{RESOURCES}} con el HTML
 * que acabamos de generar. Así separamos la lógica (PHP) de la presentación (HTML).
 */
$html = file_get_contents(__DIR__ . '/docs.html');
echo str_replace('{{RESOURCES}}', $rows, $html);