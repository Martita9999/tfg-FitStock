# Tarjetas de prueba - Stripe (modo test)

> Todas usan cualquier fecha futura (ej: 12/28) y CVC de 3 dígitos (ej: 123).

| Número de tarjeta | Resultado |
|---|---|
| `4242 4242 4242 4242` | ✅ Pago exitoso |
| `4000 0025 0000 3155` | ✅ Pago exitoso (sin 3D Secure) |
| `4000 0000 0000 3220` | ⚠️ Requiere autenticación 3D Secure |
| `4000 0000 0000 0002` | ❌ Tarjeta rechazada (declinada genérica) |
| `4000 0000 0000 9987` | ❌ Fondos insuficientes |
| `4000 0000 0000 0069` | ❌ Tarjeta expirada |
| `4000 0000 0000 0127` | ❌ CVC incorrecto |
