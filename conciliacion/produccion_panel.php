<?php
$totalEsperadasProduccion = array_sum(array_column($porOperadorProduccion, 'remisiones_esperadas'));
$totalRegistradasProduccion = array_sum(array_column($porOperadorProduccion, 'remisiones_registradas'));
$totalFaltantesProduccion = $totalEsperadasProduccion - $totalRegistradasProduccion;
$porcentajeProduccion = $totalEsperadasProduccion > 0 ? ($totalRegistradasProduccion * 100 / $totalEsperadasProduccion) : 0;
?>
<p class="text-muted small mb-3">
    Compara, por operador, cuantas remisiones anoto el dosificador en la bitacora ("Remisiones Norte/Oeste/Sur")
    contra cuantas quedaron capturadas en Recompensas. Si un operador no aparece en la lista, no ha subido nada en el periodo.
</p>

<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-3 small">
            <?php if ($estadoSyncProduccion): ?>
                <?php foreach ($estadoSyncProduccion as $sync): ?>
                    <div>
                        <b><?= htmlspecialchars($sync['planta'], ENT_QUOTES, 'UTF-8') ?>:</b>
                        <?php if ($sync['ultimo_error']): ?>
                            <span class="text-danger">error al sincronizar (<?= htmlspecialchars($sync['ultimo_error'], ENT_QUOTES, 'UTF-8') ?>)</span>
                        <?php elseif ($sync['fecha_sincronizacion']): ?>
                            <span class="text-muted">ultima sincronizacion <?= date('d/m/Y H:i', strtotime($sync['fecha_sincronizacion'])) ?></span>
                        <?php else: ?>
                            <span class="text-muted">sin sincronizar todavia</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="text-muted">Todavia no corre el cron de sincronizacion (cron/sincronizar_produccion.php).</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($errorProduccion): ?>
    <div class="alert alert-danger">Falta ejecutar la migracion database/2026_09_29_produccion_planta.sql.</div>
