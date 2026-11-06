<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/prospectos.php');
verificarSesion();

if (!prospectoRolPermitido()) {
    http_response_code(403);
    exit('No tienes permiso para acceder a la prospeccion de obras.');
}

if (empty($_SESSION['csrf_prospectos'])) {
    $_SESSION['csrf_prospectos'] = bin2hex(random_bytes(32));
}

$esAdmin = prospectoEsAdministracion();
$esSuperAdmin = ($_SESSION['rol'] ?? '') === 'ADMINISTRADOR';
$tabActivo = ($esAdmin && ($_GET['tab'] ?? '') === 'resumen') ? 'resumen' : 'obras';

$falloTablas = false;
$filas = [];
$vendedores = [];
$competidores = [];
try {
    $filas = prospectoListar($pdo, $esAdmin ? null : (int) ($_SESSION['id_usuario_login'] ?? 0));
    $vendedores = $esAdmin ? prospectoVendedores($pdo) : [];
    $competidores = prospectoCompetidoresConocidos($pdo);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $falloTablas = true;
}

$ind = prospectoIndicadores($filas);
$resumen = ($esAdmin && $tabActivo === 'resumen') ? prospectoResumen($filas) : null;
if ($resumen) {
    // Tambien salen los vendedores que todavia no han capturado nada.
    foreach ($vendedores as $v) {
        $resumen['por_vendedor'][$v['nombre']] ??= [
            'obras' => 0, 'abiertas' => 0, 'ganadas' => 0, 'perdidas' => 0,
            'm3_abiertos' => 0.0, 'm3_ganados' => 0.0, 'm3_perdidos' => 0.0, 'olvidadas' => 0,
        ];
    }
    ksort($resumen['por_vendedor']);
}

$obrasJs = [];
foreach ($filas as $f) {
    $obrasJs[(int) $f['id_prospecto']] = [
        'id' => (int) $f['id_prospecto'],
        'id_usuario' => (int) $f['id_usuario'],
        'vendedor' => $f['vendedor'],
        'cliente' => $f['cliente'],
        'telefono' => $f['telefono'],
        'proyecto' => $f['proyecto'],
        'direccion' => $f['direccion'],
        'latitud' => $f['latitud'] !== null ? (float) $f['latitud'] : null,
        'longitud' => $f['longitud'] !== null ? (float) $f['longitud'] : null,
        'tipo_obra' => $f['tipo_obra'],
        'tipos_concreto' => $f['tipos_concreto'],
        'volumen_m3' => (float) $f['volumen_m3'],
        'fecha_inicio' => $f['fecha_inicio'],
        'fecha_fin' => $f['fecha_fin'],
        'estatus' => $f['estatus'],
        'etiqueta_estatus' => prospectoEtiquetaEstatus($f['estatus']),
        'competidor' => $f['competidor'],
        'motivo_perdida' => $f['motivo_perdida'],
        'motivo_detalle' => $f['motivo_detalle'],
        'comentarios' => $f['comentarios'],
    ];
}
$flagsJs = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

function m3Prospecto(float $valor): string
{
    return number_format($valor, 0) . ' m&sup3;';
}

function fechaProspecto(?string $fecha): string
{
    return $fecha ? date('d/m/Y', strtotime($fecha)) : '';
}

