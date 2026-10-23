# Arquitectura de Recompensas

## 1. Panorama general

Aplicación web PHP organizada por módulos. Las páginas validan sesión y rol, consultan MySQL/MariaDB mediante PDO y renderizan interfaces basadas en AdminLTE/Bootstrap. Los procesos auxiliares incluyen importación de XLSX y envío de alertas por SMTP.

## 2. Estructura principal

| Ruta | Responsabilidad |
|---|---|
| `login/` | Inicio de sesión y acceso al sistema. |
| `admin/` | Conexión, configuración, usuarios, roles y funciones administrativas. |
| `clientes/` | Alta, edición y consulta de clientes. |
| `vendedores/` | Catálogo y operación relacionada con vendedores. |
| `operador/` | Flujos del operador/dosificador, incluida la remisión manual. |
| `remisiones/` | Registro, listado, edición, reportes, métricas y conciliación visual. |
| `conciliacion/` | Carga y procesamiento de archivos de ventas de Microsip. |
| `premios/` | Catálogo de recompensas. |
| `canjes/` | Canjes, cancelaciones y movimientos de puntos. |
| `alertas/` | Revisión de descargas prolongadas y envío de correo. |
| `includes/` | Componentes compartidos de interfaz, sesión y navegación. |
| `database/` | Migraciones y cambios versionados de esquema. |

Antes de cambiar una ruta, confirmar sus nombres reales con una búsqueda en el repositorio; este documento describe responsabilidades y no reemplaza la inspección del código.

## 3. Flujos principales

### 3.1 Clientes y remisiones

1. Se identifica o registra al cliente.
2. Se captura la remisión y sus datos operativos.
3. El inicio y fin de descarga guardan fecha y hora completas.
4. Al finalizar, se calculan metros, duración y puntos conforme a la configuración vigente.
5. Una cancelación deja la remisión fuera de las métricas válidas.
6. Un administrador puede eliminar definitivamente una remisión cancelada desde la pantalla de edición/listado autorizada.

La captura manual del dosificador tiene dos controles: autorización por usuario/rol y un interruptor global administrable. Ambos deben cumplirse.

### 3.2 Puntos, premios y canjes

- Los puntos se originan en remisiones válidas.
- El saldo debe poder explicarse mediante movimientos, no solo por un total aislado.
- Los canjes descuentan puntos y conservan su estado.
- Una cancelación de canje debe aplicar la reversión prevista y evitar dobles devoluciones.
- Los cambios de reglas de puntos deben preservar la trazabilidad histórica.

### 3.3 Carga de ventas de Microsip

1. Un usuario autorizado selecciona uno o varios archivos XLSX.
2. El importador lee las hojas mediante ZIP/XML.
3. Se localizan las columnas de fecha, remisión, pedido, factura, planta, vendedor, cliente, artículo, cantidad y precio.
4. Se normalizan fechas, números de remisión y textos comerciales.
5. Se determinan todas las fechas contenidas en la carga.
6. Dentro de una transacción se sustituyen los registros de esas fechas y se insertan los nuevos.
7. El resultado informa filas leídas, aceptadas, omitidas y fechas reemplazadas.

La sustitución por fecha permite subir un día, varios archivos diarios o una semana completa sin duplicar lo ya cargado.

### 3.4 Clasificación de productos

La clasificación debe ser equivalente a esta expresión conceptual:

```text
descripcion normalizada empieza con
AMANUFACTURADA CONCRETO | CONCRETO | MORTERO | TERMOC | THERMO
```

La comparación no distingue mayúsculas/minúsculas. Es importante usar prefijo y no una coincidencia en cualquier posición, para evitar incluir servicios o productos no elegibles que solo mencionen esas palabras en otra parte.

### 3.5 Conciliación y tablero

1. Se toman las remisiones de productos elegibles del archivo de ventas.
2. Las partidas se agrupan por remisión para no duplicar documentos con varios renglones.
3. Se busca la remisión correspondiente en Recompensas.
4. Las canceladas se excluyen de ambos lados de los indicadores aplicables.
5. Se calculan remisiones vendidas, registradas, faltantes, metros y porcentaje de cobertura.
6. Para agrupaciones por vendedor se usa el vendedor comercial del archivo cargado.

El porcentaje general es:

```text
remisiones válidas encontradas en Recompensas / remisiones elegibles vendidas * 100
```

Todas las tarjetas, tablas y gráficas deben partir del mismo conjunto normalizado; no deben repetir reglas distintas en cada componente.

### 3.6 Reportes de remisiones

- No cuentan remisiones canceladas en remisiones, metros, puntos ni promedios.
- Cuando una remisión coincide con la carga de Microsip, el vendedor mostrado es el vendedor comercial del archivo.
- Si no existe coincidencia, se conserva el vendedor asociado en Recompensas.
- La exportación debe coincidir con los filtros y totales visibles.