<?php else: ?>

    <form class="row g-3 align-items-end mb-4" method="get" action="index.php">
        <input type="hidden" name="tab" value="produccion">
        <div class="col-md-3"><label class="form-label"><b>Fecha inicial</b></label><input class="form-control" type="date" name="prod_fecha_inicio" value="<?= htmlspecialchars($filtrosProduccion['fecha_inicio'], ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="col-md-3"><label class="form-label"><b>Fecha final</b></label><input class="form-control" type="date" name="prod_fecha_fin" value="<?= htmlspecialchars($filtrosProduccion['fecha_fin'], ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="col-md-3">
            <label class="form-label"><b>Planta</b></label>
            <select class="form-select" name="prod_planta">
                <option value="">Todas</option>
                <?php foreach (PRODUCCION_PLANTAS_VALIDAS as $planta): ?>
                    <option value="<?= $planta ?>" <?= $filtrosProduccion['planta'] === $planta ? 'selected' : '' ?>><?= $planta ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-grid"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Aplicar filtros</button></div>
    </form>

    <?php
    $porPlantaProduccion = [];
    foreach ($porOperadorProduccion as $filaOperador) {
        $nombre = $filaOperador['planta'];
        $porPlantaProduccion[$nombre]['esperadas'] = ($porPlantaProduccion[$nombre]['esperadas'] ?? 0) + (int) $filaOperador['remisiones_esperadas'];
        $porPlantaProduccion[$nombre]['registradas'] = ($porPlantaProduccion[$nombre]['registradas'] ?? 0) + (int) $filaOperador['remisiones_registradas'];
    }
    ?>
    <div class="row g-3 mb-4">
        <?php foreach (PRODUCCION_PLANTAS_VALIDAS as $nombrePlanta):
            $esperadasPlanta = (int) ($porPlantaProduccion[$nombrePlanta]['esperadas'] ?? 0);
            $registradasPlanta = (int) ($porPlantaProduccion[$nombrePlanta]['registradas'] ?? 0);
            $pctPlanta = $esperadasPlanta > 0 ? $registradasPlanta * 100 / $esperadasPlanta : 0;
            $claseColorPlanta = $esperadasPlanta === 0 ? '' : ($pctPlanta >= 80 ? 'success' : ($pctPlanta >= 50 ? 'info' : 'danger'));
        ?>
            <div class="col-md-4">
                <div class="conc-metric <?= $claseColorPlanta ?>">
                    <small class="fw-bold text-uppercase">Planta <?= htmlspecialchars($nombrePlanta, ENT_QUOTES, 'UTF-8') ?></small>
                    <?php if ($esperadasPlanta > 0): ?>
                        <strong><?= number_format($pctPlanta, 1) ?>%</strong>
                        <span><b class="text-dark"><?= number_format($registradasPlanta) ?></b> de <b class="text-dark"><?= number_format($esperadasPlanta) ?></b> remisiones ingresadas</span>
                        <div class="progress mt-2" style="height:10px"><div class="progress-bar bg-<?= $claseColorPlanta === 'danger' ? 'danger' : ($claseColorPlanta === 'info' ? 'info' : 'success') ?>" style="width:<?= min(100, max(0, $pctPlanta)) ?>%"></div></div>
                        <span class="mt-1">Faltan <?= number_format($esperadasPlanta - $registradasPlanta) ?></span>
                    <?php else: ?>
                        <strong class="text-muted">&mdash;</strong>
                        <span>Sin remisiones en la bitacora en el periodo</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3"><div class="conc-metric"><small>Remisiones en la bitacora del dosificador</small><strong><?= number_format($totalEsperadasProduccion) ?></strong></div></div>
        <div class="col-md-6 col-xl-3"><div class="conc-metric success"><small>Registradas en Recompensas</small><strong class="text-success"><?= number_format($totalRegistradasProduccion) ?></strong></div></div>
        <div class="col-md-6 col-xl-3"><div class="conc-metric danger"><small>Faltantes</small><strong class="text-danger"><?= number_format($totalFaltantesProduccion) ?></strong></div></div>
        <div class="col-md-6 col-xl-3"><div class="conc-metric info"><small>Cobertura</small><strong><?= number_format($porcentajeProduccion, 1) ?>%</strong><div class="progress mt-2" style="height:12px"><div class="progress-bar bg-success" style="width:<?= min(100, max(0, $porcentajeProduccion)) ?>%"></div></div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><b>Por operador</b></div>
        <div class="card-body table-responsive">
            <table id="tablaProduccionOperador" class="table table-striped table-bordered table-sm align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>Planta</th>
                        <th>Operador</th>
                        <th>Esperadas</th>
                        <th>Registradas</th>
                        <th>Faltantes</th>
                        <th>Cobertura</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($porOperadorProduccion as $fila):
                        $esperadas = (int) $fila['remisiones_esperadas'];
                        $registradas = (int) $fila['remisiones_registradas'];
                        $faltan = $esperadas - $registradas;
                        $pct = $esperadas > 0 ? ($registradas * 100 / $esperadas) : 0;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['planta'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($fila['operador'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format($esperadas) ?></td>
                            <td><?= number_format($registradas) ?></td>
                            <td class="<?= $faltan > 0 ? 'text-danger fw-bold' : '' ?>"><?= number_format($faltan) ?></td>
                            <td data-order="<?= $pct ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:10px;min-width:80px">
                                        <div class="progress-bar <?= $pct >= 80 ? 'bg-success' : 'bg-warning' ?>" style="width:<?= min(100, max(0, $pct)) ?>%"></div>
                                    </div>
                                    <b><?= number_format($pct, 1) ?>%</b>
                                </div>
                            </td>
                            <td>
                                <?php if ($faltan > 0): ?>
                                    <a class="btn btn-sm btn-outline-danger" href="?<?= http_build_query(array_merge($_GET, ['tab' => 'produccion', 'prod_operador' => $fila['operador']])) ?>#faltantesOperadorProduccion">Ver faltantes</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$porOperadorProduccion): ?>
                        <tr><td colspan="7" class="text-center text-muted">Sin datos en el periodo.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($operadorProduccionSeleccionado !== ''): ?>
        <div class="card mb-0" id="faltantesOperadorProduccion">
            <div class="card-header d-flex justify-content-between align-items-center">
                <b>Remisiones faltantes de <?= htmlspecialchars($operadorProduccionSeleccionado, ENT_QUOTES, 'UTF-8') ?></b>
                <span class="badge text-bg-secondary"><?= number_format(count($faltantesOperadorProduccion)) ?> resultados</span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-striped table-bordered align-middle">
                    <thead><tr><th>Fecha</th><th>Planta</th><th>Folio</th><th>Volumen</th><th>Direccion</th></tr></thead>
                    <tbody>
                        <?php foreach ($faltantesOperadorProduccion as $fila): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($fila['fecha'])) ?></td>
                                <td><?= htmlspecialchars($fila['planta'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><b><?= htmlspecialchars($fila['folio_remision'], ENT_QUOTES, 'UTF-8') ?></b></td>
                                <td><?= number_format((float) $fila['volumen'], 2) ?> m&sup3;</td>
                                <td><?= htmlspecialchars((string) $fila['direccion'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$faltantesOperadorProduccion): ?>
                            <tr><td colspan="5" class="text-center text-muted">Sin faltantes en el periodo.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#tablaProduccionOperador').DataTable({
        pageLength: 25,
        order: [[4, 'desc']],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });
});
</script>
