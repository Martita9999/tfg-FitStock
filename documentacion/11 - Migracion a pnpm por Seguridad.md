# Migración de npm a pnpm por Seguridad

## Fecha
Mayo 2026

## Contexto
El proyecto FitStock-APP usaba **npm** como gestor de paquetes. Debido a graves incidentes de seguridad en el ecosistema npm durante 2025-2026, se decidió migrar a **pnpm v10+** por sus mejores garantías de seguridad.

---

## 1. Incidente de seguridad en npm: Shai-Hulud (Septiembre 2025)

### ¿Qué ocurrió?
Un ataque masivo a la cadena de suministro (supply chain) comprometió el registro npm. El gusano **Shai-Hulud**:
- Se propagó a través de credenciales robadas de mantenedores de paquetes
- Comprometió **más de 500 paquetes** populares (`chalk`, `debug`, `nx`, entre otros)
- Se infiltró hasta en paquetes de **CrowdStrike**
- Robaba tokens de GitHub, npm, AWS, GCP, Azure y otras credenciales
- Se autopropagaba: al encontrar tokens npm, publicaba versiones maliciosas automáticamente

### Gravedad
- **CISA** (Cybersecurity and Infrastructure Security Agency de EE.UU.) emitió una alerta oficial el 23/09/2025
- Paquetes afectados sumaban **2.600 millones de descargas semanales**
- El gusano clonaba repositorios privados y los hacía públicos
- Se considera el ataque más severo a la cadena de suministro de JavaScript hasta la fecha

### Incidentes adicionales
- **Agosto 2025**: Ataque S1ngularity/Nx — robo de token de publicación de Nx
- **Marzo 2026**: Secuestro del paquete `axios` (100M descargas/semana)
- Durante 2025, Sonatype identificó **454.600+ paquetes maliciosos nuevos** en npm (>99% del total en todos los ecosistemas)

---

## 2. ¿Por qué pnpm es más seguro?

La mayoría de estos ataques explotaban **scripts `postinstall`**: código arbitrario que se ejecuta automáticamente al instalar un paquete, sin intervención del desarrollador.

### Comparativa de seguridad

| Característica | npm 11 | pnpm v10+ |
|---|---|---|
| Bloqueo de lifecycle scripts por defecto | ❌ No (hay que usar `--ignore-scripts`) | ✅ Sí |
| `minimumReleaseAge` (bloquear paquetes recién publicados) | ❌ No | ✅ Sí |
| `trustPolicy` (bloquear degradación de confianza) | ❌ No | ✅ Sí |
| `blockExoticSubdeps` (bloquear dependencias exóticas) | ❌ No | ✅ Sí |
| Estructura `node_modules` estricta (sin phantom deps) | ❌ Hoisting plano | ✅ Estricta |
| Espacio en disco | ❌ Duplicado | ✅ 75% menos |
| Velocidad de instalación | Lento (14.3s cold) | Rápido (4.2s cold) |

### Las 3 protecciones clave de pnpm

1. **`allowBuilds`** (antiguo `onlyBuiltDependencies`):
   - Solo los paquetes explícitamente permitidos pueden ejecutar scripts `postinstall`
   - Si un paquete no necesitaba build antes, una versión comprometida no podrá ejecutar código malicioso

2. **`minimumReleaseAge`**:
   - Bloquea la instalación de paquetes publicados hace menos de N minutos/días
   - Da tiempo a la comunidad para detectar y eliminar versiones maliciosas
   - Ejemplo: `1440` (1 día) — suficiente para que el malware sea identificado

3. **`trustPolicy: no-downgrade`**:
   - Impide instalar versiones publicadas con menor nivel de confianza que las anteriores
   - Ejemplo: si un paquete siempre se publicó desde CI/CD verificado, una versión publicada con token simple quedaría bloqueada

### Ejemplo de configuración en `pnpm-workspace.yaml`

```yaml
packages:
  - '.'
onlyBuiltDependencies:
  - '@angular/core'  # solo lo imprescindible
minimumReleaseAge: 1440  # 1 día
trustPolicy: no-downgrade
```

---

## 3. Impacto en el proyecto FitStock

### Cambios realizados
- Se reemplazó npm por pnpm como gestor de paquetes
- Se eliminó el campo `"packageManager": "npm@11.11.0"` del `package.json`
- Se creó `pnpm-lock.yaml` (compatible con `package.json` existente)

### Comandos de uso diario
| Acción | npm | pnpm |
|---|---|---|
| Instalar dependencias | `npm install` | `pnpm install` |
| Añadir dependencia | `npm install <paquete>` | `pnpm add <paquete>` |
| Añadir dev dep | `npm install -D <paquete>` | `pnpm add -D <paquete>` |
| Compilar producción | `npm run build -- --no-server` | `pnpm run build --no-server` |
| Servir desarrollo | `npm run start` | `pnpm run start` |

### Notas
- `package.json` es completamente compatible
- No es necesario cambiar nada en el código fuente
- `pnpm-lock.yaml` debe committearse al repositorio
- El build de producción genera los mismos archivos en `dist/`

---

## 4. Conclusión

npm ha mejorado la seguridad del lado de **publicación** (trusted publishing, OIDC, 2FA), pero el ataque Shai-Hulud demostró que la protección del lado del **consumidor** era insuficiente. pnpm v10+ cubre este vacío con protecciones que npm aún no ofrece por defecto.

La migración es un **cambio de configuración sin impacto en el código**, que añade una capa crítica de defensa contra futuros ataques a la cadena de suministro.