### 3.7 Alertas de descargas

- El proceso programado consulta remisiones que continúan abiertas y exceden el umbral configurado, por ejemplo dos horas.
- La duración se calcula desde el inicio real hasta la hora actual con fecha completa.
- Se envía al correo general configurado por SMTP.
- Debe evitarse el envío repetitivo de la misma alerta dentro del intervalo de control.
- La prueba SMTP y la tarea programada son verificaciones separadas: que el correo de prueba llegue no garantiza que el programador esté ejecutando el archivo correcto.
- La alternativa de notificaciones push fue descartada y no forma parte del despliegue vigente.

## 4. Datos y tablas

Los nombres exactos deben confirmarse en las migraciones y consultas actuales. Los grupos funcionales son:

- Usuarios, roles, vendedores y clientes.
- Remisiones, estados, fechas de descarga, camión y chofer.
- Configuración y movimientos de puntos.
- Premios, canjes y cancelaciones.
- Configuración de módulos, incluida la captura manual.
- Alertas enviadas o marcas de control.
- Encabezados/partidas o filas normalizadas de ventas importadas.

No crear tablas paralelas para un concepto existente sin revisar antes `database/` y las consultas del módulo.

## 5. Migraciones relevantes

| Archivo | Propósito |
|---|---|
| `database/2026_06_12_sso_logistica.sql` | Integración de acceso con Logística. |
| `database/2026_06_13_configuracion_puntos.sql` | Configuración de puntos. |
| `database/2026_06_13_modulo_canjes.sql` | Base del módulo de canjes. |
| `database/2026_06_15_cancelacion_canjes.sql` | Cancelación y reversión de canjes. |
| `database/2026_06_15_roles_canjes.sql` | Permisos de roles para canjes. |
| `database/2026_08_15_remision_qr_crm.sql` | Datos y flujo de remisión mediante QR/CRM. |
| `database/2026_08_20_remisiones_camion_logistica.sql` | Asociación de camiones de Logística. |
| `database/2026_08_27_remisiones_manuales_dosificador.sql` | Captura manual y su configuración. |
| `database/2026_08_31_alertas_descargas.sql` | Soporte de alertas por descargas prolongadas. |
| `database/2026_09_02_conciliacion_ventas.sql` | Almacenamiento para carga y conciliación de ventas. |
| `database/2026_09_10_productos_conciliacion.sql` | Ajustes de clasificación de productos elegibles. |
| `database/2026_09_10_rol_captura_remisiones.sql` | Registra el rol que capturó cada remisión nueva. |

Antes de ejecutar en cPanel, abrir la migración y verificar si es idempotente y si el esquema objetivo ya contiene parcial o totalmente el cambio.

## 6. Seguridad

- Toda página y acción sensible debe validar sesión y rol en el servidor.
- Las consultas con datos variables deben usar parámetros preparados.
- Las cargas deben validar extensión, estructura, tamaño y contenido antes de procesarse.
- Escapar contenido de usuario al generar HTML.
- No almacenar credenciales SMTP o de base de datos en documentación, capturas, repositorio o mensajes de despliegue.
- Las acciones irreversibles requieren permiso de `ADMIN`, objetivo explícito y protección contra solicitudes accidentales.

## 7. Configuración y despliegue

El despliegue habitual es manual hacia cPanel:

1. Identificar los archivos modificados con el control de versiones.
2. Respaldar en producción los archivos que serán reemplazados.
3. Subir conservando la misma estructura de carpetas.
4. Ejecutar solamente las migraciones nuevas necesarias.
5. Mantener en producción sus propios archivos privados de conexión y SMTP; no reemplazarlos con credenciales locales.
6. Probar inicio de sesión, permisos, carga XLSX, conciliación, reportes y alertas afectadas.
7. Revisar los registros de PHP/Apache si aparece una pantalla en blanco o un error 500.

## 8. Deuda técnica y mejoras pendientes

- Centralizar reglas repetidas de métricas, estados y vendedores para impedir diferencias entre reporte, Excel y tablero.
- Implementar, si se aprueba, una matriz administrativa de permisos por módulo y rol. Actualmente no existe como módulo general terminado.
- Añadir pruebas automatizadas para cruces de día, cancelaciones, recargas de periodos y remisiones con varias partidas.
- Formalizar una bitácora de importaciones y errores accesible desde administración.

## 9. Criterio para cambios futuros

Una corrección no está completa si solo cambia la cifra visible en una tarjeta. Debe revisarse el mismo concepto en consultas, tabla, gráficas, exportación, alertas y cualquier cálculo derivado.
