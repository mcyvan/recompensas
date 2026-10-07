<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once("../app/functions/remisiones.php");
require_once("../app/functions/conciliacion_ventas.php");
require_once("../app/functions/produccion_planta.php");

verificarSesion();
verificarPermisoRemisiones();

if (empty($_SESSION['csrf_remisiones'])) {
    $_SESSION['csrf_remisiones'] = bin2hex(random_bytes(32));
}

$mensajeCorrecto = $_SESSION['mensaje_remision_correcto'] ?? null;
$mensajeError = $_SESSION['mensaje_remision_error'] ?? null;
unset($_SESSION['mensaje_remision_correcto'], $_SESSION['mensaje_remision_error']);

$filtrosListado = filtrosListadoRemisiones($_GET);
$remisiones = obtenerRemisiones($pdo, $filtrosListado);
$csrf = $_SESSION['csrf_remisiones'];
$puedeAdministrar = puedeAdministrarRemisiones();
$puedeEliminarRemisiones = ($_SESSION['rol'] ?? '') === 'ADMINISTRADOR';
$operadores = obtenerOperadores($pdo);
$vendedores = obtenerVendedoresComercialesReporte($pdo);
$filtrosReporte = filtrosReporteRemisiones($_GET, $pdo);
$resumenReporte = obtenerResumenReporteRemisiones($pdo, $filtrosReporte);
$reporteClientes = obtenerReporteRemisionesPorCliente($pdo, $filtrosReporte);
$reporteVendedores = obtenerReporteRemisionesPorVendedor($pdo, $filtrosReporte);
$reporteChoferes = obtenerReporteRemisionesPorChofer($pdo, $filtrosReporte);
$reporteDias = obtenerReporteRemisionesPorDia($pdo, $filtrosReporte);
try {
    $sinClienteChoferes = obtenerMedicionSinClientePorChofer($pdo, $filtrosReporte);
    $sinClienteVendedores = obtenerMedicionSinClientePorVendedor($pdo, $filtrosReporte);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $sinClienteChoferes = [];
    $sinClienteVendedores = [];
}
$totalSinCliente = array_sum(array_column($sinClienteChoferes, 'sin_cliente'));
$totalPendientesSinCliente = array_sum(array_column($sinClienteChoferes, 'pendientes'));
$tabSolicitado = (string) ($_GET['tab'] ?? '');
$tabActivo = in_array($tabSolicitado, ['reportes', 'conciliacion', 'produccion'], true) ? $tabSolicitado : 'remisiones';

if ($tabActivo === 'conciliacion') {
    $filtrosConciliacion = filtrosConciliacionVentas($pdo, [
        'fecha_inicio' => $_GET['conc_fecha_inicio'] ?? '',
        'fecha_fin' => $_GET['conc_fecha_fin'] ?? '',
        'planta' => $_GET['conc_planta'] ?? '',
    ]);
    $plantasConciliacion = obtenerPlantasConciliacion($pdo);
    $resumenConciliacion = obtenerResumenConciliacion($pdo, $filtrosConciliacion);
    $porDiaConciliacion = obtenerConciliacionPorDia($pdo, $filtrosConciliacion);
    $porVendedorConciliacion = obtenerConciliacionPorVendedor($pdo, $filtrosConciliacion);
    $detalleConciliacion = obtenerDetalleConciliacion($pdo, $filtrosConciliacion);
    $estadoConciliacion = strtolower(trim((string) ($_GET['conc_estado'] ?? 'todos')));
    if (!in_array($estadoConciliacion, ['todos', 'registradas', 'faltantes'], true)) {
        $estadoConciliacion = 'todos';
    }
    if ($estadoConciliacion !== 'todos') {
        $valorEsperado = $estadoConciliacion === 'registradas' ? 1 : 0;
        $detalleConciliacion = array_values(array_filter(
            $detalleConciliacion,
            static fn($fila) => (int) $fila['en_recompensas'] === $valorEsperado
        ));
    }
}

