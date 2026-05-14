from docx import Document
from docx.shared import Pt, Inches, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT

doc = Document()

style = doc.styles['Normal']
font = style.font
font.name = 'Calibri'
font.size = Pt(11)

for _ in range(6):
    doc.add_paragraph()

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
run = p.add_run('MEMORIA DE CAMBIOS\nDespliegue FitStock')
run.bold = True
run.font.size = Pt(24)
run.font.color.rgb = RGBColor(0x1F, 0x4E, 0x79)

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
run = p.add_run('Migración de Angular SSR a SPA Estático\nConfiguración de API en Producción')
run.font.size = Pt(14)
run.font.color.rgb = RGBColor(0x4A, 0x4A, 0x4A)

doc.add_page_break()

doc.add_heading('Índice', level=1)
items = [
    '1. Resumen de los cambios realizados',
    '2. Cambio 1: URLs de los servicios Angular (localhost -> producción)',
    '3. Cambio 2: Desactivación de SSR (Server-Side Rendering)',
    '4. Cambio 3: Actualización del router.php',
    '5. Cambio 4: Actualización de api/index.php (routing desde subcarpeta)',
    '6. Cambio 5: Corrección de URLs de imágenes de productos',
    '7. Cambio 6: Corrección de ruta de subida de imágenes',
    '8. Cambio 7: Build de producción sin SSR',
    '9. Cambio 8: Añadir RewriteBase al .htaccess (error al refrescar)',
    '10. Archivos a subir por FTP',
    '11. Estructura final del servidor',
]
for item in items:
    p = doc.add_paragraph(item)
    p.paragraph_format.space_after = Pt(4)

doc.add_page_break()

doc.add_heading('1. Resumen de los cambios realizados', level=1)
doc.add_paragraph(
    'Se han realizado los siguientes cambios en el proyecto FitStock para '
    'desplegarlo correctamente en el hosting de Arsys (plan compartido Linux), '
    'con la aplicación alojada en una subcarpeta /API/:'
)

cambios = [
    ('Actualización de URLs de API',
     'Todos los servicios Angular apuntaban a http://localhost:8000/api '
     '(entorno de desarrollo). Se ha cambiado a https://chomsky.es/API/api '
     '(entorno de producción con subcarpeta).'),
    ('Desactivación de SSR',
     'La aplicación usaba Angular SSR, que requiere Node.js en el servidor. '
     'Se ha compilado como SPA estático con --no-server.'),
    ('Actualización del router.php',
     'Modificado para que sirva index.html de Angular en rutas no-API '
     'y para que reconozca peticiones /API/api (case-insensitive).'),
    ('Actualización de api/index.php',
     'Añadido case "API" y normalización de rutas para funcionar desde '
     'la subcarpeta /API/.'),
    ('Corrección de URLs de imágenes',
     'Cambiado de ruta absoluta /images/productos/ a relativa '
     'images/productos/ para que funcione con el base-href /API/.'),
    ('Corrección de ruta de subida de imágenes',
     'Actualizada la ruta donde se guardan las imágenes al subirlas '
     'desde la app para que apunte a la carpeta correcta del servidor.'),
    ('Actualización de la base de datos',
     'Añadidos datos de ejemplo (materiales, productos, incidencias) '
     'al archivo databasearsys.sql.'),
]
for titulo, desc in cambios:
    p = doc.add_paragraph()
    run = p.add_run(f'{titulo}: ')
    run.bold = True
    p.add_run(desc)

doc.add_page_break()
doc.add_heading('2. Cambio 1: URLs de los servicios Angular', level=1)
doc.add_paragraph(
    'Todos los servicios de Angular tenían la URL http://localhost:8000/api '
    '(entorno de desarrollo). Se ha cambiado a https://chomsky.es/API/api '
    'para que apunten al servidor de producción, teniendo en cuenta que '
    'la aplicación está dentro de la subcarpeta /API/.'
)

doc.add_heading('Archivos modificados (7 servicios):', level=2)

