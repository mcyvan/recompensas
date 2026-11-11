<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/prospectos.php');
require_once('../app/functions/xlsx_simple.php');
verificarSesion();

if (!prospectoEsAdministracion()) {
    http_response_code(403);
    exit('Solo administracion puede descargar el Excel de prospectos.');
}

// Mismos filtros que la pantalla: vendedor (nombre), estatus (codigo o ABIERTA), tipo de obra y nivel de alerta.
$fVendedor = trim((string) ($_GET['vendedor'] ?? ''));
$fEstatus = trim((string) ($_GET['estatus'] ?? ''));
$fTipo = trim((string) ($_GET['tipo'] ?? ''));
$fAlerta = (int) ($_GET['alerta'] ?? 0);

try {
    $todas = prospectoListar($pdo, null);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No fue posible leer las obras. Revisa que la migracion de prospectos este ejecutada.');
}

$filas = array_values(array_filter($todas, static function (array $f) use ($fVendedor, $fEstatus, $fTipo, $fAlerta): bool {
    if ($fVendedor !== '' && $f['vendedor'] !== $fVendedor) {
        return false;
    }
    if ($fEstatus === 'ABIERTA' && !in_array($f['estatus'], PROSPECTO_ESTATUS_ABIERTOS, true)) {
        return false;
    }
    if ($fEstatus !== '' && $fEstatus !== 'ABIERTA' && $f['estatus'] !== $fEstatus) {
        return false;
    }
    if ($fTipo !== '' && $f['tipo_obra'] !== $fTipo) {
        return false;
    }
    if ($fAlerta > 0 && prospectoNivelAlerta($f) !== $fAlerta) {
        return false;
    }

    return true;
}));

// Resumen de los filtros aplicados, para que el archivo se explique solo.
$descripcionFiltros = [];
if ($fVendedor !== '') {
    $descripcionFiltros[] = 'Vendedor: ' . $fVendedor;
}
if ($fEstatus !== '') {
    $descripcionFiltros[] = 'Estatus: ' . ($fEstatus === 'ABIERTA' ? 'en proceso (no cerradas)' : prospectoEtiquetaEstatus($fEstatus));
}
if ($fTipo !== '') {
    $descripcionFiltros[] = 'Tipo de obra: ' . $fTipo;
}
if ($fAlerta > 0 && isset(PROSPECTO_ALERTAS[$fAlerta])) {
    $descripcionFiltros[] = 'Alerta: ' . PROSPECTO_ALERTAS[$fAlerta]['etiqueta'];
}
$textoFiltros = $descripcionFiltros ? implode(' | ', $descripcionFiltros) : 'Sin filtros (todas las obras)';
$generado = 'Generado el ' . date('d/m/Y H:i');

// ---------- Hoja 1: obras ----------
$titulos = [
    'Vendedor', 'Cliente', 'Teléfono', 'Proyecto', 'Tipo de obra', 'Concreto', 'm³', 'Inicio de obra', 'Fin de obra',
    'Estatus', 'Motivo de pérdida', 'Detalle del motivo', 'Competidor', 'Dirección', 'Ubicación', 'Comentarios',
    'Fecha de captura', 'Último movimiento', 'Días sin movimiento', 'Alerta',
];
$anchos = [22, 30, 14, 32, 18, 22, 11, 14, 14, 24, 32, 26, 20, 34, 14, 40, 14, 18, 12, 28];

