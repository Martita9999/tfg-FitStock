# Iconos SVG de la Landing Page (PortalComponent)

Se usaron 6 iconos SVG inline (sin librerías externas) en `portal.html`, cada uno representando una funcionalidad del sistema.

## ¿Qué es SVG?

**SVG** (*Scalable Vector Graphics* — Gráficos Vectoriales Escalables) es un formato de imagen vectorial basado en XML que permite dibujar gráficos mediante primitivas geométricas (rectángulos, círculos, líneas, trazados, etc.). A diferencia de formatos raster como PNG o JPG, SVG no pierde calidad al escalarse porque las imágenes se definen matemáticamente, no por píxeles. Al ser código XML, los SVG pueden incrustarse directamente en HTML y manipularse con CSS y JavaScript (cambiar colores, tamaños, animar, etc.).

---

## 1. Productos — Estante/Caja (`viewBox="0 0 24 24"`)

```
<rect x="3" y="3" width="18" height="18" rx="2" />   ← estantería o caja
<path d="M3 9h18" />                                  ← balda divisoria
<path d="M9 21V9" />                                  ← separador vertical / producto
```
Representa un estante con productos apilados. Alude a la gestión de inventario de suplementos.

---

## 2. Gestión de Máquinas — Máquina multipower (`viewBox="0 0 24 24"`)

```
<path d="M6.5 6.5h11" />                              ← barra superior ajustable
<path d="M6 12h12" />                                 ← barra media ajustable
<path d="M7.5 17.5h9" />                              ← barra inferior ajustable
<rect x="4" y="3" width="16" height="18" rx="2" />   ← estructura de la máquina
```
Simula una máquina de gimnasio tipo multipower con barras horizontales que representan los puntos de ajuste.

---

## 3. Préstamos — Barra con discos (`viewBox="0 0 36 36"`)

```
<rect x="2"  y="4" width="8" height="28" rx="2" />   ← disco izquierdo
<rect x="26" y="4" width="8" height="28" rx="2" />   ← disco derecho
<rect x="12" y="13" width="12" height="10" rx="1.5" /> ← agarre central
<line x1="2"  y1="9"  x2="10" y2="9"  />              ← detalle disco izq
<line x1="2"  y1="17" x2="10" y2="17" />              ← detalle disco izq
<line x1="2"  y1="25" x2="10" y2="25" />              ← detalle disco izq
<line x1="26" y1="9"  x2="34" y2="9"  />              ← detalle disco der
<line x1="26" y1="17" x2="34" y2="17" />              ← detalle disco der
<line x1="26" y1="25" x2="34" y2="25" />              ← detalle disco der
```
Representa una barra de pesas con discos a los lados. Usa `viewBox="0 0 36 36"` por ser más ancha que alta. Alude al préstamo de material deportivo.

---

## 4. Incidencias — Alerta (`viewBox="0 0 24 24"`)

```
<circle cx="12" cy="12" r="10" />                     ← círculo exterior
<line x1="12" y1="8" x2="12" y2="12" />              ← palo del signo de exclamación
<line x1="12" y1="16" x2="12.01" y2="16" />          ← punto del signo de exclamación
```
Icono de alerta/notificación. Representa incidencias y puntos críticos en máquinas.

---

## 5. Usuarios y Roles — Grupo de personas (`viewBox="0 0 24 24"`)

```
<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /> ← torso usuario 1
<circle cx="9" cy="7" r="4" />                          ← cabeza usuario 1
<path d="M23 21v-2a4 4 0 0 0-3-3.87" />                ← torso usuario 2
<path d="M16 3.13a4 4 0 0 1 0 7.75" />                 ← cabeza usuario 2
```
Dos siluetas de personas superpuestas. Simboliza la gestión de múltiples usuarios y roles.

---

## 6. Dashboard — Gráfico de barras (`viewBox="0 0 24 24"`)

```
<line x1="18" y1="20" x2="18" y2="10" />              ← barra derecha
<line x1="12" y1="20" x2="12" y2="4"  />              ← barra central (más alta)
<line x1="6"  y1="20" x2="6"  y2="14" />              ← barra izquierda
```
Tres barras de diferentes alturas que forman un gráfico estadístico. Representa el dashboard con métricas y datos clave.

---

## Notas técnicas

- **Formato**: SVG inline (incrustado directamente en el HTML, sin archivos externos).
- **Color**: heredan `currentColor` y se pintan con la clase `.feature-icon { color: #f97316; }` (naranja).
- **Atributos comunes**: `fill="none"`, `stroke-width="1.5"`, `stroke-linecap="round"`, `stroke-linejoin="round"`.
- **Tamaño**: `width="48" height="48"` (salvo el de préstamos que usa 48×48 con viewBox 36×36).
- **Ventaja**: al ser inline, se renderizan sin peticiones HTTP adicionales y se pueden estilizar con CSS.