servicios = [
    ('compras.service.ts', 'private API_URL = \'http://localhost:8000/api\''),
    ('incidencias.service.ts', 'private API_URL = \'http://localhost:8000/api\''),
    ('materiales.service.ts', 'private API_URL = \'http://localhost:8000/api\''),
    ('prestamos.service.ts', 'private API_URL = \'http://localhost:8000/api\''),
    ('productos.service.ts', 'private API_URL = \'http://localhost:8000/api\''),
    ('resumen.service.ts', 'private API_URL = \'http://localhost:8000/api\''),
    ('usuario.ts', 'private API_URL = \'http://localhost:8000/api\''),
]

table = doc.add_table(rows=1, cols=2)
table.style = 'Light Shading Accent 1'
table.alignment = WD_TABLE_ALIGNMENT.CENTER
hdr = table.rows[0].cells
hdr[0].text = 'Archivo (src/app/services/)'
hdr[1].text = 'Cambio realizado'
for archivo, linea in servicios:
    row = table.add_row().cells
    row[0].text = archivo
    row[1].text = 'http://localhost:8000/api → https://chomsky.es/API/api'

doc.add_page_break()
doc.add_heading('3. Cambio 2: Desactivación de SSR', level=1)
doc.add_paragraph(
    'La aplicación Angular estaba configurada con SSR. Como el hosting '
    'compartido de Arsys no ejecuta Node.js, se compila como SPA estático.'
)
doc.add_paragraph(
    'Comando utilizado:\n\n  npm run build -- --no-server --base-href /API/\n\n'
    'La flag --no-server evita generar archivos de servidor Node.js.\n'
    'La flag --base-href /API/ asegura que las rutas absolutas de Angular '
    'funcionen desde la subcarpeta /API/.'
)

doc.add_page_break()
doc.add_heading('4. Cambio 3: Actualización del router.php', level=1)
doc.add_paragraph(
    'El router.php es el punto de entrada de todas las peticiones HTTP. '
    'Se han realizado dos cambios respecto a la versión original:'
)

doc.add_heading('4.1. Regex para detectar /api (case-insensitive)', level=2)
doc.add_paragraph(
    'Línea 31. Antes:\n'
    '  preg_match(\'#^/api#\', $uri)\n\n'
    'Ahora:\n'
    '  preg_match(\'#/api(/|$)#i\', $uri)\n\n'
    'Esto permite detectar /api tanto en rutas como /api/productos como '
    'en /API/api/productos (case-insensitive).'
)

doc.add_heading('4.2. Fallback para Angular SPA', level=2)
doc.add_paragraph(
    'Líneas 36-43. Se añadió la sección "Fallback: servir index.html de '
    'Angular". Antes de mostrar la documentación, busca index.html de '
    'Angular en la raíz y lo sirve. Esto permite que rutas como /login, '
    '/admin/inventario, etc. funcionen con el enrutamiento interno de Angular.'
)

doc.add_page_break()
doc.add_heading('5. Cambio 4: Actualización de api/index.php', level=1)
doc.add_paragraph(
    'El api/index.php es el enrutador interno de la API. Se han realizado '
    'dos cambios para que funcione desde la subcarpeta /API/:'
)

doc.add_heading('5.1. Aceptar "API" como action válido', level=2)
doc.add_paragraph(
    'Línea 56. Se añadió:\n'
    '  case \'API\':\n\n'
    'Justo debajo de case \'api\':. Cuando la app está en /API/, '
    'el primer segmento de la ruta es "API" (mayúsculas), no "api".'
)

doc.add_heading('5.2. Normalización de rutas', level=2)
doc.add_paragraph(
    'Líneas 34-36. Se añadió lógica para detectar y eliminar el prefijo '
    '"API" del array de rutas:\n\n'
    '  if (strtoupper($path[0] ?? \'\') === \'API\' && strtolower($path[1] ?? \'\') === \'api\') {\n'
    '      array_shift($path);\n'
    '  }\n\n'
    'Esto normaliza rutas como /API/api/productos a /api/productos, '
    'dejando el array en el formato que espera el resto del código '
    '(path[0]=api, path[1]=recurso, path[2]=ID).'
)