$obras = [array_map(static fn($t) => ['v' => $t, 's' => 'cabecera'], $titulos)];
$sumaM3 = 0.0;
foreach ($filas as $f) {
    $nivel = prospectoNivelAlerta($f);
    $estiloAlerta = $nivel > 0 ? 'alerta' . $nivel : 'texto';
    $urlMapa = prospectoUrlMapa($f);
    $m3 = (float) $f['volumen_m3'];
    $sumaM3 += $m3;

    $obras[] = [
        ['v' => $f['vendedor'], 's' => 'texto'],
        ['v' => $f['cliente'], 's' => 'texto'],
        ['v' => (string) $f['telefono'], 's' => 'texto'],
        ['v' => $f['proyecto'], 's' => 'texto'],
        ['v' => $f['tipo_obra'], 's' => 'texto'],
        ['v' => $f['tipos_concreto'], 's' => 'texto'],
        ['v' => $m3, 's' => 'num2'],
        ['fecha' => (string) $f['fecha_inicio'], 's' => 'fecha'],
        ['fecha' => (string) $f['fecha_fin'], 's' => 'fecha'],
        ['v' => prospectoEtiquetaEstatus($f['estatus']), 's' => 'texto'],
        ['v' => $f['motivo_perdida'], 's' => 'texto'],
        ['v' => $f['motivo_detalle'], 's' => 'texto'],
        ['v' => $f['competidor'], 's' => 'texto'],
        ['v' => $f['direccion'], 's' => 'texto'],
        $urlMapa !== '' ? ['url' => $urlMapa, 'v' => 'Ver mapa', 's' => 'enlace'] : ['v' => '', 's' => 'texto'],
        ['v' => $f['comentarios'], 's' => 'texto'],
        ['fecha' => (string) $f['fecha_registro'], 's' => 'fecha'],
        ['fecha' => (string) $f['ultimo_movimiento'], 's' => 'fechahora'],
        ['v' => prospectoDiasSinMovimiento($f), 's' => $estiloAlerta],
        ['v' => $nivel > 0 ? PROSPECTO_ALERTAS[$nivel]['etiqueta'] : '', 's' => $estiloAlerta],
    ];
}

$ultimaDato = count($obras); // fila de excel del ultimo dato (la cabecera es la 1)
if ($filas) {
    $obras[] = []; // fila en blanco entre los datos y el total
    $totalFila = [];
    for ($i = 0; $i < 20; $i++) {
        $totalFila[] = ['v' => '', 's' => 'normal'];
    }
    $totalFila[0] = ['v' => 'Total (respeta los filtros de Excel)', 's' => 'negrita'];
    $totalFila[6] = ['f' => 'SUBTOTAL(109,G2:G' . $ultimaDato . ')', 'v' => $sumaM3, 's' => 'num2'];
    $obras[] = $totalFila;
}
$obras[] = [];
$obras[] = [['v' => $textoFiltros . ' - ' . $generado, 's' => 'nota']];

// ---------- Hoja 2: resumen ----------
$resumen = prospectoResumen($filas);
$ind = prospectoIndicadores($filas);

$res = [];
$res[] = [['v' => 'Prospección de obras - Resumen', 's' => 'titulo']];
$res[] = [['v' => $textoFiltros . ' - ' . $generado, 's' => 'nota']];
$res[] = [];
$res[] = [
    ['v' => 'Obras', 's' => 'seccion'], ['v' => 'En proceso', 's' => 'seccion'], ['v' => 'm³ en proceso', 's' => 'seccion'],
    ['v' => 'Ganadas', 's' => 'seccion'], ['v' => 'm³ ganados', 's' => 'seccion'], ['v' => 'Perdidas', 's' => 'seccion'],
    ['v' => 'm³ perdidos', 's' => 'seccion'], ['v' => 'Cierre', 's' => 'seccion'],
];
$cerradas = $ind['ganadas'] + $ind['perdidas'];
$res[] = [
    ['v' => $ind['total'], 's' => 'entero'], ['v' => $ind['abiertas'], 's' => 'entero'], ['v' => $ind['m3_abiertos'], 's' => 'num2'],
    ['v' => $ind['ganadas'], 's' => 'entero'], ['v' => $ind['m3_ganados'], 's' => 'num2'], ['v' => $ind['perdidas'], 's' => 'entero'],
    ['v' => $ind['m3_perdidos'], 's' => 'num2'],
    $cerradas > 0 ? ['v' => $ind['ganadas'] / $cerradas, 's' => 'porcentaje'] : ['v' => '-', 's' => 'texto'],
];
$res[] = [];

