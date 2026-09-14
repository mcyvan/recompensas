<?php

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/functions/auth.php';
require_once __DIR__ . '/../app/functions/conciliacion_ventas.php';

verificarSesion();
verificarPermisoConciliacionVentas();

if (empty($_SESSION['csrf_conciliacion_ventas'])) {
    $_SESSION['csrf_conciliacion_ventas'] = bin2hex(random_bytes(32));
}

try {
    $filtros = filtrosConciliacionVentas($pdo, $_GET);
    $resumen = obtenerResumenConciliacion($pdo, $filtros);
    $porDia = obtenerConciliacionPorDia($pdo, $filtros);
    $porVendedor = obtenerConciliacionPorVendedor($pdo, $filtros);
    $detalle = obtenerDetalleConciliacion($pdo, $filtros);
    $cargas = obtenerUltimasCargasVentas($pdo);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Falta ejecutar la migracion database/2026_09_02_conciliacion_ventas.sql.');
}

$estado = strtolower(trim((string) ($_GET['estado'] ?? 'todos')));
if (!in_array($estado, ['todos', 'registradas', 'faltantes'], true)) {
    $estado = 'todos';
}
if ($estado !== 'todos') {
    $esperado = $estado === 'registradas' ? 1 : 0;
    $detalle = array_values(array_filter($detalle, static fn($fila) => (int) $fila['en_recompensas'] === $esperado));
}

$mensajeCorrecto = $_SESSION['mensaje_conciliacion_correcto'] ?? null;
$mensajeError = $_SESSION['mensaje_conciliacion_error'] ?? null;
unset($_SESSION['mensaje_conciliacion_correcto'], $_SESSION['mensaje_conciliacion_error']);