doc.add_page_break()
doc.add_heading('6. Cambio 5: URLs de imágenes de productos', level=1)
doc.add_paragraph(
    'En el componente producto-list.component.ts se corrigió la función '
    'getImagenUrl() que construye la URL de las imágenes de productos.'
)

doc.add_heading('Archivo:', level=2)
doc.add_paragraph('src/app/components/producto-list/producto-list.ts, línea 226')

doc.add_heading('Antes:', level=2)
p = doc.add_paragraph()
run = p.add_run('return \'/images/productos/\' + encodeURIComponent(nombre) + \'.jpg\';')
run.font.name = 'Consolas'
run.font.size = Pt(10)

doc.add_heading('Después:', level=2)
p = doc.add_paragraph()
run = p.add_run('return \'images/productos/\' + encodeURIComponent(nombre) + \'.jpg\';')
run.font.name = 'Consolas'
run.font.size = Pt(10)

doc.add_paragraph(
    'La diferencia es la barra inicial (/). Con la barra, la URL es '
    'absoluta desde la raíz del dominio (/images/productos/...), pero '
    'la app está en /API/, por lo que no encontraba las imágenes. '
    'Sin la barra, la URL es relativa al base-href /API/, resolviéndose '
    'como /API/images/productos/...'
)

doc.add_page_break()
doc.add_heading('7. Cambio 6: Ruta de subida de imágenes', level=1)
doc.add_paragraph(
    'En api/index.php se corrigió la ruta donde se guardan las imágenes '
    'al subirlas desde la aplicación.'
)

doc.add_heading('Archivo:', level=2)
doc.add_paragraph('FitStock-API/api/index.php, línea 386')

doc.add_heading('Antes:', level=2)
p = doc.add_paragraph()
run = p.add_run('$uploadDir = __DIR__ . \'/../../FitStock-APP/public/images/productos/\';')
run.font.name = 'Consolas'
run.font.size = Pt(10)

doc.add_heading('Después:', level=2)
p = doc.add_paragraph()
run = p.add_run('$uploadDir = __DIR__ . \'/../images/productos/\';')
run.font.name = 'Consolas'
run.font.size = Pt(10)

doc.add_paragraph(
    'La ruta anterior estaba diseñada para el entorno de desarrollo local '
    '(Docker). En el servidor de producción, las imágenes se almacenan en '
    'la carpeta /html/API/images/productos/, que es donde las busca el '
    'frontend Angular.'
)

doc.add_page_break()
doc.add_heading('8. Cambio 7: Build de producción sin SSR', level=1)
doc.add_paragraph(
    'Comando ejecutado:\n\n'
    '  npm run build -- --no-server --base-href /API/\n\n'
    'Archivos generados en dist/fitStock-front/browser/:'
)

table = doc.add_table(rows=1, cols=2)
table.style = 'Light Shading Accent 1'
table.alignment = WD_TABLE_ALIGNMENT.CENTER
hdr = table.rows[0].cells
hdr[0].text = 'Archivo'
hdr[1].text = 'Descripción'
files = [
    ('index.html', 'Punto de entrada de Angular'),
    ('main-*.js', 'Código JavaScript principal (~500 KB)'),
    ('polyfills-*.js', 'Polyfills para compatibilidad (~35 KB)'),
    ('styles-*.css', 'Estilos globales (~16 KB)'),
    ('favicon.ico', 'Icono de navegador'),
    ('icono.jpg', 'Icono adicional'),
    ('images/', 'Carpeta con imágenes (productos, etc.)'),
]
for archivo, desc in files:
    row = table.add_row().cells
    row[0].text = archivo
    row[1].text = desc