if ($tabActivo === 'produccion') {
    $filtrosProduccion = filtrosProduccionPlanta([
        'fecha_inicio' => $_GET['prod_fecha_inicio'] ?? '',
        'fecha_fin' => $_GET['prod_fecha_fin'] ?? '',
        'planta' => $_GET['prod_planta'] ?? '',
    ]);

    try {
        $porOperadorProduccion = obtenerProduccionPorOperador($pdo, $filtrosProduccion);
        $estadoSyncProduccion = obtenerEstadoSincronizacionProduccion($pdo);
        $errorProduccion = false;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $porOperadorProduccion = [];
        $estadoSyncProduccion = [];
        $errorProduccion = true;
    }
    $operadorProduccionSeleccionado = trim((string) ($_GET['prod_operador'] ?? ''));
    $faltantesOperadorProduccion = $operadorProduccionSeleccionado !== '' && !$errorProduccion
        ? obtenerFoliosFaltantesOperador($pdo, $operadorProduccionSeleccionado, $filtrosProduccion)
        : [];
}
?>
<!doctype html>
<html lang="es">

<head>
    <?php include("../app/layout/head.php"); ?>
    <title>Remisiones</title>
    <style>
        .dashboard-metric {
            border: 1px solid #dde4ee;
            border-left: 4px solid #174a94;
            border-radius: 8px;
            background: #ffffff;
            padding: 14px 16px;
            height: 100%;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }

        .dashboard-metric.danger {
            border-left-color: #dc3545;
        }

        .dashboard-metric.warning {
            border-left-color: #ffc107;
        }

        .dashboard-metric.success {
            border-left-color: #198754;
        }

        .dashboard-metric .label {
            color: #64748b;
            font-size: 0.82rem;
        }

        .dashboard-metric .value {
            color: #172033;
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .conc-metric { border: 1px solid #dfe5ec; border-left: 5px solid #174a94; border-radius: 10px; background: #fff; padding: 16px; height: 100%; box-shadow: 0 6px 18px rgba(15,23,42,.05); }
        .conc-metric.success { border-left-color: #198754; }
        .conc-metric.danger { border-left-color: #dc3545; }
        .conc-metric.info { border-left-color: #0dcaf0; }
        .conc-metric small, .conc-metric span { display:block; color:#64748b; }
        .conc-metric strong { display:block; color:#172033; font-size:1.65rem; line-height:1.2; }
    </style>
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <?php if ($mensajeCorrecto): ?>
        <script>
            Swal.fire({
                icon: "success",
                text: <?= json_encode($mensajeCorrecto, JSON_UNESCAPED_UNICODE) ?>
            });
        </script>
    <?php endif; ?>
    <?php if ($mensajeError): ?>
        <script>
            Swal.fire({
                icon: "error",
                text: <?= json_encode($mensajeError, JSON_UNESCAPED_UNICODE) ?>
            });
        </script>
    <?php endif; ?>

    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <?php include("../app/layout/navbar.php"); ?>
            </div>
        </nav>

        <?php include("../app/layout/menu.php"); ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Remisiones</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card card-danger card-outline">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="tabsRemisiones" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?= $tabActivo === 'remisiones' ? 'active' : '' ?>" id="tab-remisiones" href="index.php?tab=remisiones" role="tab">
                                        Remisiones
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?= $tabActivo === 'reportes' ? 'active' : '' ?>" id="tab-reportes" href="index.php?tab=reportes" role="tab">
                                        Reportes
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?= $tabActivo === 'conciliacion' ? 'active' : '' ?>" id="tab-conciliacion" href="index.php?tab=conciliacion" role="tab">
                                        Graficas y resultados
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link <?= $tabActivo === 'produccion' ? 'active' : '' ?>" id="tab-produccion" href="index.php?tab=produccion" role="tab">
                                        Produccion
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane fade <?= $tabActivo === 'remisiones' ? 'show active' : '' ?>" id="panel-remisiones" role="tabpanel" aria-labelledby="tab-remisiones" <?= $tabActivo !== 'remisiones' ? 'hidden' : '' ?>>
                                    <form class="row g-3 align-items-end mb-3" method="GET" action="index.php">
                                        <input type="hidden" name="tab" value="remisiones">
                                        <div class="col-md-2">
                                            <label class="form-label"><b>Fecha inicio</b></label>
                                            <input type="date" class="form-control" name="lista_fecha_inicio" value="<?= htmlspecialchars($filtrosListado['fecha_inicio'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label"><b>Fecha fin</b></label>
                                            <input type="date" class="form-control" name="lista_fecha_fin" value="<?= htmlspecialchars($filtrosListado['fecha_fin'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-md-2 d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-search"></i> Filtrar
                                            </button>
                                        </div>
                                        <div class="col-md-2 d-grid">
                                            <a class="btn btn-outline-secondary" href="index.php?tab=remisiones">
                                                Mes actual
                                            </a>
                                        </div>
                                    </form>
                                    <div class="table-responsive">
                                <table id="tablaRemisiones" class="table table-striped table-bordered align-middle" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Folio</th>
                                            <th>Telefono</th>
                                            <th>Cliente</th>
                                            <th>Vendedor</th>
                                            <th>Planta</th>
                                            <th>Volumen</th>
                                            <th>CRM</th>
                                            <th>Fecha inicio</th>
                                            <th>Fecha fin</th>
                                            <th>Tiempo</th>
                                            <th>Chofer / Camion</th>
                                            <?php if ($puedeAdministrar): ?>
                                                <th>Capturada por</th>
                                            <?php endif; ?>
                                            <th>Puntos</th>
                                            <th>Estatus</th>
                                            <?php if ($puedeAdministrar): ?>
                                                <th>Acciones</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($remisiones as $indice => $remision): ?>
                                            <tr>
                                                <td><?= $indice + 1 ?></td>
                                                <td><?= htmlspecialchars($remision['folio_remision'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($remision['telefono'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <?php if (empty($remision['id_cliente'])): ?>
                                                        <span class="badge text-bg-warning">Sin cliente</span>
                                                        <?php if (!empty($remision['cliente_crm'])): ?>
                                                            <div class="small text-muted">CRM: <?= htmlspecialchars($remision['cliente_crm'], ENT_QUOTES, 'UTF-8') ?></div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars(trim($remision['nombres'] . ' ' . $remision['apellido_p'] . ' ' . $remision['apellido_m']), ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($remision['vendedor'] ?? 'Sin vendedor', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <?php if (!empty($remision['planta_crm'])): ?>
                                                        <?= htmlspecialchars($remision['planta_crm'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sin planta</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= number_format((float) $remision['volumen'], 2) ?> m&sup3;</td>
                                                <td>
                                                    <?php if (!empty($remision['qr_origen'])): ?>
                                                        <div class="small">
                                                            <?php if (!empty($remision['factura_crm'])): ?>
                                                                <div><b>Factura:</b> <?= htmlspecialchars($remision['factura_crm'], ENT_QUOTES, 'UTF-8') ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($remision['pedido_crm'])): ?>
                                                                <div><b>Pedido:</b> <?= htmlspecialchars($remision['pedido_crm'], ENT_QUOTES, 'UTF-8') ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($remision['cantidad_crm'])): ?>
                                                                <div><b>Cantidad:</b> <?= number_format((float) $remision['cantidad_crm'], 2) ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($remision['articulo_crm'])): ?>
                                                                <div class="text-muted"><?= htmlspecialchars($remision['articulo_crm'], ENT_QUOTES, 'UTF-8') ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($remision['hora_inicio'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($remision['hora_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <?php if (($remision['minutos_colado'] ?? null) !== null && $remision['minutos_colado'] !== ''): ?>
                                                        <span class="badge <?= (float) $remision['minutos_colado'] <= 45 ? 'text-bg-success' : 'text-bg-danger' ?>">
                                                            <?= htmlspecialchars(formatoTiempoRemision($remision['minutos_colado']), ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div><?= htmlspecialchars($remision['operador'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <?php if (!empty($remision['camion_logistica'])): ?>
                                                        <div class="small text-muted">
                                                            <i class="bi bi-truck"></i>
                                                            <?= htmlspecialchars($remision['camion_logistica'], ENT_QUOTES, 'UTF-8') ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="small text-muted">Camion no registrado</div>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if ($puedeAdministrar): ?>
                                                    <td>
                                                        <?php if (!empty($remision['rol_captura'])): ?>
                                                            <span class="badge text-bg-secondary">
                                                                <?= htmlspecialchars($remision['rol_captura'], ENT_QUOTES, 'UTF-8') ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Sin informacion</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td><?= number_format((float) ($remision['puntos'] ?? 0), 2) ?></td>
                                                <td>
                                                    <span class="badge <?= etiquetaEstatusRemision($remision['estatus']) ?>">
                                                        <?= htmlspecialchars($remision['estatus'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <?php if ($puedeAdministrar): ?>
                                                    <td class="text-nowrap">
                                                        <a href="editar.php?id=<?= (int) $remision['id_remision'] ?>" class="btn btn-outline-warning btn-sm" title="Editar">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <?php if ($remision['estatus'] !== 'CANCELADO'): ?>
                                                            <button
                                                                type="button"
                                                                class="btn btn-outline-danger btn-sm btn-desactivar"
                                                                title="Desactivar"
                                                                data-id="<?= (int) $remision['id_remision'] ?>"
                                                                data-folio="<?= htmlspecialchars($remision['folio_remision'], ENT_QUOTES, 'UTF-8') ?>">
                                                                <i class="bi bi-slash-circle"></i>
                                                            </button>
                                                        <?php elseif ($puedeEliminarRemisiones): ?>
                                                            <button
                                                                type="button"
                                                                class="btn btn-danger btn-sm btn-eliminar-remision"
                                                                title="Eliminar definitivamente"
                                                                data-id="<?= (int) $remision['id_remision'] ?>"
                                                                data-folio="<?= htmlspecialchars($remision['folio_remision'], ENT_QUOTES, 'UTF-8') ?>">
                                                                <i class="bi bi-trash3"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade <?= $tabActivo === 'reportes' ? 'show active' : '' ?>" id="panel-reportes" role="tabpanel" aria-labelledby="tab-reportes" <?= $tabActivo !== 'reportes' ? 'hidden' : '' ?>>
                                    <form class="row g-3 align-items-end mb-3" method="GET" action="index.php">
                                        <input type="hidden" name="tab" value="reportes">
                                        <div class="col-md-2">
                                            <label class="form-label"><b>Fecha inicio</b></label>
                                            <input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars($filtrosReporte['fecha_inicio'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label"><b>Fecha fin</b></label>
                                            <input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars($filtrosReporte['fecha_fin'], ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label"><b>Cliente / telefono</b></label>
                                            <input type="text" class="form-control" name="cliente" value="<?= htmlspecialchars($filtrosReporte['cliente'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre o telefono">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label"><b>Vendedor</b></label>
                                            <select name="vendedor" class="form-control">
                                                <option value="">Todos</option>
                                                <?php foreach ($vendedores as $vendedor): ?>
                                                    <option value="<?= htmlspecialchars($vendedor, ENT_QUOTES, 'UTF-8') ?>" <?= $filtrosReporte['vendedor'] === $vendedor ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($vendedor, ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label"><b>Chofer</b></label>
                                            <select name="id_operador" class="form-control">
                                                <option value="">Todos</option>
                                                <?php foreach ($operadores as $operador): ?>
                                                    <option value="<?= (int) $operador['id_usuario'] ?>" <?= (int) ($filtrosReporte['id_operador'] ?? 0) === (int) $operador['id_usuario'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($operador['usuario'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label"><b>Estatus</b></label>
                                            <select name="estatus" class="form-control">
                                                <option value="">Todos</option>
                                                <?php foreach (['EN PROCESO', 'FINALIZADO'] as $estatus): ?>
                                                    <option value="<?= $estatus ?>" <?= $filtrosReporte['estatus'] === $estatus ? 'selected' : '' ?>>
                                                        <?= $estatus ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-1 d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                        <div class="col-md-2 d-grid">
                                            <button type="submit" class="btn btn-success" formaction="exportar_excel.php" formmethod="GET">
                                                <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                                            </button>
                                        </div>
                                    </form>

                                    <div class="row g-3 mb-3">
                                        <div class="col-xl-3 col-md-4 col-6">
                                            <div class="dashboard-metric">
                                                <div class="label">Remisiones</div>
                                                <div class="value"><?= number_format((float) ($resumenReporte['total_remisiones'] ?? 0), 0) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-4 col-6">
                                            <div class="dashboard-metric success">
                                                <div class="label">Metros vendidos</div>
                                                <div class="value"><?= number_format((float) ($resumenReporte['total_metros'] ?? 0), 2) ?> m&sup3;</div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-4 col-6">
                                            <div class="dashboard-metric">
                                                <div class="label">Puntos generados</div>
                                                <div class="value"><?= number_format((float) ($resumenReporte['total_puntos'] ?? 0), 2) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-4 col-6">
                                            <div class="dashboard-metric">
                                                <div class="label">Clientes con remision</div>
                                                <div class="value"><?= number_format((float) ($resumenReporte['total_clientes'] ?? 0), 0) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-4 col-6">
                                            <div class="dashboard-metric">
                                                <div class="label">Vendedores</div>
                                                <div class="value"><?= number_format((float) ($resumenReporte['total_vendedores'] ?? 0), 0) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-4 col-6">
                                            <div class="dashboard-metric">
                                                <div class="label">Choferes</div>
                                                <div class="value"><?= number_format((float) ($resumenReporte['total_choferes'] ?? 0), 0) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-4 col-6">
                                            <div class="dashboard-metric">
                                                <div class="label">Promedio por remision</div>
                                                <div class="value"><?= number_format((float) ($resumenReporte['promedio_metros'] ?? 0), 2) ?> m&sup3;</div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-4 col-6">
                                            <div class="dashboard-metric warning">
                                                <div class="label">Promedio descarga</div>
                                                <div class="value"><?= number_format((float) ($resumenReporte['promedio_minutos'] ?? 0), 1) ?> min</div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-md-6 col-6">
                                            <div class="dashboard-metric success">
                                                <div class="label">Finalizadas</div>
                                                <div class="value text-success"><?= number_format((float) ($resumenReporte['finalizadas'] ?? 0), 0) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6 col-md-6 col-6">
                                            <div class="dashboard-metric warning">
                                                <div class="label">En proceso</div>
                                                <div class="value text-warning"><?= number_format((float) ($resumenReporte['en_proceso'] ?? 0), 0) ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-xl-5">
                                            <h6 class="fw-bold mb-2">Por vendedor</h6>
                                            <div class="table-responsive">
                                                <table id="tablaReporteVendedores" class="table table-sm table-striped table-bordered align-middle" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Vendedor</th>
                                                            <th>Clientes</th>
                                                            <th>Remisiones</th>
                                                            <th>Metros</th>
                                                            <th>Puntos</th>
                                                            <th>Ultima</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($reporteVendedores as $fila): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($fila['vendedor'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= number_format((float) $fila['total_clientes'], 0) ?></td>
                                                                <td><?= number_format((float) $fila['total_remisiones'], 0) ?></td>
                                                                <td><?= number_format((float) $fila['total_metros'], 2) ?></td>
                                                                <td><?= number_format((float) $fila['total_puntos'], 2) ?></td>
                                                                <td><?= htmlspecialchars($fila['ultima_remision'], ENT_QUOTES, 'UTF-8') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="col-xl-7">
                                            <h6 class="fw-bold mb-2">Por cliente</h6>
                                            <div class="table-responsive">
                                                <table id="tablaReporteClientes" class="table table-sm table-striped table-bordered align-middle" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Cliente</th>
                                                            <th>Telefono</th>
                                                            <th>Vendedor</th>
                                                            <th>Remisiones</th>
                                                            <th>Metros</th>
                                                            <th>Puntos</th>
                                                            <th>Ultima</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($reporteClientes as $fila): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($fila['cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars($fila['telefono'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars($fila['vendedor'] ?? 'Sin vendedor', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= number_format((float) $fila['total_remisiones'], 0) ?></td>
                                                                <td><?= number_format((float) $fila['total_metros'], 2) ?></td>
                                                                <td><?= number_format((float) $fila['total_puntos'], 2) ?></td>
                                                                <td><?= htmlspecialchars($fila['ultima_remision'], ENT_QUOTES, 'UTF-8') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="col-xl-6">
                                            <h6 class="fw-bold mb-2">Por chofer</h6>
                                            <div class="table-responsive">
                                                <table id="tablaReporteChoferes" class="table table-sm table-striped table-bordered align-middle" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Chofer</th>
                                                            <th>Remisiones</th>
                                                            <th>Metros</th>
                                                            <th>Prom. min</th>
                                                            <th>Fuera 45</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($reporteChoferes as $fila): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($fila['operador'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= number_format((float) $fila['total_remisiones'], 0) ?></td>
                                                                <td><?= number_format((float) $fila['total_metros'], 2) ?></td>
                                                                <td><?= number_format((float) $fila['promedio_minutos'], 1) ?></td>
                                                                <td><?= number_format((float) $fila['fuera_tiempo'], 0) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="col-xl-6">
                                            <h6 class="fw-bold mb-2">Por dia</h6>
                                            <div class="table-responsive">
                                                <table id="tablaReporteDias" class="table table-sm table-striped table-bordered align-middle" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Fecha</th>
                                                            <th>Remisiones</th>
                                                            <th>Metros</th>
                                                            <th>Puntos</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($reporteDias as $fila): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($fila['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= number_format((float) $fila['total_remisiones'], 0) ?></td>
                                                                <td><?= number_format((float) $fila['total_metros'], 2) ?></td>
                                                                <td><?= number_format((float) $fila['total_puntos'], 2) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <h6 class="fw-bold mb-1">Remisiones capturadas sin cliente registrado</h6>
                                            <p class="text-muted small mb-2">
                                                En el periodo: <b><?= number_format($totalSinCliente) ?></b> remisiones se capturaron sin cliente, de las cuales
                                                <b><?= number_format($totalPendientesSinCliente) ?></b> siguen pendientes de ligar.
                                                <b>Ya existia</b> = el cliente ya estaba dado de alta antes de la descarga (omision al capturar).
                                                <b>Alta posterior</b> = el cliente se dio de alta el mismo dia o despues (vendedor).
                                            </p>
                                        </div>

                                        <div class="col-xl-6">
                                            <h6 class="fw-bold mb-2">Sin cliente por chofer</h6>
                                            <div class="table-responsive">
                                                <table id="tablaSinClienteChoferes" class="table table-sm table-striped table-bordered align-middle" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Chofer</th>
                                                            <th>Remisiones</th>
                                                            <th>Sin cliente</th>
                                                            <th>%</th>
                                                            <th>Pendientes</th>
                                                            <th>Ya existia</th>
                                                            <th>Alta posterior</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($sinClienteChoferes as $fila):
                                                            $porcentajeSinCliente = (int) $fila['total_remisiones'] > 0 ? ((int) $fila['sin_cliente'] * 100 / (int) $fila['total_remisiones']) : 0;
                                                        ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($fila['chofer'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= number_format((int) $fila['total_remisiones']) ?></td>
                                                                <td><?= number_format((int) $fila['sin_cliente']) ?></td>
                                                                <td data-order="<?= $porcentajeSinCliente ?>"><?= number_format($porcentajeSinCliente, 1) ?>%</td>
                                                                <td><?= number_format((int) $fila['pendientes']) ?></td>
                                                                <td><?= number_format((int) $fila['cliente_existia']) ?></td>
                                                                <td><?= number_format((int) $fila['alta_posterior']) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="col-xl-6">
                                            <h6 class="fw-bold mb-2">Sin cliente por vendedor</h6>
                                            <div class="table-responsive">
                                                <table id="tablaSinClienteVendedores" class="table table-sm table-striped table-bordered align-middle" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Vendedor</th>
                                                            <th>Remisiones</th>
                                                            <th>Sin cliente</th>
                                                            <th>%</th>
                                                            <th>Pendientes</th>
                                                            <th>Ya existia</th>
                                                            <th>Alta posterior</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($sinClienteVendedores as $fila):
                                                            $porcentajeSinCliente = $fila['total_remisiones'] > 0 ? ($fila['sin_cliente'] * 100 / $fila['total_remisiones']) : 0;
                                                        ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($fila['vendedor'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= number_format($fila['total_remisiones']) ?></td>
                                                                <td><?= number_format($fila['sin_cliente']) ?></td>
                                                                <td data-order="<?= $porcentajeSinCliente ?>"><?= number_format($porcentajeSinCliente, 1) ?>%</td>
                                                                <td><?= number_format($fila['pendientes']) ?></td>
                                                                <td><?= number_format($fila['cliente_existia']) ?></td>
                                                                <td><?= number_format($fila['alta_posterior']) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade <?= $tabActivo === 'conciliacion' ? 'show active' : '' ?>" id="panel-conciliacion" role="tabpanel" aria-labelledby="tab-conciliacion" <?= $tabActivo !== 'conciliacion' ? 'hidden' : '' ?>>
                                    <?php if ($tabActivo === 'conciliacion') { include __DIR__ . '/../conciliacion/resultados_panel.php'; } ?>
                                </div>
                                <div class="tab-pane fade <?= $tabActivo === 'produccion' ? 'show active' : '' ?>" id="panel-produccion" role="tabpanel" aria-labelledby="tab-produccion" <?= $tabActivo !== 'produccion' ? 'hidden' : '' ?>>
                                    <?php if ($tabActivo === 'produccion') { include __DIR__ . '/../conciliacion/produccion_panel.php'; } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include("../app/layout/footer.php"); ?>
    </div>

    <?php if ($puedeAdministrar): ?>
        <form id="formDesactivar" action="../controller/controller_desactivar_remision.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_remision" id="desactivarId">
            <input type="hidden" name="motivo" id="desactivarMotivo">
        </form>
    <?php endif; ?>

    <?php if ($puedeEliminarRemisiones): ?>
        <form id="formEliminarRemision" action="../controller/controller_eliminar_remision.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_remision" id="eliminarRemisionId">
            <input type="hidden" name="folio_confirmacion" id="eliminarRemisionFolio">
        </form>
    <?php endif; ?>

    <?php include("../app/layout/footer_links.php"); ?>
    <script>
        $(document).ready(function() {
            $('#tablaRemisiones').DataTable({
                order: [[8, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });

            $('#tablaReporteVendedores').DataTable({
                paging: false,
                searching: false,
                info: false,
                order: [[3, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });

            $('#tablaReporteClientes').DataTable({
                pageLength: 25,
                order: [[4, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });

            $('#tablaReporteChoferes').DataTable({
                paging: false,
                searching: false,
                info: false,
                order: [[2, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });

            $('#tablaSinClienteChoferes, #tablaSinClienteVendedores').DataTable({
                pageLength: 10,
                order: [[2, 'desc']],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' }
            });

            $('#tablaReporteDias').DataTable({
                paging: false,
                searching: false,
                info: false,
                order: [[0, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });

            document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', function() {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                });
            });
        });

        <?php if ($puedeAdministrar): ?>
            document.querySelectorAll('.btn-desactivar').forEach(function(boton) {
                boton.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const folio = this.dataset.folio;

                    Swal.fire({
                        icon: 'warning',
                        title: 'Desactivar remision ' + folio,
                        input: 'textarea',
                        inputLabel: 'Motivo',
                        inputPlaceholder: 'Describe el motivo de la desactivacion',
                        inputAttributes: {
                            maxlength: 500
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Desactivar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc3545',
                        inputValidator: function(valor) {
                            if (!valor || valor.trim().length < 10) {
                                return 'Ingresa un motivo de al menos 10 caracteres';
                            }
                        }
                    }).then(function(resultado) {
                        if (resultado.isConfirmed) {
                            document.getElementById('desactivarId').value = id;
                            document.getElementById('desactivarMotivo').value = resultado.value.trim();
                            document.getElementById('formDesactivar').submit();
                        }
                    });
                });
            });
        <?php endif; ?>

        <?php if ($puedeEliminarRemisiones): ?>
            document.querySelectorAll('.btn-eliminar-remision').forEach(function(boton) {
                boton.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const folio = this.dataset.folio;
                    Swal.fire({
                        icon: 'error',
                        title: 'Eliminar definitivamente ' + folio,
                        text: 'Esta accion eliminara tambien sus movimientos de puntos y no se puede deshacer. Escribe el folio para confirmar.',
                        input: 'text',
                        inputPlaceholder: folio,
                        showCancelButton: true,
                        confirmButtonText: 'Eliminar definitivamente',
                        cancelButtonText: 'Conservar',
                        confirmButtonColor: '#dc3545',
                        inputValidator: function(valor) {
                            if (!valor || valor.trim().toUpperCase() !== folio.trim().toUpperCase()) {
                                return 'Escribe exactamente el folio ' + folio;
                            }
                        }
                    }).then(function(resultado) {
                        if (resultado.isConfirmed) {
                            document.getElementById('eliminarRemisionId').value = id;
                            document.getElementById('eliminarRemisionFolio').value = resultado.value.trim().toUpperCase();
                            document.getElementById('formEliminarRemision').submit();
                        }
                    });
                });
            });
        <?php endif; ?>
    </script>
</body>

</html>
