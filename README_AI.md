# Guía para continuar Recompensas

Este archivo resume cómo abordar cambios en el proyecto sin perder las decisiones de negocio ya tomadas.

## Lectura inicial obligatoria

1. `README.md`
2. `CONTEXT.md`
3. `ARCHITECTURE.md`
4. Las migraciones y archivos concretos relacionados con la solicitud.

## Mapa rápido

- Remisiones, reportes y gráficas: `remisiones/`.
- Importación de ventas: `conciliacion/`.
- Registro manual del dosificador: `operador/` y su configuración administrativa.
- Puntos, premios y canjes: `premios/`, `canjes/` y configuración relacionada.
- Alertas por correo: `alertas/` y configuración SMTP privada.
- Cambios de esquema: `database/`.

Confirma siempre los nombres exactos mediante búsqueda; no supongas una ruta o columna basándote solo en este resumen.

## Decisiones vigentes

- Las canceladas están excluidas de las métricas válidas.
- El administrador puede eliminar definitivamente remisiones canceladas; otros roles no.
- En conciliación, el vendedor correcto es el que viene en el archivo de Microsip.
- En reportes, ese vendedor también tiene prioridad cuando existe coincidencia; el vendedor interno queda como respaldo.
- `VERONICA REYES AGUIRRE` equivale a `VERONICA REYES`.
- El tablero usa vendedores dados de alta más `MOSTRADOR`, `AMERICAS`, `JAIME RODRIGUEZ` y `CLARA CRUZ`.
- Productos elegibles: descripciones que comienzan con `AMANUFACTURADA CONCRETO`, `CONCRETO`, `MORTERO`, `TERMOC` o `THERMO`.
- Una recarga XLSX reemplaza las fechas contenidas en el archivo.
- Una remisión con varias partidas cuenta una vez; los metros sí se suman.
- El folio capturado por el operador debe ser `RE` + seis dígitos; seis dígitos solos se normalizan agregando `RE`.
- Las remisiones nuevas guardan el rol capturista; la columna solo es visible para `ADMINISTRADOR` y `ADMINISTRACION`.
- Las duraciones usan fecha y hora completas, incluidos cruces de día.
- Las alertas vigentes son por correo general; push no forma parte del sistema.
- La captura manual del dosificador depende de permiso y habilitación global.
- La matriz general administrable de permisos por módulo sigue pendiente; no asumir que ya existe.

## Forma segura de trabajar

1. Revisar el estado del repositorio y preservar cambios que no pertenezcan a la tarea.
2. Buscar todas las referencias del campo, estado o cálculo antes de editar.
3. Reutilizar una sola regla normalizada cuando la misma métrica aparece en varios lugares.
4. Para cambios de base, crear una migración en `database/`; no depender de una modificación manual sin registrar.
5. Validar sintaxis de cada PHP modificado.
6. Probar permisos directamente contra la URL y la acción, no solo mediante el menú.
7. Contrastar cifras entre tabla, tarjetas, gráficas y Excel.
8. Entregar una lista exacta de archivos y SQL que deben pasarse a cPanel.

## Casos mínimos de prueba

- Remisión finalizada normal.
- Remisión cancelada.
- Descarga que inicia un día y termina al siguiente.
- Remisión vendida con vendedor distinto al vendedor del cliente.
- Alias de Verónica Reyes.
- Remisión con dos o más partidas elegibles.
- Archivo diario recargado.
- Archivo de una semana que sustituye días previamente cargados.
- Artículo elegible por cada prefijo y un servicio no elegible.
- Acceso de `ADMIN`, `ADMINISTRACION` y un rol sin permiso.

## Producción

El usuario suele desplegar manualmente en cPanel. La entrega debe separar claramente:

- archivos nuevos;
- archivos modificados;
- archivos que se eliminan;
- SQL que se ejecuta una sola vez;
- pruebas posteriores al despliegue.

Nunca incluir ni reemplazar credenciales reales de base de datos o correo en una entrega.
