<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once("../app/functions/remisiones.php");

verificarSesion();
verificarPermisoRemisiones();

$filtros = filtrosReporteRemisiones($_GET, $pdo);
$fechaArchivo = date('Ymd_His');
$nombreArchivo = "reporte_remisiones_{$fechaArchivo}.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

$salida = fopen('php://output', 'w');

// BOM para que Excel detecte correctamente UTF-8.
fwrite($salida, "\xEF\xBB\xBF");

$escribirTitulo = function (string $titulo) use ($salida): void {
    fputcsv($salida, []);
    fputcsv($salida, [$titulo]);
};

$resumen = obtenerResumenReporteRemisiones($pdo, $filtros);
$porCliente = obtenerReporteRemisionesPorCliente($pdo, $filtros, 1000);
$porVendedor = obtenerReporteRemisionesPorVendedor($pdo, $filtros);
$porChofer = obtenerReporteRemisionesPorChofer($pdo, $filtros);
$porDia = obtenerReporteRemisionesPorDia($pdo, $filtros);

fputcsv($salida, ['Reporte de remisiones']);
fputcsv($salida, ['Fecha inicio', $filtros['fecha_inicio']]);
fputcsv($salida, ['Fecha fin', $filtros['fecha_fin']]);
fputcsv($salida, ['Cliente / telefono', $filtros['cliente'] ?: 'Todos']);
fputcsv($salida, ['Vendedor', $filtros['vendedor'] ?: 'Todos']);
fputcsv($salida, ['Chofer', $filtros['id_operador'] ?: 'Todos']);
fputcsv($salida, ['Estatus', $filtros['estatus'] ?: 'Todos']);

$escribirTitulo('Resumen');
fputcsv($salida, ['Remisiones', 'Metros', 'Puntos', 'Clientes', 'Vendedores', 'Choferes', 'Promedio m3', 'Promedio minutos', 'Finalizadas', 'En proceso', 'Canceladas']);
fputcsv($salida, [
    (int) ($resumen['total_remisiones'] ?? 0),
    (float) ($resumen['total_metros'] ?? 0),
    (float) ($resumen['total_puntos'] ?? 0),
    (int) ($resumen['total_clientes'] ?? 0),
    (int) ($resumen['total_vendedores'] ?? 0),
    (int) ($resumen['total_choferes'] ?? 0),
    round((float) ($resumen['promedio_metros'] ?? 0), 2),
    round((float) ($resumen['promedio_minutos'] ?? 0), 2),
    (int) ($resumen['finalizadas'] ?? 0),
    (int) ($resumen['en_proceso'] ?? 0),
    (int) ($resumen['canceladas'] ?? 0),
]);

$escribirTitulo('Por vendedor');
fputcsv($salida, ['Vendedor', 'Clientes', 'Remisiones', 'Metros', 'Puntos', 'Ultima remision']);
foreach ($porVendedor as $fila) {
    fputcsv($salida, [
        $fila['vendedor'],
        (int) $fila['total_clientes'],
        (int) $fila['total_remisiones'],
        (float) $fila['total_metros'],
        (float) $fila['total_puntos'],
        $fila['ultima_remision'],
    ]);
}

$escribirTitulo('Por cliente');
fputcsv($salida, ['Cliente', 'Telefono', 'Vendedor', 'Remisiones', 'Metros', 'Puntos', 'Primera remision', 'Ultima remision']);
foreach ($porCliente as $fila) {
    fputcsv($salida, [
        $fila['cliente'],
        $fila['telefono'],
        $fila['vendedor'] ?? 'Sin vendedor',
        (int) $fila['total_remisiones'],
        (float) $fila['total_metros'],
        (float) $fila['total_puntos'],
        $fila['primera_remision'],
        $fila['ultima_remision'],
    ]);
}

$escribirTitulo('Por chofer');
fputcsv($salida, ['Chofer', 'Remisiones', 'Metros', 'Puntos', 'Promedio minutos', 'Fuera de 45 min']);
foreach ($porChofer as $fila) {
    fputcsv($salida, [
        $fila['operador'],
        (int) $fila['total_remisiones'],
        (float) $fila['total_metros'],
        (float) $fila['total_puntos'],
        round((float) $fila['promedio_minutos'], 2),
        (int) $fila['fuera_tiempo'],
    ]);
}

$escribirTitulo('Por dia');
fputcsv($salida, ['Fecha', 'Remisiones', 'Metros', 'Puntos']);
foreach ($porDia as $fila) {
    fputcsv($salida, [
        $fila['fecha'],
        (int) $fila['total_remisiones'],
        (float) $fila['total_metros'],
        (float) $fila['total_puntos'],
    ]);
}

$parametros = [];
$where = condicionesReporteRemisiones($filtros, $parametros);
$selectCamion = selectCamposCamionRemision($pdo);
$joinVentas = joinVendedorComercialReporte();
$vendedorComercial = expresionVendedorComercialReporte();
$stmt = $pdo->prepare(
    "SELECT
        r.folio_remision,
        r.telefono,
        COALESCE(NULLIF(TRIM(CONCAT(c.nombres, ' ', c.apellido_p, ' ', c.apellido_m)), ''), 'SIN CLIENTE REGISTRADO') AS cliente,
        $vendedorComercial AS vendedor,
        r.volumen,
        r.hora_inicio,
        r.hora_fin,
        r.minutos_colado,
        r.puntos,
        r.estatus,
        $selectCamion,
        u.usuario AS operador
     FROM tb_remisiones r
     LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
     LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
     $joinVentas
     INNER JOIN tb_usuarios u ON u.id_usuario = r.id_operador
     $where
     ORDER BY r.hora_inicio DESC, r.id_remision DESC"
);
$stmt->execute($parametros);

$escribirTitulo('Detalle');
fputcsv($salida, ['Folio', 'Telefono', 'Cliente', 'Vendedor', 'Volumen', 'Hora inicio', 'Hora fin', 'Minutos', 'Puntos', 'Estatus', 'Chofer', 'Camion']);
while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($salida, [
        $fila['folio_remision'],
        $fila['telefono'],
        $fila['cliente'],
        $fila['vendedor'] ?? 'Sin vendedor',
        (float) $fila['volumen'],
        $fila['hora_inicio'],
        $fila['hora_fin'],
        $fila['minutos_colado'],
        (float) $fila['puntos'],
        $fila['estatus'],
        $fila['operador'],
        $fila['camion_logistica'] ?: 'Camion no registrado',
    ]);
}

fclose($salida);
exit;