doc.add_page_break()
doc.add_heading('8. Cambio 7: Añadir RewriteBase al .htaccess', level=1)
doc.add_paragraph(
    'Al refrescar cualquier página de Angular (F5 o recargar), el navegador '
    'envía la ruta exacta al servidor (ej: /API/admin/inventario). El servidor '
    'busca esa ruta en el sistema de archivos y, al no encontrarla, debe '
    'redirigir al router.php para que sirva el index.html de Angular.'
)
doc.add_paragraph(
    'Sin la directiva RewriteBase, Apache no resuelve correctamente las '
    'rutas relativas del .htaccess cuando se usa desde una subcarpeta, '
    'devolviendo un error 404 al refrescar cualquier ruta interna de Angular.'
)

doc.add_heading('Archivo:', level=2)
doc.add_paragraph('FitStock-API/.htaccess, línea 2')

doc.add_heading('Antes:', level=2)
p = doc.add_paragraph()
run = p.add_run("""RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ router.php [QSA,L]""")
run.font.name = 'Consolas'
run.font.size = Pt(10)

doc.add_heading('Después:', level=2)
p = doc.add_paragraph()
run = p.add_run("""RewriteEngine On
RewriteBase /API/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ router.php [QSA,L]""")
run.font.name = 'Consolas'
run.font.size = Pt(10)

doc.add_paragraph(
    'La línea RewriteBase /API/ le indica a Apache que todas las reglas '
    'del .htaccess deben interpretarse relativas a la subcarpeta /API/, '
    'no a la raíz del dominio. Esto permite que al refrescar cualquier '
    'ruta de Angular (login, admin/inventario, etc.), Apache redirija '
    'correctamente a router.php y este sirva el index.html.'
)

doc.add_page_break()
doc.add_heading('9. Archivos a subir por FTP', level=1)

doc.add_heading('Paso 1: Angular (a /html/API/)', level=2)
doc.add_paragraph(
    'Desde dist/fitStock-front/browser/:\n'
    '  • index.html\n'
    '  • main-*.js\n'
    '  • polyfills-*.js\n'
    '  • styles-*.css\n'
    '  • favicon.ico\n'
    '  • icono.jpg\n'
    '  • images/ (carpeta completa)'
)

doc.add_heading('Paso 2: API actualizada (a /html/API/)', level=2)
doc.add_paragraph('  • router.php (desde FitStock-API/router.php)\n'
    '  • api/index.php (desde FitStock-API/api/index.php)')

doc.add_heading('Paso 3: Imágenes de productos (a /html/API/images/productos/)', level=2)
doc.add_paragraph('  • FitStock-APP/public/images/productos/*.jpg')

doc.add_heading('Paso 4: Base de datos', level=2)
doc.add_paragraph(
    '  • Importar FitStock-API/config/databasearsys.sql en phpMyAdmin'
)

doc.add_page_break()
doc.add_heading('10. Estructura final del servidor', level=1)

estructura = """/html/
└── API/
    ├── .htaccess              # Redirección a router.php
    ├── router.php             # Router PHP (modificado)
    ├── index.html             # Angular (NUEVO)
    ├── main-*.js              # Angular (NUEVO)
    ├── polyfills-*.js         # Angular (NUEVO)
    ├── styles-*.css           # Angular (NUEVO)
    ├── favicon.ico            # Angular (NUEVO)
    ├── icono.jpg              # Angular (NUEVO)
    ├── images/                # Angular (NUEVO)
    │   └── productos/         # Imágenes de productos
    │       ├── *.jpg
    │       └── ...
    ├── api/                   # Backend PHP
    │   ├── index.php          # (modificado)
    │   └── ...
    ├── conexion.php           # Conexión BD
    ├── config/                # Configuración
    ├── docs/                  # Documentación
    └── models/                # Modelos de datos"""

p = doc.add_paragraph()
run = p.add_run(estructura)
run.font.name = 'Consolas'
run.font.size = Pt(9)

doc.save('C:\\TFG\\tfg-FitStock\\documentacion\\Cambios_Despliegue_FitStock.docx')
print('Documento generado correctamente.')
