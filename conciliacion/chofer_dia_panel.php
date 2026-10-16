<?php
$totalEsperadasChofer = array_sum(array_column($diasChofer, 'esperadas'));
$totalRegistradasChofer = array_sum(array_column($diasChofer, 'registradas'));
$totalFaltantesChofer = $totalEsperadasChofer - $totalRegistradasChofer;
$cumplimientoChofer = $totalEsperadasChofer > 0 ? ($totalRegistradasChofer * 100 / $totalEsperadasChofer) : 0;
$etiquetaDia = static fn(array $d) => $d['dia'] . ' ' . date('d/m', strtotime($d['fecha']));
?>
<p class="text-muted small mb-3">
    Muestra, dia por dia, cuantas remisiones debio ingresar un chofer segun la bitacora del dosificador
    ("Remisiones Norte/Oeste/Sur") y cuantas de esas ya estan capturadas en Recompensas.
</p>

<?php if ($errorChofer): ?>
    <div class="alert alert-danger">Falta ejecutar la migracion database/2026_09_29_produccion_planta.sql.</div>
<?php else: ?>

    <form class="row g-3 align-items-end mb-4" method="get" action="index.php">
        <input type="hidden" name="tab" value="chofer">
        <div class="col-md-4">
            <label class="form-label"><b>Chofer</b></label>
            <select class="form-select" name="chofer_operador" required>
                <option value="">Selecciona un chofer</option>
                <?php foreach ($operadoresProduccion as $nombreOperador): ?>
                    <option value="<?= htmlspecialchars($nombreOperador, ENT_QUOTES, 'UTF-8') ?>" <?= $choferSeleccionado === $nombreOperador ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombreOperador, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><label class="form-label"><b>Fecha inicial</b></label><input class="form-control" type="date" name="chofer_fecha_inicio" value="<?= htmlspecialchars($filtrosChofer['fecha_inicio'], ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="col-md-2"><label class="form-label"><b>Fecha final</b></label><input class="form-control" type="date" name="chofer_fecha_fin" value="<?= htmlspecialchars($filtrosChofer['fecha_fin'], ENT_QUOTES, 'UTF-8') ?>"></div>
        <div class="col-md-2">
            <label class="form-label"><b>Planta</b></label>
            <select class="form-select" name="chofer_planta">
                <option value="">Todas</option>
                <?php foreach (PRODUCCION_PLANTAS_VALIDAS as $planta): ?>
                    <option value="<?= $planta ?>" <?= $filtrosChofer['planta'] === $planta ? 'selected' : '' ?>><?= $planta ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Ver</button></div>
    </form>

    <div class="mb-4">
        <h6 class="fw-bold mb-1">Mejores choferes por planta</h6>
        <p class="text-muted small mb-3">
            Los 4 con mayor porcentaje de remisiones capturadas sobre las que debieron capturar en el periodo.
            Solo cuentan choferes con al menos 10 remisiones en la bitacora. Da clic en un nombre para ver su detalle dia por dia.
        </p>
        <?php if (!$topChoferes): ?>
            <div class="text-muted">No hay choferes con suficientes remisiones en el periodo.</div>
        <?php endif; ?>
        <div class="row g-3">
            <?php foreach ($topChoferes as $nombrePlanta => $choferesPlanta): ?>
                <div class="col-lg-4">
                    <div class="conc-metric">
                        <small class="fw-bold text-uppercase mb-2">Planta <?= htmlspecialchars($nombrePlanta, ENT_QUOTES, 'UTF-8') ?></small>
                        <?php foreach ($choferesPlanta as $lugar => $chofer):
                            $colorTop = $chofer['porcentaje'] >= 80 ? 'success' : ($chofer['porcentaje'] >= 50 ? 'info' : 'danger');
                            $enlaceChofer = 'index.php?' . http_build_query([
                                'tab' => 'chofer',
                                'chofer_operador' => $chofer['operador'],
                                'chofer_fecha_inicio' => $filtrosChofer['fecha_inicio'],
                                'chofer_fecha_fin' => $filtrosChofer['fecha_fin'],
                                'chofer_planta' => $nombrePlanta,
                            ]);
                        ?>
                            <div class="py-2 <?= $lugar > 0 ? 'border-top' : '' ?>">
                                <div class="d-flex justify-content-between align-items-baseline gap-2">
                                    <span class="text-truncate"><b class="text-muted">#<?= $lugar + 1 ?></b>
                                        <a href="<?= htmlspecialchars($enlaceChofer, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none fw-semibold" title="<?= htmlspecialchars($chofer['operador'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($chofer['operador'], ENT_QUOTES, 'UTF-8') ?></a>
                                    </span>
                                    <b class="text-<?= $colorTop === 'info' ? 'primary' : $colorTop ?> text-nowrap"><?= number_format($chofer['porcentaje'], 1) ?>%</b>
                                </div>
                                <div class="progress mt-1" style="height:6px"><div class="progress-bar bg-<?= $colorTop ?>" style="width:<?= min(100, max(0, $chofer['porcentaje'])) ?>%"></div></div>
                                <div class="small text-muted mt-1"><?= number_format($chofer['registradas']) ?> de <?= number_format($chofer['esperadas']) ?> remisiones ingresadas</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($choferSeleccionado === ''): ?>
        <div class="alert alert-info mb-0">Selecciona un chofer para ver como ha ido capturando dia por dia.</div>
    <?php else: ?>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3"><div class="conc-metric"><small>Debio ingresar</small><strong><?= number_format($totalEsperadasChofer) ?></strong></div></div>
            <div class="col-md-6 col-xl-3"><div class="conc-metric success"><small>Ingreso</small><strong class="text-success"><?= number_format($totalRegistradasChofer) ?></strong></div></div>
            <div class="col-md-6 col-xl-3"><div class="conc-metric danger"><small>Faltan</small><strong class="text-danger"><?= number_format($totalFaltantesChofer) ?></strong></div></div>
            <div class="col-md-6 col-xl-3"><div class="conc-metric info"><small>Cumplimiento</small><strong><?= number_format($cumplimientoChofer, 1) ?>%</strong><div class="progress mt-2" style="height:12px"><div class="progress-bar bg-success" style="width:<?= min(100, max(0, $cumplimientoChofer)) ?>%"></div></div></div></div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><b><?= htmlspecialchars($choferSeleccionado, ENT_QUOTES, 'UTF-8') ?></b> &middot; remisiones por dia</div>
            <div class="card-body">
                <?php if ($totalEsperadasChofer === 0): ?>
                    <p class="text-muted mb-0">Este chofer no tiene remisiones en la bitacora en el periodo elegido.</p>
                <?php else: ?>
                    <div style="height:340px"><canvas id="graficaChoferDia"></canvas></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($totalEsperadasChofer > 0): ?>
            <div class="row g-4">
                <div class="col-xl-5">
                    <div class="card h-100">
                        <div class="card-header"><b>Detalle por dia</b></div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead><tr><th>Dia</th><th>Debio</th><th>Ingreso</th><th>Faltan</th><th>%</th></tr></thead>
                                <tbody>
                                    <?php foreach (array_reverse($diasChofer) as $d):
                                        if ($d['esperadas'] === 0 && $d['registradas'] === 0) { continue; }
                                        $pctDia = $d['esperadas'] > 0 ? $d['registradas'] * 100 / $d['esperadas'] : 0;
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($etiquetaDia($d), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= $d['esperadas'] ?></td>
                                            <td class="text-success fw-bold"><?= $d['registradas'] ?></td>
                                            <td class="<?= $d['faltantes'] > 0 ? 'text-danger fw-bold' : '' ?>"><?= $d['faltantes'] ?></td>
                                            <td><?= number_format($pctDia, 0) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-7">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <b>Folios que no ha capturado</b>
                            <span class="badge text-bg-secondary"><?= number_format(count($faltantesChofer)) ?></span>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                <thead><tr><th>Fecha</th><th>Planta</th><th>Folio</th><th>Volumen</th><th>Direccion</th></tr></thead>
                                <tbody>
                                    <?php foreach ($faltantesChofer as $fila): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($fila['fecha'])) ?></td>
                                            <td><?= htmlspecialchars($fila['planta'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><b><?= htmlspecialchars($fila['folio_remision'], ENT_QUOTES, 'UTF-8') ?></b></td>
                                            <td><?= number_format((float) $fila['volumen'], 2) ?> m&sup3;</td>
                                            <td><?= htmlspecialchars((string) $fila['direccion'], ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$faltantesChofer): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No le falta ninguna en el periodo.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const dias = <?= json_encode(array_map(static fn($d) => [
                    'etiqueta' => $etiquetaDia($d),
                    'esperadas' => $d['esperadas'],
                    'registradas' => $d['registradas'],
                ], $diasChofer), JSON_UNESCAPED_UNICODE) ?>;
                new Chart(document.getElementById('graficaChoferDia'), {
                    type: 'bar',
                    data: {
                        labels: dias.map(d => d.etiqueta),
                        datasets: [
                            { label: 'Debio ingresar', data: dias.map(d => d.esperadas), backgroundColor: '#9aa8bd' },
                            { label: 'Ingreso', data: dias.map(d => d.registradas), backgroundColor: '#198754' }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                        plugins: { tooltip: { mode: 'index', intersect: false } }
                    }
                });
            });
            </script>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