function h(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Prospeccion de obras</title>
    <style>
        .pros-metric { border: 1px solid #dfe5ec; border-left: 5px solid #174a94; border-radius: 10px; background: #fff; padding: 12px 16px; height: 100%; }
        .pros-metric small { display: block; color: #64748b; }
        .pros-metric strong { font-size: 1.5rem; color: #172033; }
        .pros-metric.ok { border-left-color: #198754; }
        .pros-metric.mal { border-left-color: #dc3545; }
        .pros-metric.alerta { border-left-color: #f59f00; }
        .pros-barra { height: 10px; background: #e9eef5; border-radius: 5px; overflow: hidden; min-width: 80px; }
        .pros-barra > div { height: 100%; background: #174a94; }
        .pros-linea { border-left: 3px solid #dfe5ec; padding: 0 0 14px 14px; position: relative; }
        .pros-linea:last-child { padding-bottom: 0; }
        .pros-linea::before { content: ''; position: absolute; left: -7px; top: 3px; width: 11px; height: 11px; border-radius: 50%; background: #174a94; }
        .pros-nota { white-space: pre-wrap; }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <?php include('../app/layout/navbar.php'); ?>
            </div>
        </nav>

        <?php include('../app/layout/menu.php'); ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <h3 class="mb-0">Prospeccion de obras</h3>
                    <button type="button" class="btn btn-primary" id="btnNuevaObra"><i class="bi bi-plus-lg"></i> Nueva obra</button>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <?php if ($falloTablas): ?>
                        <div class="alert alert-warning">Falta ejecutar la migracion database/2026_11_05_prospectos_obras.sql; todavia no se pueden capturar obras.</div>
                    <?php endif; ?>

                    <?php if ($esAdmin): ?>
                        <ul class="nav nav-tabs mb-3">
                            <li class="nav-item"><a class="nav-link <?= $tabActivo === 'obras' ? 'active' : '' ?>" href="index.php">Obras</a></li>
                            <li class="nav-item"><a class="nav-link <?= $tabActivo === 'resumen' ? 'active' : '' ?>" href="index.php?tab=resumen">Resumen por vendedor</a></li>
                        </ul>
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-lg"><div class="pros-metric"><small>Obras en proceso</small><strong><?= number_format($ind['abiertas']) ?></strong> <span class="text-muted"><?= m3Prospecto($ind['m3_abiertos']) ?></span></div></div>
                        <div class="col-6 col-lg"><div class="pros-metric ok"><small>Ganadas</small><strong><?= number_format($ind['ganadas']) ?></strong> <span class="text-muted"><?= m3Prospecto($ind['m3_ganados']) ?></span></div></div>
                        <div class="col-6 col-lg"><div class="pros-metric mal"><small>Perdidas</small><strong><?= number_format($ind['perdidas']) ?></strong> <span class="text-muted"><?= m3Prospecto($ind['m3_perdidos']) ?></span></div></div>
                        <div class="col-6 col-lg"><div class="pros-metric"><small>Cierre (ganadas de las cerradas)</small><strong><?= $ind['cierre'] === null ? '-' : number_format($ind['cierre'], 0) . '%' ?></strong></div></div>
                        <div class="col-12 col-lg"><div class="pros-metric alerta"><small>Sin movimiento hace <?= PROSPECTO_DIAS_SIN_MOVIMIENTO ?>+ dias</small><strong><?= number_format($ind['olvidadas']) ?></strong></div></div>
                    </div>

                    <?php if ($tabActivo === 'resumen' && $resumen): ?>
                        <?php
                        $totalObras = max(1, $ind['total']);
                        ?>
                        <div class="card card-primary card-outline mb-4">
                            <div class="card-header"><b>Por vendedor</b></div>
                            <div class="card-body p-2 table-responsive">
                                <table class="table table-striped table-bordered table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Vendedor</th><th class="text-end">Obras</th><th class="text-end">En proceso</th><th class="text-end">m&sup3; en proceso</th>
                                            <th class="text-end">Ganadas</th><th class="text-end">m&sup3; ganados</th><th class="text-end">Perdidas</th><th class="text-end">m&sup3; perdidos</th>
                                            <th class="text-end">Cierre</th><th class="text-end">Sin movimiento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resumen['por_vendedor'] as $nombre => $v):
                                            $cerradas = $v['ganadas'] + $v['perdidas'];
                                        ?>
                                            <tr>
                                                <td><b><?= h($nombre) ?></b><?= $v['obras'] === 0 ? ' <span class="badge text-bg-secondary">Sin obras capturadas</span>' : '' ?></td>
                                                <td class="text-end"><?= number_format($v['obras']) ?></td>
                                                <td class="text-end"><?= number_format($v['abiertas']) ?></td>
                                                <td class="text-end"><?= number_format($v['m3_abiertos'], 0) ?></td>
                                                <td class="text-end text-success"><?= number_format($v['ganadas']) ?></td>
                                                <td class="text-end text-success"><?= number_format($v['m3_ganados'], 0) ?></td>
                                                <td class="text-end text-danger"><?= number_format($v['perdidas']) ?></td>
                                                <td class="text-end text-danger"><?= number_format($v['m3_perdidos'], 0) ?></td>
                                                <td class="text-end"><?= $cerradas > 0 ? number_format($v['ganadas'] * 100 / $cerradas, 0) . '%' : '-' ?></td>
                                                <td class="text-end"><?= $v['olvidadas'] > 0 ? '<span class="badge text-bg-warning">' . $v['olvidadas'] . '</span>' : '0' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-4">
                                <div class="card card-outline card-primary h-100">
                                    <div class="card-header"><b>Obras por estatus</b></div>
                                    <div class="card-body">
                                        <?php foreach ($resumen['por_estatus'] as $codigo => $e): ?>
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between small">
                                                    <span><?= prospectoBadgeEstatus($codigo) ?></span>
                                                    <span><b><?= number_format($e['obras']) ?></b> obras &middot; <?= number_format($e['m3'], 0) ?> m&sup3;</span>
                                                </div>
                                                <div class="pros-barra mt-1"><div style="width:<?= round($e['obras'] * 100 / $totalObras) ?>%"></div></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card card-outline card-danger h-100">
                                    <div class="card-header"><b>Por que se pierden las obras</b></div>
                                    <div class="card-body">
                                        <?php if (!$resumen['motivos']): ?><div class="text-muted">Todavia no hay obras perdidas.</div><?php endif; ?>
                                        <?php foreach ($resumen['motivos'] as $motivo => $m): ?>
                                            <div class="d-flex justify-content-between border-bottom py-1 small">
                                                <span><?= h($motivo) ?></span>
                                                <span><b><?= number_format($m['obras']) ?></b> obras &middot; <?= number_format($m['m3'], 0) ?> m&sup3;</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card card-outline card-danger h-100">
                                    <div class="card-header"><b>Competidores que se llevaron obras</b></div>
                                    <div class="card-body">
                                        <?php if (!$resumen['competidores']): ?><div class="text-muted">Todavia no hay obras perdidas contra un competidor.</div><?php endif; ?>
                                        <?php foreach ($resumen['competidores'] as $nombre => $c): ?>
                                            <div class="d-flex justify-content-between border-bottom py-1 small">
                                                <span><?= h($nombre) ?></span>
                                                <span><b><?= number_format($c['obras']) ?></b> obras &middot; <?= number_format($c['m3'], 0) ?> m&sup3;</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php $o = $esAdmin ? 1 : 0; ?>
                        <div class="card card-primary card-outline mb-4">
                            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                                <div class="card-title"><b><?= $esAdmin ? 'Obras de todos los vendedores' : 'Mis obras' ?></b></div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if ($esAdmin): ?>
                                        <select id="filtroVendedor" class="form-select form-select-sm" style="width:auto">
                                            <option value="">Vendedor: todos</option>
                                            <?php foreach ($vendedores as $v): ?>
                                                <option value="<?= h($v['nombre']) ?>"><?= h($v['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                    <select id="filtroEstatus" class="form-select form-select-sm" style="width:auto">
                                        <option value="">Estatus: todos</option>
                                        <option value="ABIERTA">En proceso (no cerradas)</option>
                                        <?php foreach (PROSPECTO_ESTATUS as $codigo => [$etiqueta]): ?>
                                            <option value="<?= $codigo ?>"><?= h($etiqueta) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select id="filtroTipo" class="form-select form-select-sm" style="width:auto">
                                        <option value="">Tipo de obra: todos</option>
                                        <?php foreach (PROSPECTO_TIPOS_OBRA as $tipo): ?>
                                            <option value="<?= h($tipo) ?>"><?= h($tipo) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body p-2">
                                <div class="table-responsive">
                                    <table id="tablaObras" class="table table-striped table-bordered align-middle" style="width:100%">
                                        <thead>
                                            <tr>
                                                <?php if ($esAdmin): ?><th>Vendedor</th><?php endif; ?>
                                                <th>Cliente</th>
                                                <th>Proyecto</th>
                                                <th>Tipo de obra</th>
                                                <th>Concreto</th>
                                                <th>m&sup3;</th>
                                                <th>Inicio de obra</th>
                                                <th>Fin de obra</th>
                                                <th>Estatus</th>
                                                <th>Competidor</th>
                                                <th>Ultimo movimiento</th>
                                                <th>Ubicacion</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($filas as $f):
                                                $abierta = in_array($f['estatus'], PROSPECTO_ESTATUS_ABIERTOS, true);
                                                $dias = prospectoDiasSinMovimiento($f);
                                                $olvidada = $abierta && $dias >= PROSPECTO_DIAS_SIN_MOVIMIENTO;
                                            ?>
                                                <tr>
                                                    <?php if ($esAdmin): ?><td><?= h($f['vendedor']) ?></td><?php endif; ?>
                                                    <td>
                                                        <b><?= h($f['cliente']) ?></b>
                                                        <?php if ($f['telefono']): ?>
                                                            <div class="small text-nowrap"><a href="tel:+52<?= h($f['telefono']) ?>" title="Llamar"><i class="bi bi-telephone-fill"></i> <?= h($f['telefono']) ?></a></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= h($f['proyecto']) ?></td>
                                                    <td><?= h($f['tipo_obra']) ?></td>
                                                    <td><?= h($f['tipos_concreto']) ?></td>
                                                    <td class="text-end" data-order="<?= (float) $f['volumen_m3'] ?>"><?= number_format((float) $f['volumen_m3'], 2) ?></td>
                                                    <td data-order="<?= h($f['fecha_inicio'] ?? '') ?>"><?= fechaProspecto($f['fecha_inicio']) ?></td>
                                                    <td data-order="<?= h($f['fecha_fin'] ?? '') ?>"><?= fechaProspecto($f['fecha_fin']) ?></td>
                                                    <td data-search="<?= $f['estatus'] ?> <?= $abierta ? 'ABIERTA' : '' ?>">
                                                        <?= prospectoBadgeEstatus($f['estatus']) ?>
                                                        <?php if ($f['estatus'] === 'PERDIDA' && $f['motivo_perdida']): ?>
                                                            <div class="small text-muted"><?= h($f['motivo_perdida']) ?><?= $f['motivo_detalle'] ? ': ' . h($f['motivo_detalle']) : '' ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= h($f['competidor']) ?></td>
                                                    <td data-order="<?= strtotime($f['ultimo_movimiento']) ?>">
                                                        <?= date('d/m/Y', strtotime($f['ultimo_movimiento'])) ?>
                                                        <?php if ($olvidada): ?>
                                                            <span class="badge text-bg-warning" title="Obra en proceso sin ningun movimiento">hace <?= $dias ?> dias</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php $urlMapa = prospectoUrlMapa($f); ?>
                                                        <?= h($f['direccion']) ?>
                                                        <?php if ($urlMapa !== ''): ?>
                                                            <a href="<?= h($urlMapa) ?>" target="_blank" rel="noopener" class="text-nowrap" title="Abrir en Google Maps"><i class="bi bi-geo-alt-fill"></i> Ver mapa</a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <button type="button" class="btn btn-sm btn-primary btn-seguimiento" data-id="<?= (int) $f['id_prospecto'] ?>"><i class="bi bi-chat-left-text"></i> Seguimiento</button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-editar" data-id="<?= (int) $f['id_prospecto'] ?>" title="Editar los datos de la obra"><i class="bi bi-pencil"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php include('../app/layout/footer.php'); ?>
    </div>

    <!-- Alta / edicion de la obra -->
    <div class="modal fade" id="modalObra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="formObra" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalObra">Nueva obra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_prospecto" id="obraId">
                    <div class="row g-3">
                        <?php if ($esAdmin): ?>
                            <div class="col-md-6" id="bloqueVendedorObra">
                                <label class="form-label" for="obraVendedor"><b>Vendedor</b></label>
                                <select class="form-select" name="id_usuario" id="obraVendedor">
                                    <option value="">Elige el vendedor</option>
                                    <?php foreach ($vendedores as $v): ?>
                                        <option value="<?= (int) $v['id_usuario'] ?>"><?= h($v['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label" for="obraCliente"><b>Cliente</b></label>
                            <input class="form-control" type="text" name="cliente" id="obraCliente" maxlength="150" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="obraTelefono"><b>Telefono de contacto</b></label>
                            <input class="form-control" type="tel" name="telefono" id="obraTelefono" maxlength="20" inputmode="tel" placeholder="10 digitos, ej. 614 123 4567">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="obraProyecto"><b>Nombre del proyecto</b></label>
                            <input class="form-control" type="text" name="proyecto" id="obraProyecto" maxlength="200" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="obraTipo"><b>Tipo de obra</b></label>
                            <select class="form-select" name="tipo_obra" id="obraTipo" required>
                                <option value="">Elige el tipo</option>
                                <?php foreach (PROSPECTO_TIPOS_OBRA as $tipo): ?>
                                    <option value="<?= h($tipo) ?>"><?= h($tipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="<?= $esAdmin ? 'col-md-6' : 'col-12' ?>">
                            <label class="form-label" for="obraConcreto"><b>Tipos de concreto</b></label>
                            <input class="form-control" type="text" name="tipos_concreto" id="obraConcreto" maxlength="200" placeholder="Ej. F'C 250 BOMBEABLE, F'C 150">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="obraDireccion"><b>Direccion de la obra</b></label>
                            <input class="form-control" type="text" name="direccion" id="obraDireccion" maxlength="255" placeholder="Calle, numero, colonia, ciudad">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="obraUbicacion"><b>Ubicacion en Google Maps</b></label>
                            <div class="input-group">
                                <input class="form-control" type="text" name="ubicacion" id="obraUbicacion" placeholder="Coordenadas o enlace de Google Maps">
                                <button type="button" class="btn btn-outline-primary" id="btnUbicacionActual" title="Toma la ubicacion de este telefono o computadora"><i class="bi bi-crosshair"></i> Usar mi ubicacion actual</button>
                            </div>
                            <div class="form-text" id="ayudaUbicacion">Estando en la obra, toca el boton. O pega las coordenadas (28.6353, -106.0889) o el enlace largo de Google Maps.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="obraVolumen"><b>Volumen total (m&sup3;)</b></label>
                            <input class="form-control" type="number" name="volumen_m3" id="obraVolumen" min="0.01" max="500000" step="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="obraInicio"><b>Inicio de obra</b></label>
                            <input class="form-control" type="date" name="fecha_inicio" id="obraInicio">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="obraFin"><b>Fin de obra</b></label>
                            <input class="form-control" type="date" name="fecha_fin" id="obraFin">
                        </div>
                        <div class="col-md-3" id="bloqueEstatusObra">
                            <label class="form-label" for="obraEstatus"><b>Estatus</b></label>
                            <select class="form-select" name="estatus" id="obraEstatus">
                                <?php foreach (PROSPECTO_ESTATUS_ABIERTOS as $codigo): ?>
                                    <option value="<?= $codigo ?>"><?= h(prospectoEtiquetaEstatus($codigo)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="obraCompetidor"><b>Competidor</b> <span class="text-muted small">(si lo conoces)</span></label>
                            <input class="form-control" type="text" name="competidor" id="obraCompetidor" maxlength="80" list="listaCompetidores">
                            <datalist id="listaCompetidores">
                                <?php foreach ($competidores as $c): ?><option value="<?= h($c) ?>"><?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="obraComentarios"><b>Comentarios</b></label>
                            <textarea class="form-control" name="comentarios" id="obraComentarios" rows="3" maxlength="2000"></textarea>
                        </div>
                    </div>
                    <div class="alert alert-danger d-none mt-3 mb-0" id="errorObra"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarObra">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Seguimiento de la obra -->
    <div class="modal fade" id="modalSeguimiento" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="segTitulo"></h5>
                        <div class="small text-muted" id="segSubtitulo"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="formSeguimiento" autocomplete="off">
                        <input type="hidden" id="segId">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="segEstatus"><b>Estatus</b></label>
                                <select class="form-select" id="segEstatus">
                                    <?php foreach (PROSPECTO_ESTATUS as $codigo => [$etiqueta]): ?>
                                        <option value="<?= $codigo ?>"><?= h($etiqueta) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-7 d-none" id="bloqueMotivo">
                                <label class="form-label" for="segMotivo"><b>Por que se perdio</b></label>
                                <select class="form-select" id="segMotivo">
                                    <option value="">Elige el motivo</option>
                                    <?php foreach (PROSPECTO_MOTIVOS_PERDIDA as $motivo): ?>
                                        <option value="<?= h($motivo) ?>"><?= h($motivo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 d-none" id="bloqueMotivoDetalle">
                                <label class="form-label" for="segMotivoDetalle"><b>Cual fue el motivo</b> <span class="text-muted small">(obligatorio en "Otro")</span></label>
                                <input class="form-control" type="text" id="segMotivoDetalle" maxlength="150">
                            </div>
                            <div class="col-md-12 d-none" id="bloqueCompetidor">
                                <label class="form-label" for="segCompetidor"><b>Competidor</b> <span class="text-muted small" id="segCompetidorAyuda"></span></label>
                                <input class="form-control" type="text" id="segCompetidor" maxlength="80" list="listaCompetidores">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="segNota"><b>Nota</b> <span class="text-muted small">(que paso, que se acordo, que sigue)</span></label>
                                <textarea class="form-control" id="segNota" rows="3" maxlength="2000"></textarea>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-3 mb-0" id="errorSeguimiento"></div>
                        <div class="d-flex justify-content-between mt-3">
                            <div>
                                <?php if ($esSuperAdmin): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnEliminarObra"><i class="bi bi-trash3"></i> Eliminar obra</button>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary" id="btnGuardarSeguimiento"><i class="bi bi-check2"></i> Guardar seguimiento</button>
                        </div>
                    </form>
                    <hr>
                    <h6 class="fw-bold">Historial</h6>
                    <div id="segHistorial" class="ms-2"></div>
                </div>
            </div>
        </div>
    </div>

    <?php include('../app/layout/footer_links.php'); ?>
    <script>
        $(document).ready(function() {
            const csrfToken = <?= json_encode($_SESSION['csrf_prospectos']) ?>;
            const OBRAS = <?= json_encode($obrasJs ?: new stdClass(), $flagsJs) ?>;
            const MOTIVO_OTRO = <?= json_encode(PROSPECTO_MOTIVO_OTRO, $flagsJs) ?>;
            const ESTATUS_ABIERTOS = <?= json_encode(PROSPECTO_ESTATUS_ABIERTOS) ?>;
            const esAdmin = <?= $esAdmin ? 'true' : 'false' ?>;
            const urlControlador = '../controller/controller_prospecto.php';
            const escapar = valor => String(valor ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));

            function enviar(datos) {
                const cuerpo = new URLSearchParams(datos);
                cuerpo.set('csrf_token', csrfToken);
                return fetch(urlControlador, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: cuerpo
                }).then(r => r.json().catch(() => ({ ok: false, error: 'Respuesta no valida del servidor.' })));
            }

            function mostrarError(caja, texto) {
                caja.textContent = texto;
                caja.classList.remove('d-none');
            }

            // ---- Tabla y filtros ----
            if (document.getElementById('tablaObras')) {
                const o = esAdmin ? 1 : 0;
                const tabla = $('#tablaObras').DataTable({
                    order: [[o + 9, 'desc']],
                    pageLength: 25,
                    columnDefs: [{ orderable: false, targets: [o + 11] }],
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
                });
                $('#filtroVendedor').on('change', function() {
                    tabla.column(0).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '', true, false).draw();
                });
                $('#filtroEstatus').on('change', function() {
                    tabla.column(o + 7).search(this.value, false, false).draw();
                });
                $('#filtroTipo').on('change', function() {
                    tabla.column(o + 2).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '', true, false).draw();
                });
            }

            // ---- Alta y edicion ----
            const modalObra = new bootstrap.Modal(document.getElementById('modalObra'));
            const formObra = document.getElementById('formObra');
            const errorObra = document.getElementById('errorObra');

            function abrirObra(obra) {
                formObra.reset();
                errorObra.classList.add('d-none');
                document.getElementById('obraId').value = obra ? obra.id : '';
                document.getElementById('tituloModalObra').textContent = obra ? 'Editar obra' : 'Nueva obra';
                document.getElementById('bloqueEstatusObra').classList.toggle('d-none', !!obra);
                const bloqueVendedor = document.getElementById('bloqueVendedorObra');
                if (bloqueVendedor) {
                    bloqueVendedor.classList.toggle('d-none', !!obra);
                }
                if (obra) {
                    document.getElementById('obraCliente').value = obra.cliente;
                    document.getElementById('obraTelefono').value = obra.telefono || '';
                    document.getElementById('obraProyecto').value = obra.proyecto;
                    document.getElementById('obraDireccion').value = obra.direccion || '';
                    document.getElementById('obraUbicacion').value = (obra.latitud !== null && obra.longitud !== null)
                        ? obra.latitud + ', ' + obra.longitud : '';
                    document.getElementById('obraTipo').value = obra.tipo_obra;
                    document.getElementById('obraConcreto').value = obra.tipos_concreto;
                    document.getElementById('obraVolumen').value = obra.volumen_m3;
                    document.getElementById('obraInicio').value = obra.fecha_inicio || '';
                    document.getElementById('obraFin').value = obra.fecha_fin || '';
                    document.getElementById('obraCompetidor').value = obra.competidor || '';
                    document.getElementById('obraComentarios').value = obra.comentarios || '';
                }
                modalObra.show();
            }

            document.getElementById('btnNuevaObra').addEventListener('click', () => abrirObra(null));

            // Toma la ubicacion del dispositivo. En celular pide permiso la primera vez; solo funciona con https.
            const ayudaUbicacion = document.getElementById('ayudaUbicacion');
            const textoAyudaUbicacion = ayudaUbicacion.textContent;
            document.getElementById('btnUbicacionActual').addEventListener('click', function() {
                const boton = this;
                if (!navigator.geolocation) {
                    ayudaUbicacion.textContent = 'Este navegador no puede dar la ubicacion. Pega las coordenadas o el enlace de Google Maps.';
                    return;
                }
                boton.disabled = true;
                ayudaUbicacion.textContent = 'Buscando tu ubicacion...';
                navigator.geolocation.getCurrentPosition(function(posicion) {
                    document.getElementById('obraUbicacion').value =
                        posicion.coords.latitude.toFixed(7) + ', ' + posicion.coords.longitude.toFixed(7);
                    ayudaUbicacion.textContent = 'Ubicacion tomada (precision de unos ' + Math.round(posicion.coords.accuracy) + ' m).';
                    boton.disabled = false;
                }, function(error) {
                    ayudaUbicacion.textContent = error.code === 1
                        ? 'No diste permiso para usar la ubicacion. Activalo en el navegador o pega las coordenadas.'
                        : 'No se pudo obtener la ubicacion. Intenta de nuevo o pega las coordenadas.';
                    boton.disabled = false;
                }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
            });
            document.getElementById('modalObra').addEventListener('show.bs.modal', function() {
                ayudaUbicacion.textContent = textoAyudaUbicacion;
            });

            formObra.addEventListener('submit', function(evento) {
                evento.preventDefault();
                errorObra.classList.add('d-none');
                const boton = document.getElementById('btnGuardarObra');
                boton.disabled = true;
                const datos = Object.fromEntries(new FormData(formObra).entries());
                datos.accion = 'guardar';
                enviar(datos).then(respuesta => {
                    if (!respuesta.ok) {
                        throw new Error(respuesta.error || 'No se pudo guardar.');
                    }
                    window.location.reload();
                }).catch(error => {
                    mostrarError(errorObra, error.message);
                    boton.disabled = false;
                });
            });

            // ---- Seguimiento ----
            const modalSeg = new bootstrap.Modal(document.getElementById('modalSeguimiento'));
            const segEstatus = document.getElementById('segEstatus');
            const segMotivo = document.getElementById('segMotivo');
            const errorSeg = document.getElementById('errorSeguimiento');

            function ajustarCamposEstatus() {
                const perdida = segEstatus.value === 'PERDIDA';
                const ganada = segEstatus.value === 'GANADA';
                document.getElementById('bloqueMotivo').classList.toggle('d-none', !perdida);
                document.getElementById('bloqueCompetidor').classList.toggle('d-none', ganada);
                document.getElementById('bloqueMotivoDetalle').classList.toggle('d-none', !(perdida && segMotivo.value === MOTIVO_OTRO));
                document.getElementById('segCompetidorAyuda').textContent =
                    perdida ? '(si se la llevo alguno, cual)' : '(si lo conoces)';
            }
            segEstatus.addEventListener('change', ajustarCamposEstatus);
            segMotivo.addEventListener('change', ajustarCamposEstatus);

            function pintarHistorial(seguimientos) {
                const caja = document.getElementById('segHistorial');
                if (!seguimientos.length) {
                    caja.innerHTML = '<div class="text-muted">Todavia no hay movimientos.</div>';
                    return;
                }
                caja.innerHTML = seguimientos.map(s => {
                    const cambio = (s.estatus_anterior && s.estatus_anterior !== s.estatus_nuevo)
                        ? '<span class="badge text-bg-light border">' + escapar(s.etiqueta_anterior) + '</span> &rarr; ' : '';
                    return '<div class="pros-linea">'
                        + '<div class="small text-muted">' + escapar(s.fecha_texto) + ' &middot; ' + escapar(s.autor || '') + '</div>'
                        + '<div>' + cambio + '<span class="badge text-bg-' + escapar(s.color_nuevo) + '">' + escapar(s.etiqueta_nueva) + '</span></div>'
                        + (s.nota ? '<div class="pros-nota mt-1">' + escapar(s.nota) + '</div>' : '')
                        + '</div>';
                }).join('');
            }

            function cargarHistorial(id) {
                document.getElementById('segHistorial').innerHTML = '<div class="text-muted">Cargando...</div>';
                enviar({ accion: 'detalle', id_prospecto: id }).then(respuesta => {
                    if (!respuesta.ok) {
                        throw new Error(respuesta.error || 'No se pudo cargar el historial.');
                    }
                    pintarHistorial(respuesta.seguimientos);
                }).catch(error => {
                    document.getElementById('segHistorial').innerHTML = '<div class="text-danger">' + escapar(error.message) + '</div>';
                });
            }

            function abrirSeguimiento(obra) {
                errorSeg.classList.add('d-none');
                document.getElementById('segId').value = obra.id;
                document.getElementById('segTitulo').textContent = obra.cliente + ' - ' + obra.proyecto;
                document.getElementById('segSubtitulo').textContent =
                    (esAdmin ? obra.vendedor + ' · ' : '') + obra.tipo_obra + ' · '
                    + Number(obra.volumen_m3).toLocaleString('es-MX', { maximumFractionDigits: 2 }) + ' m³';
                segEstatus.value = obra.estatus;
                segMotivo.value = obra.motivo_perdida || '';
                document.getElementById('segMotivoDetalle').value = obra.motivo_detalle || '';
                document.getElementById('segCompetidor').value = obra.estatus === 'GANADA' ? '' : (obra.competidor || '');
                document.getElementById('segNota').value = '';
                ajustarCamposEstatus();
                cargarHistorial(obra.id);
                modalSeg.show();
            }

            document.addEventListener('click', function(evento) {
                const botonSeg = evento.target.closest('.btn-seguimiento');
                const botonEditar = evento.target.closest('.btn-editar');
                if (botonSeg && OBRAS[botonSeg.dataset.id]) {
                    abrirSeguimiento(OBRAS[botonSeg.dataset.id]);
                } else if (botonEditar && OBRAS[botonEditar.dataset.id]) {
                    abrirObra(OBRAS[botonEditar.dataset.id]);
                }
            });

            document.getElementById('formSeguimiento').addEventListener('submit', function(evento) {
                evento.preventDefault();
                errorSeg.classList.add('d-none');
                const boton = document.getElementById('btnGuardarSeguimiento');
                boton.disabled = true;
                enviar({
                    accion: 'seguimiento',
                    id_prospecto: document.getElementById('segId').value,
                    estatus: segEstatus.value,
                    motivo_perdida: segMotivo.value,
                    motivo_detalle: document.getElementById('segMotivoDetalle').value,
                    competidor: document.getElementById('segCompetidor').value,
                    nota: document.getElementById('segNota').value
                }).then(respuesta => {
                    if (!respuesta.ok) {
                        throw new Error(respuesta.error || 'No se pudo guardar.');
                    }
                    window.location.reload();
                }).catch(error => {
                    mostrarError(errorSeg, error.message);
                    boton.disabled = false;
                });
            });

            const botonEliminar = document.getElementById('btnEliminarObra');
            if (botonEliminar) {
                botonEliminar.addEventListener('click', function() {
                    const id = document.getElementById('segId').value;
                    Swal.fire({
                        title: 'Eliminar esta obra?',
                        text: 'Deja de aparecer en la lista y en el resumen. Queda registrado en la bitacora de cambios.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Si, eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc3545'
                    }).then(resultado => {
                        if (!resultado.isConfirmed) {
                            return;
                        }
                        enviar({ accion: 'eliminar', id_prospecto: id }).then(respuesta => {
                            if (!respuesta.ok) {
                                throw new Error(respuesta.error || 'No se pudo eliminar.');
                            }
                            window.location.reload();
                        }).catch(error => mostrarError(errorSeg, error.message));
                    });
                });
            }
        });
    </script>
</body>
</html>