$res[] = [['v' => 'Por vendedor', 's' => 'titulo']];
$res[] = array_map(static fn($t) => ['v' => $t, 's' => 'cabecera'], [
    'Vendedor', 'Obras', 'En proceso', 'm³ en proceso', 'Ganadas', 'm³ ganados', 'Perdidas', 'm³ perdidos', 'Cierre',
    'Sin movimiento: ' . PROSPECTO_ALERTAS[1]['corta'], 'Sin movimiento: ' . PROSPECTO_ALERTAS[2]['corta'],
    'Sin movimiento: ' . PROSPECTO_ALERTAS[3]['corta'],
]);
foreach ($resumen['por_vendedor'] as $nombre => $v) {
    $cerr = $v['ganadas'] + $v['perdidas'];
    $res[] = [
        ['v' => $nombre, 's' => 'negrita'], ['v' => $v['obras'], 's' => 'entero'], ['v' => $v['abiertas'], 's' => 'entero'],
        ['v' => $v['m3_abiertos'], 's' => 'num2'], ['v' => $v['ganadas'], 's' => 'entero'], ['v' => $v['m3_ganados'], 's' => 'num2'],
        ['v' => $v['perdidas'], 's' => 'entero'], ['v' => $v['m3_perdidos'], 's' => 'num2'],
        $cerr > 0 ? ['v' => $v['ganadas'] / $cerr, 's' => 'porcentaje'] : ['v' => '-', 's' => 'texto'],
        ['v' => $v['alertas'][1], 's' => $v['alertas'][1] > 0 ? 'alerta1' : 'entero'],
        ['v' => $v['alertas'][2], 's' => $v['alertas'][2] > 0 ? 'alerta2' : 'entero'],
        ['v' => $v['alertas'][3], 's' => $v['alertas'][3] > 0 ? 'alerta3' : 'entero'],
    ];
}
$res[] = [];

$res[] = [['v' => 'Por estatus', 's' => 'titulo']];
$res[] = array_map(static fn($t) => ['v' => $t, 's' => 'cabecera'], ['Estatus', 'Obras', 'm³']);
foreach ($resumen['por_estatus'] as $codigo => $e) {
    $res[] = [['v' => prospectoEtiquetaEstatus($codigo), 's' => 'texto'], ['v' => $e['obras'], 's' => 'entero'], ['v' => $e['m3'], 's' => 'num2']];
}
$res[] = [];

$res[] = [['v' => 'Por qué se pierden las obras', 's' => 'titulo']];
$res[] = array_map(static fn($t) => ['v' => $t, 's' => 'cabecera'], ['Motivo', 'Obras', 'm³']);
if (!$resumen['motivos']) {
    $res[] = [['v' => 'Todavía no hay obras perdidas.', 's' => 'texto']];
}
foreach ($resumen['motivos'] as $motivo => $m) {
    $res[] = [['v' => $motivo, 's' => 'texto'], ['v' => $m['obras'], 's' => 'entero'], ['v' => $m['m3'], 's' => 'num2']];
}
$res[] = [];

$res[] = [['v' => 'Competidores que se llevaron obras', 's' => 'titulo']];
$res[] = array_map(static fn($t) => ['v' => $t, 's' => 'cabecera'], ['Competidor', 'Obras', 'm³']);
if (!$resumen['competidores']) {
    $res[] = [['v' => 'Todavía no hay obras perdidas contra un competidor.', 's' => 'texto']];
}
foreach ($resumen['competidores'] as $nombre => $c) {
    $res[] = [['v' => $nombre, 's' => 'texto'], ['v' => $c['obras'], 's' => 'entero'], ['v' => $c['m3'], 's' => 'num2']];
}

try {
    $ruta = xlsxCrear([
        ['nombre' => 'Obras', 'anchos' => $anchos, 'cabecera' => 1, 'filtro' => true, 'filtro_hasta' => max(2, $ultimaDato), 'filas' => $obras],
        ['nombre' => 'Resumen', 'anchos' => [34, 14, 14, 16, 14, 16, 14, 16, 12, 20, 20, 20], 'filas' => $res],
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No fue posible crear el Excel.');
}

$nombreArchivo = 'prospectos_obras_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . filesize($ruta));
header('Pragma: no-cache');
header('Expires: 0');
readfile($ruta);
unlink($ruta);
exit;
