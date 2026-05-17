# Exportación CSV y PDF

## Resumen

La aplicación implementa exportación a **CSV** y **PDF** en dos componentes:
- **IncidenciaListComponent** (incidencias resueltas)
- **PrestamoListComponent** (préstamos completados)

Ambos componentes muestran botones "Excel" (CSV) y "PDF" en la interfaz.

---

## CSV (Excel)

### Cómo funciona

No se usa ninguna librería externa. El CSV se genera manualmente:

1. Se mapean los datos a un array de filas con los valores como strings
2. Se construye el contenido CSV con cabeceras + filas, separando con comas
3. Cada valor se envuelve en comillas dobles (`"valor"`) para escapar caracteres especiales
4. Se añade el BOM UTF-8 (`\uFEFF`) al inicio para compatibilidad con Excel y caracteres españoles (tildes, ñ, etc.)
5. Se crea un `Blob` con tipo `text/csv;charset=utf-8;`
6. Se genera un enlace temporal (`<a>`) con `URL.createObjectURL()` y se hace clic para descargar
7. Se libera la URL con `URL.revokeObjectURL()`

### Código base (incidencia-list.ts)

```typescript
exportarCSV() {
  const filas = this.incidenciasResueltas.map(inc => [
    inc.id,
    inc.nombre_material,
    inc.ubicacion,
    inc.descripcion,
    inc.prioridad,
    inc.estado,
    inc.created_at ? new Date(inc.created_at).toLocaleDateString() : '',
    inc.fecha_resolucion ? new Date(inc.fecha_resolucion).toLocaleDateString() : ''
  ]);
  const cabeceras = ['ID', 'Máquina', 'Ubicación', 'Descripción', 'Prioridad', 'Estado', 'Inicio', 'Fin'];
  const csv = [cabeceras.join(','), ...filas.map(f => f.map(v => `"${v}"`).join(','))].join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `incidencias_resueltas_${new Date().toISOString().slice(0,10)}.csv`;
  link.click();
  URL.revokeObjectURL(link.href);
}
```

### Columnas exportadas

| Componente | Columnas |
|---|---|
| Incidencias | ID, Máquina, Ubicación, Descripción, Prioridad, Estado, Inicio, Fin |
| Préstamos | ID, Material, Usuario, Fecha Préstamo, Fecha Devolución |

### Nombre de archivo

- Incidencias: `incidencias_resueltas_YYYY-MM-DD.csv`
- Préstamos: `prestamos_completados_YYYY-MM-DD.csv`

### HTML (ambos componentes)

```html
<div class="export-buttons">
  <button class="btn-export" (click)="exportarCSV()" title="Descargar Excel">
    <svg ...>...</svg>
    Excel
  </button>
  <button class="btn-export" (click)="exportarPDF()" title="Descargar PDF">
    <svg ...>...</svg>
    PDF
  </button>
</div>
```

---

## PDF

### Cómo funciona

Usa las librerías **jsPDF** y **jspdf-autotable**:

1. Se crea una instancia de `new jsPDF()`
2. Se mapean los datos a un array de filas
3. Se llama a `autoTable(doc, { head: [...], body: [...], styles: { fontSize: 8 } })` para dibujar la tabla
4. Se guarda con `doc.save('nombre.pdf')`

### Dependencias

```json
"jspdf": "^4.2.1",
"jspdf-autotable": "^5.0.7"
```

### Instalación

```bash
npm install jspdf jspdf-autotable
```

### Importación en el componente

```typescript
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
```

### Código base (incidencia-list.ts)

```typescript
exportarPDF() {
  const doc = new jsPDF();
  const filas = this.incidenciasResueltas.map(inc => [
    inc.id.toString(),
    inc.nombre_material || '—',
    inc.ubicacion || '—',
    inc.descripcion,
    inc.prioridad,
    inc.estado,
    inc.created_at ? new Date(inc.created_at).toLocaleDateString() : '',
    inc.fecha_resolucion ? new Date(inc.fecha_resolucion).toLocaleDateString() : ''
  ]);
  autoTable(doc, {
    head: [['ID', 'Máquina', 'Ubicación', 'Descripción', 'Prioridad', 'Estado', 'Inicio', 'Fin']],
    body: filas,
    styles: { fontSize: 8 }
  });
  doc.save(`incidencias_resueltas_${new Date().toISOString().slice(0,10)}.pdf`);
}
```

---

## Archivos involucrados

| Ruta | Función |
|---|---|
| `src/app/components/incidencia-list/incidencia-list.ts` | Lógica CSV y PDF (incidencias) |
| `src/app/components/incidencia-list/incidencia-list.html` | Botones de exportación (incidencias) |
| `src/app/components/incidencia-list/incidencia-list.css` | Estilos `.btn-export`, `.export-buttons` |
| `src/app/components/prestamo-list/prestamo-list.ts` | Lógica CSV y PDF (préstamos) |
| `src/app/components/prestamo-list/prestamo-list.html` | Botones de exportación (préstamos) |
| `src/app/components/prestamo-list/prestamo-list.css` | Estilos `.btn-export`, `.export-buttons` |
| `package.json` | Dependencias `jspdf`, `jspdf-autotable` |

---

## Notas importantes

- Solo se exportan datos **resueltos/completados**, no activos
- El CSV lleva BOM UTF-8 para que Excel interprete correctamente los caracteres especiales
- El PDF usa tamaño de fuente 8pt para tablas compactas
- Los botones muestran "Excel" para CSV y "PDF" para PDF