$total = (int) ($resumen['remisiones_venta'] ?? 0);
$registradas = (int) ($resumen['remisiones_registradas'] ?? 0);
$faltantes = (int) ($resumen['faltantes'] ?? 0);
$porcentaje = (float) ($resumen['porcentaje'] ?? 0);
$tabActivo = 'carga';
?>
<!doctype html>
<html lang="es">
<head>
    <?php include __DIR__ . '/../app/layout/head.php'; ?>
    <title>Conciliacion de ventas</title>
    <style>
        .metric-card { border: 1px solid #dfe5ec; border-left: 5px solid #174a94; border-radius: 10px; background: #fff; padding: 16px; height: 100%; box-shadow: 0 6px 18px rgba(15,23,42,.05); }
        .metric-card.success { border-left-color: #198754; }
        .metric-card.danger { border-left-color: #dc3545; }
        .metric-card.info { border-left-color: #0dcaf0; }
        .metric-label { color: #64748b; font-size: .83rem; }
        .metric-value { color: #172033; font-size: 1.65rem; font-weight: 800; line-height: 1.15; }
        .coverage-track { height: 14px; background: #e9ecef; border-radius: 999px; overflow: hidden; }
        .coverage-fill { height: 100%; background: #198754; }
        .upload-drop { border: 2px dashed #b8c2cc; border-radius: 10px; background: #f8fafc; padding: 18px; }
        .article-cell { max-width: 360px; white-space: normal; }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid"><?php include __DIR__ . '/../app/layout/navbar.php'; ?></div>
    </nav>
    <?php include __DIR__ . '/../app/layout/menu.php'; ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <h3 class="mb-0">Carga de archivos de ventas</h3>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <?php if ($mensajeCorrecto): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($mensajeCorrecto, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($mensajeError): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <?php if ($tabActivo === 'carga'): ?>
                <div class="card card-primary card-outline mb-4">
                    <div class="card-header"><div class="card-title"><b>Cargar ventas</b></div></div>
                    <form action="../controller/controller_importar_ventas.php" method="post" enctype="multipart/form-data">
                        <div class="card-body">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_conciliacion_ventas'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="upload-drop">
                                <label class="form-label" for="archivosVentas"><b>Archivos Excel (.xlsx)</b></label>
                                <input class="form-control" type="file" id="archivosVentas" name="archivos_ventas[]" accept=".xlsx" multiple required>
                                <div class="form-text mt-2">
                                    Puedes cargar un dia, varios archivos diarios o un reporte semanal. Los dias incluidos se sustituyen completos antes de insertar la nueva informacion.
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-arrow-up"></i> Cargar y conciliar</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($tabActivo === 'resultados'): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3 align-items-end" method="get">
                            <input type="hidden" name="tab" value="resultados">
                            <div class="col-md-3">
                                <label class="form-label"><b>Fecha inicial</b></label>
                                <input class="form-control" type="date" name="fecha_inicio" value="<?= htmlspecialchars($filtros['fecha_inicio'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><b>Fecha final</b></label>
                                <input class="form-control" type="date" name="fecha_fin" value="<?= htmlspecialchars($filtros['fecha_fin'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><b>Estado</b></label>
                                <select class="form-select" name="estado">
                                    <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todas</option>
                                    <option value="registradas" <?= $estado === 'registradas' ? 'selected' : '' ?>>En Recompensas</option>
                                    <option value="faltantes" <?= $estado === 'faltantes' ? 'selected' : '' ?>>Faltantes</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-grid">
                                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Aplicar filtros</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-xl-3"><div class="metric-card"><div class="metric-label">Remisiones de concreto vendidas</div><div class="metric-value"><?= number_format($total) ?></div><small><?= number_format((float) ($resumen['metros_venta'] ?? 0), 2) ?> m&sup3;</small></div></div>
                    <div class="col-md-6 col-xl-3"><div class="metric-card success"><div class="metric-label">Registradas en Recompensas</div><div class="metric-value text-success"><?= number_format($registradas) ?></div><small><?= number_format((float) ($resumen['metros_registrados'] ?? 0), 2) ?> m&sup3;</small></div></div>
                    <div class="col-md-6 col-xl-3"><div class="metric-card danger"><div class="metric-label">Remisiones faltantes</div><div class="metric-value text-danger"><?= number_format($faltantes) ?></div><small><?= number_format(max(0, (float) ($resumen['metros_venta'] ?? 0) - (float) ($resumen['metros_registrados'] ?? 0)), 2) ?> m&sup3;</small></div></div>
                    <div class="col-md-6 col-xl-3"><div class="metric-card info"><div class="metric-label">Cobertura de Recompensas</div><div class="metric-value"><?= number_format($porcentaje, 1) ?>%</div><div class="coverage-track mt-2"><div class="coverage-fill" style="width: <?= min(100, max(0, $porcentaje)) ?>%"></div></div></div></div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-7">
                        <div class="card h-100">
                            <div class="card-header"><b>Cobertura por dia</b></div>
                            <div class="card-body"><canvas id="graficaCobertura" height="120"></canvas></div>
                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="card h-100">
                            <div class="card-header"><b>Resumen diario</b></div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm table-striped align-middle">
                                    <thead><tr><th>Fecha</th><th>Vendidas</th><th>Dentro</th><th>Faltan</th><th>%</th><th>m&sup3;</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($porDia as $dia):
                                        $diaTotal = (int) $dia['remisiones_venta'];
                                        $diaDentro = (int) $dia['remisiones_registradas'];
                                        $diaPorcentaje = $diaTotal > 0 ? $diaDentro * 100 / $diaTotal : 0;
                                    ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($dia['fecha'])) ?></td>
                                            <td><?= number_format($diaTotal) ?></td>
                                            <td class="text-success fw-bold"><?= number_format($diaDentro) ?></td>
                                            <td class="text-danger fw-bold"><?= number_format($diaTotal - $diaDentro) ?></td>
                                            <td><?= number_format($diaPorcentaje, 1) ?>%</td>
                                            <td><?= number_format((float) $dia['metros_venta'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$porDia): ?><tr><td colspan="6" class="text-center text-muted">No hay ventas de concreto en el periodo.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <b>Cobertura por vendedor</b>
                        <span class="text-muted small">Una remision se cuenta una sola vez</span>
                    </div>
                    <div class="card-body table-responsive">
                        <div style="height:300px" class="mb-4">
                            <canvas id="graficaVendedores"></canvas>
                        </div>
                        <table id="tablaVendedores" class="table table-striped table-bordered table-sm align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Vendedor</th>
                                    <th>Remisiones vendidas</th>
                                    <th>En Recompensas</th>
                                    <th>Faltantes</th>
                                    <th>Cobertura</th>
                                    <th>Metros vendidos</th>
                                    <th>Metros en Recompensas</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($porVendedor as $vendedor):
                                $totalVendedor = (int) $vendedor['remisiones_venta'];
                                $registradasVendedor = (int) $vendedor['remisiones_registradas'];
                                $faltantesVendedor = max(0, $totalVendedor - $registradasVendedor);
                                $porcentajeVendedor = $totalVendedor > 0 ? $registradasVendedor * 100 / $totalVendedor : 0;
                            ?>
                                <tr>
                                    <td><b><?= htmlspecialchars((string) $vendedor['vendedor'], ENT_QUOTES, 'UTF-8') ?></b></td>
                                    <td><?= number_format($totalVendedor) ?></td>
                                    <td class="text-success fw-bold"><?= number_format($registradasVendedor) ?></td>
                                    <td class="text-danger fw-bold"><?= number_format($faltantesVendedor) ?></td>
                                    <td data-order="<?= $porcentajeVendedor ?>">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:10px;min-width:90px">
                                                <div class="progress-bar bg-success" style="width:<?= min(100, max(0, $porcentajeVendedor)) ?>%"></div>
                                            </div>
                                            <b><?= number_format($porcentajeVendedor, 1) ?>%</b>
                                        </div>
                                    </td>
                                    <td><?= number_format((float) $vendedor['metros_venta'], 2) ?> m&sup3;</td>
                                    <td><?= number_format((float) $vendedor['metros_registrados'], 2) ?> m&sup3;</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <b>Detalle de remisiones de concreto y thermoconcreto</b>
                        <span class="badge text-bg-secondary"><?= number_format(count($detalle)) ?> resultados</span>
                    </div>
                    <div class="card-body table-responsive">
                        <table id="tablaConciliacion" class="table table-striped table-bordered table-sm align-middle" style="width:100%">
                            <thead><tr><th>Fecha</th><th>Remision</th><th>Pedido</th><th>Factura</th><th>Planta</th><th>Vendedor</th><th>Cliente</th><th>Articulo</th><th>Metros</th><th>Recompensas</th></tr></thead>
                            <tbody>
                            <?php foreach ($detalle as $fila): ?>
                                <tr>
                                    <td data-order="<?= htmlspecialchars($fila['fecha']) ?>"><?= date('d/m/Y', strtotime($fila['fecha'])) ?></td>
                                    <td><b><?= htmlspecialchars($fila['remision'], ENT_QUOTES, 'UTF-8') ?></b></td>
                                    <td><?= htmlspecialchars((string) $fila['pedido'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $fila['factura'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $fila['planta'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $fila['vendedor'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $fila['cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="article-cell"><?= htmlspecialchars((string) $fila['articulos'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format((float) $fila['metros'], 2) ?></td>
                                    <td><?= (int) $fila['en_recompensas'] === 1 ? '<span class="badge text-bg-success">SI</span>' : '<span class="badge text-bg-danger">FALTA</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($tabActivo === 'carga'): ?>
                <div class="card mb-4">
                    <div class="card-header"><b>Ultimas cargas</b></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead><tr><th>Fecha de carga</th><th>Archivo(s)</th><th>Periodo sustituido</th><th>Dias</th><th>Filas</th></tr></thead>
                            <tbody>
                            <?php foreach ($cargas as $carga): ?>
                                <tr><td><?= date('d/m/Y H:i', strtotime($carga['fecha_carga'])) ?></td><td><?= htmlspecialchars($carga['archivos'], ENT_QUOTES, 'UTF-8') ?></td><td><?= date('d/m/Y', strtotime($carga['fecha_inicio'])) ?> - <?= date('d/m/Y', strtotime($carga['fecha_fin'])) ?></td><td><?= number_format((int) $carga['dias_reemplazados']) ?></td><td><?= number_format((int) $carga['filas_importadas']) ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (!$cargas): ?><tr><td colspan="5" class="text-center text-muted">Todavia no hay cargas.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/../app/layout/footer.php'; ?>
</div>
<?php include __DIR__ . '/../app/layout/footer_links.php'; ?>
<script>
$(function () {
    $('#tablaVendedores').DataTable({
        pageLength: 25,
        order: [[1, 'desc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' }
    });
    $('#tablaConciliacion').DataTable({
        pageLength: 25,
        order: [[0, 'desc'], [1, 'desc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' }
    });
});

const datosDias = <?= json_encode(array_reverse($porDia), JSON_UNESCAPED_UNICODE) ?>;
const canvasCobertura = document.getElementById('graficaCobertura');
if (canvasCobertura) new Chart(canvasCobertura, {
    type: 'bar',
    data: {
        labels: datosDias.map(d => d.fecha.split('-').reverse().join('/')),
        datasets: [
            { label: 'En Recompensas', data: datosDias.map(d => Number(d.remisiones_registradas)), backgroundColor: '#198754' },
            { label: 'Faltantes', data: datosDias.map(d => Number(d.remisiones_venta) - Number(d.remisiones_registradas)), backgroundColor: '#dc3545' }
        ]
    },
    options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } }
});

const datosVendedores = <?= json_encode($porVendedor, JSON_UNESCAPED_UNICODE) ?>;
const canvasVendedores = document.getElementById('graficaVendedores');
if (canvasVendedores) new Chart(canvasVendedores, {
    type: 'bar',
    data: {
        labels: datosVendedores.map(v => v.vendedor),
        datasets: [{
            label: 'Cobertura %',
            data: datosVendedores.map(v => Number(v.remisiones_venta) > 0 ? Number(v.remisiones_registradas) * 100 / Number(v.remisiones_venta) : 0),
            backgroundColor: datosVendedores.map(v => (Number(v.remisiones_registradas) / Math.max(1, Number(v.remisiones_venta))) >= .8 ? '#198754' : '#f0ad4e')
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, max: 100, ticks: { callback: value => value + '%' } } }
    }
});
</script>
</body>
</html>
