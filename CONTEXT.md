# Contexto del proyecto Recompensas

Última actualización: 10 de septiembre de 2026.

## Objetivo

Recompensas concentra clientes, remisiones de concreto, tiempos de descarga, puntos y canjes. También recibe archivos de ventas de Microsip para comparar el total vendido contra las remisiones efectivamente capturadas en el sistema.

El proyecto local está en `C:\xampp\htdocs\recompensas` y los cambios de producción se suben manualmente a cPanel junto con las migraciones SQL necesarias.

## Estado funcional actual

- Alta y consulta de clientes y vendedores.
- Registro de remisiones mediante el flujo normal, QR y captura manual autorizada.
- Activación o desactivación global del registro manual para dosificadores.
- Cálculo de duración de descarga sin perder días completos cuando cruza de fecha.
- Generación de puntos, saldos, premios y canjes.
- Reportes de remisiones con métricas y exportación.
- Eliminación permanente de remisiones canceladas disponible solamente para `ADMIN`.
- La lista de remisiones muestra a `ADMINISTRADOR` y `ADMINISTRACION` el rol que realizó cada captura nueva.
- Carga de uno o varios archivos XLSX de ventas, diarios o de periodos completos.
- Si se vuelve a cargar un periodo, se sustituyen los datos de las fechas incluidas para evitar duplicados.
- Tablero de “Gráficas y resultados” dentro de Remisiones para conciliar ventas contra Recompensas.
- Filtros de conciliación por fechas, planta y vendedor.
- Alertas generales por correo cuando una descarga abierta rebasa el límite configurado.

## Reglas de negocio que no deben romperse

### Remisiones

- Las remisiones canceladas no cuentan en totales, metros, promedios ni conciliación.
- Una remisión puede contener varias partidas; debe contarse una sola vez y sus metros deben agregarse correctamente.
- En las capturas del operador, el folio se guarda siempre como `RE` seguido de exactamente seis dígitos. Si se escriben únicamente los seis dígitos, el sistema agrega `RE` automáticamente; cualquier otra longitud o formato se rechaza.
- Las capturas nuevas conservan el rol de origen: `OPERADOR`, `DOSIFICADOR`, `ADMINISTRACION` o `ADMINISTRADOR`. Este dato solo se muestra a los dos roles administrativos.
- La duración debe calcularse con fecha y hora completas. Una descarga iniciada un día y terminada al siguiente no puede reducirse a la diferencia de minutos del reloj.
- La eliminación definitiva solo está permitida para administradores y se usa sobre remisiones canceladas.

### Vendedor comercial

- En la conciliación y sus gráficas manda el vendedor indicado en el archivo de ventas de Microsip.
- No debe sustituirse por el vendedor asociado al cliente o a la remisión dentro de Recompensas.
- En reportes operativos, cuando existe coincidencia con la carga de ventas se usa el vendedor comercial del archivo; solo si no existe se conserva el vendedor registrado en Recompensas.
- `VERONICA REYES AGUIRRE` y `VERONICA REYES` se consideran la misma persona.
- La lista del tablero se limita a vendedores dados de alta en Recompensas y agrega las excepciones de negocio: `MOSTRADOR`, `AMERICAS`, `JAIME RODRIGUEZ` y `CLARA CRUZ`.

### Productos elegibles

Una partida se considera concreto o producto elegible cuando la descripción del artículo comienza, ignorando mayúsculas, minúsculas y espacios iniciales, con alguno de estos prefijos:

- `AMANUFACTURADA CONCRETO`
- `CONCRETO`
- `MORTERO`
- `TERMOC`
- `THERMO`

Esto replica la condición usada en la hoja de control. Productos como bombeo, maniobras, block, cemento, tarimas o aditivos no entran salvo que su descripción realmente comience con uno de esos prefijos.

### Carga de ventas

- Los archivos esperados contienen al menos: fecha, remisión, pedido, factura, planta, vendedor, cliente, artículo, cantidad y precio.
- La recarga reemplaza las fechas presentes en el archivo, no acumula copias de las mismas filas.
- La coincidencia principal con Recompensas se hace por número de remisión normalizado.
- Deben conservarse los datos comerciales originales del archivo para auditoría, especialmente vendedor, cliente, planta, artículo y cantidad.

### Seguridad y permisos

- La autorización debe validarse en PHP, no únicamente ocultando enlaces del menú.
- `ADMIN` conserva acceso total.
- `ADMINISTRACION` puede acceder a la carga de ventas.
- Los dosificadores solo pueden registrar remisiones manuales cuando su permiso y la configuración global están habilitados.
- La matriz administrable de permisos por rol y módulo se ha propuesto, pero todavía no debe documentarse como implementada.

### Fechas

- La operación usa la zona horaria `America/Mexico_City`.
- Evitar comparar únicamente horas o cadenas formateadas cuando intervienen dos fechas distintas.

## Antes de modificar

1. Leer `README.md`, este archivo y `ARCHITECTURE.md`.
2. Revisar cambios locales antes de editar; no borrar trabajo ajeno.
3. Localizar primero todas las consultas y pantallas que calculen la misma métrica.
4. Mantener consistentes el reporte, las tarjetas, las gráficas y el Excel.
5. Si cambia la base de datos, agregar una migración repetible en `database/` y documentarla.
6. Probar al menos un caso normal, uno cancelado y uno que cruce de día cuando aplique.
