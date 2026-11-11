<?php
$etiquetasEstado = samsaraEtiquetasEstado();
$conteoEstados = [];
foreach ($comparativoSamsara['remisiones'] as $filaConteo) {
    $estadoConteo = $filaConteo['analisis']['estado'] ?? 'sin_datos';
    $conteoEstados[$estadoConteo] = ($conteoEstados[$estadoConteo] ?? 0) + 1;
}
$totalComparadas = count($comparativoSamsara['remisiones']);
$camionesCambiados = count(array_filter($comparativoSamsara['remisiones'], static fn($r) => $r['camion_cambiado']));
$comparablesConGps = $totalComparadas - ($conteoEstados['sin_datos'] ?? 0) - ($conteoEstados['sin_camion'] ?? 0) - ($conteoEstados['error'] ?? 0) - ($conteoEstados['sin_movimiento'] ?? 0);
$porcentajeConcuerda = $comparablesConGps > 0 ? (($conteoEstados['concuerda'] ?? 0) * 100 / $comparablesConGps) : 0;
$formatoHora = static fn($marca) => $marca ? date('H:i', (int) $marca) : '-';
$formatoSigno = static fn($minutos) => $minutos === null ? '-' : (($minutos > 0 ? '+' : '') . $minutos);
?>
<p class="text-muted small mb-2">
    Compara el tiempo que marcaron los choferes en cada remision con lo que muestra el GPS de Samsara. Las columnas van en el orden del
    recorrido del camion: <b>SP</b> salida de planta &rarr; <b>LLO</b> llegada a obra &rarr; inicio y fin de la descarga (lo que marco el chofer)
    &rarr; <b>SO</b> salida de obra &rarr; <b>LLP</b> llegada a planta.
</p>
<p class="text-muted small mb-3">
    La <b>obra</b> es el lugar de la descarga y hasta <?= number_format(SAMSARA_RADIO_ZONA_METROS) ?> m a su alrededor (donde lavan la olla). El
    <b>lavado</b> es el tiempo entre el fin de la descarga y la salida de obra. Se espera que el camion este en obra el tiempo de la descarga mas
    <?= SAMSARA_LAVADO_MINUTOS ?> min de lavado, y <b>concuerda</b> si la diferencia es de <?= SAMSARA_TOLERANCIA_MINUTOS ?> min o menos.
    &quot;Min en planta&quot; incluye la espera para que lo despachen; con &quot;+&quot; el tiempo real pudo ser mayor.
    <b>Camion parado</b> significa que el GPS no lo vio moverse desde 2 horas antes hasta 1 hora despues de la remision: ese camion no fue a ninguna obra
    (por eso no se calculan sus tiempos). Si el dosificador anoto en la bitacora un camion distinto al que quedo en el sistema (cambio de camion sin
    actualizar mantenimiento), se usa el de la bitacora y se marca con <span class="badge text-bg-warning">bitácora</span>.
</p>

<form class="row g-3 align-items-end mb-4" method="get" action="index.php">
    <input type="hidden" name="tab" value="samsara">
    <div class="col-md-3"><label class="form-label"><b>Fecha inicial</b></label><input class="form-control" type="date" name="sam_fecha_inicio" value="<?= htmlspecialchars($filtrosSamsara['fecha_inicio'], ENT_QUOTES, 'UTF-8') ?>"></div>
    <div class="col-md-3"><label class="form-label"><b>Fecha final</b></label><input class="form-control" type="date" name="sam_fecha_fin" value="<?= htmlspecialchars($filtrosSamsara['fecha_fin'], ENT_QUOTES, 'UTF-8') ?>"></div>
    <div class="col-md-3">
        <label class="form-label"><b>Camion</b></label>
        <select class="form-select" name="sam_camion">
            <option value="">Todos</option>
            <?php foreach ($camionesSamsara as $nombreCamion): ?>
                <option value="<?= htmlspecialchars($nombreCamion, ENT_QUOTES, 'UTF-8') ?>" <?= $filtrosSamsara['camion'] === $nombreCamion ? 'selected' : '' ?>><?= htmlspecialchars($nombreCamion, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3 d-grid"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Comparar</button></div>
</form>

<?php if ($comparativoSamsara['error']): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($comparativoSamsara['error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($comparativoSamsara['recortado']): ?>
    <div class="alert alert-warning">Se compararon solo las primeras <?= SAMSARA_MAX_REMISIONES ?> remisiones. Acota las fechas o elige un camion para ver el resto.</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl"><div class="conc-metric"><small>Remisiones comparadas</small><strong><?= number_format($totalComparadas) ?></strong><span><?= number_format($camionesCambiados) ?> con camion de la bitacora</span></div></div>
    <div class="col-6 col-xl"><div class="conc-metric success"><small>Concuerdan</small><strong class="text-success"><?= number_format($conteoEstados['concuerda'] ?? 0) ?></strong><span><?= number_format($porcentajeConcuerda, 0) ?>% de las que tienen GPS</span></div></div>
    <div class="col-6 col-xl"><div class="conc-metric"><small>Samsara mayor</small><strong class="text-warning"><?= number_format($conteoEstados['samsara_mayor'] ?? 0) ?></strong><span>estuvo mas de lo esperado</span></div></div>
    <div class="col-6 col-xl"><div class="conc-metric danger"><small>Samsara menor</small><strong class="text-danger"><?= number_format($conteoEstados['samsara_menor'] ?? 0) ?></strong><span>estuvo menos de lo esperado</span></div></div>
    <div class="col-6 col-xl"><div class="conc-metric danger"><small>Marcaron en planta</small><strong class="text-danger"><?= number_format($conteoEstados['en_planta'] ?? 0) ?></strong></div></div>
    <div class="col-6 col-xl"><div class="conc-metric danger"><small>Camion parado</small><strong class="text-danger"><?= number_format($conteoEstados['sin_movimiento'] ?? 0) ?></strong><span>no se movio en esa remision</span></div></div>
    <div class="col-6 col-xl"><div class="conc-metric"><small>Sin datos / sin camion</small><strong><?= number_format(($conteoEstados['sin_datos'] ?? 0) + ($conteoEstados['sin_camion'] ?? 0) + ($conteoEstados['error'] ?? 0)) ?></strong></div></div>
</div>

<div class="card mb-4">
    <div class="card-header"><b>Remision por remision</b></div>
    <div class="card-body table-responsive">
        <table id="tablaSamsara" class="table table-sm table-striped table-bordered align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Camion</th>
                    <th>Chofer</th>
                    <th title="Minutos que estuvo el camion en la planta antes de salir a esta obra (incluye la espera de despacho)">Min en planta</th>
                    <th title="Tiempo aproximado de carga: minutos con las RPM del motor altas (la olla girando) y el camion detenido en la planta, justo antes de salir. Samsara da una muestra cada ~2 min, asi que puede variar +/- 2 min. Pasa el cursor sobre el dato para ver la hora">Min carga (aprox.)</th>
                    <th title="Salida de planta">SP</th>
                    <th title="Minutos de la salida de planta a la llegada a obra">Min traslado a obra</th>
                    <th title="Llegada a obra: cuando el camion entra a la zona de obra">LLO</th>
                    <th title="Minutos entre la llegada a obra y el inicio de la descarga marcado por el chofer">Min espera</th>
                    <th title="Inicio de la descarga marcado por el chofer">Inicio descarga</th>
                    <th title="Fin de la descarga marcado por el chofer">Fin descarga</th>
                    <th title="Minutos entre el inicio y el fin de la descarga marcados">Min descarga</th>
                    <th title="Minutos entre el fin de la descarga y la salida de obra">Min lavado</th>
                    <th title="Salida de obra: cuando el camion sale de la zona de obra">SO</th>
                    <th title="Minutos de la salida de obra a la llegada a planta">Min traslado a planta</th>
                    <th title="Llegada a planta">LLP</th>
                    <th title="Minutos entre la llegada a obra y la salida de obra">Min en obra</th>
                    <th title="Min en obra menos (min descarga + <?= SAMSARA_LAVADO_MINUTOS ?> min de lavado). Dentro de mas o menos <?= SAMSARA_TOLERANCIA_MINUTOS ?> min concuerda">Diferencia</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comparativoSamsara['remisiones'] as $fila):
                    $analisis = $fila['analisis'] ?? ['estado' => 'sin_datos'];
                    [$textoEstado, $colorEstado] = $etiquetasEstado[$analisis['estado']] ?? ['-', 'secondary'];
                    $diferencia = $analisis['diferencia_min'] ?? null;
                    $plantaMin = $analisis['planta_min'] ?? null;
                ?>
                    <tr>
                        <td><b><?= htmlspecialchars($fila['folio_remision'], ENT_QUOTES, 'UTF-8') ?></b></td>
                        <td>
                            <?= htmlspecialchars($fila['camion_real'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($fila['camion_cambiado']): ?>
                                <span class="badge text-bg-warning" title="En el sistema esta remision quedo con el camion <?= htmlspecialchars($fila['camion_sistema'], ENT_QUOTES, 'UTF-8') ?>; la bitacora del dosificador dice <?= htmlspecialchars($fila['camion_real'], ENT_QUOTES, 'UTF-8') ?>. Se uso el de la bitacora.">bitácora<br>(sistema: <?= htmlspecialchars($fila['camion_sistema'], ENT_QUOTES, 'UTF-8') ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($fila['operador'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td data-order="<?= (int) ($plantaMin ?? -1) ?>"><?= $plantaMin !== null ? $plantaMin . (!empty($analisis['planta_truncado']) ? '+' : '') : '-' ?></td>
                        <td data-order="<?= (int) ($analisis['carga_min'] ?? -1) ?>"<?= !empty($analisis['carga_inicio']) ? ' title="Carga de ' . $formatoHora($analisis['carga_inicio']) . ' a ' . $formatoHora($analisis['carga_fin']) . '"' : '' ?>><?= $analisis['carga_min'] ?? '-' ?></td>
                        <td data-order="<?= (int) ($analisis['planta_salida'] ?? 0) ?>"><?= $formatoHora($analisis['planta_salida'] ?? null) ?></td>
                        <td data-order="<?= (int) ($analisis['traslado_ida_min'] ?? -1) ?>"><?= $analisis['traslado_ida_min'] ?? '-' ?></td>
                        <td data-order="<?= (int) ($analisis['llegada'] ?? 0) ?>"><?= $formatoHora($analisis['llegada'] ?? null) ?></td>
                        <td data-order="<?= (int) ($analisis['espera_antes_min'] ?? -1) ?>"><?= $analisis['espera_antes_min'] ?? '-' ?></td>
                        <td data-order="<?= (int) $fila['inicio_ts'] ?>"><?= date('d/m H:i', (int) $fila['inicio_ts']) ?></td>
                        <td data-order="<?= (int) $fila['fin_ts'] ?>"><?= date('H:i', (int) $fila['fin_ts']) ?></td>
                        <td data-order="<?= (int) ($analisis['marcado_min'] ?? 0) ?>"><?= (int) ($analisis['marcado_min'] ?? round(((int) $fila['fin_ts'] - (int) $fila['inicio_ts']) / 60)) ?></td>
                        <td data-order="<?= (int) ($analisis['despues_fin_min'] ?? -1) ?>"><?= $analisis['despues_fin_min'] ?? '-' ?></td>
                        <td data-order="<?= (int) ($analisis['salida'] ?? 0) ?>"><?= $formatoHora($analisis['salida'] ?? null) ?></td>
                        <td data-order="<?= (int) ($analisis['traslado_vuelta_min'] ?? -1) ?>"><?= $analisis['traslado_vuelta_min'] ?? '-' ?></td>
                        <td data-order="<?= (int) ($analisis['regreso_planta'] ?? 0) ?>"><?= $formatoHora($analisis['regreso_planta'] ?? null) ?></td>
                        <td data-order="<?= (int) ($analisis['samsara_min'] ?? -1) ?>"><?= $analisis['samsara_min'] ?? '-' ?></td>
                        <td data-order="<?= (int) ($diferencia ?? 0) ?>" class="<?= $diferencia !== null && abs($diferencia) > SAMSARA_TOLERANCIA_MINUTOS ? 'fw-bold ' . ($diferencia > 0 ? 'text-warning-emphasis' : 'text-danger') : '' ?>"><?= $formatoSigno($diferencia) ?></td>
                        <td><span class="badge text-bg-<?= $colorEstado ?>"><?= htmlspecialchars($textoEstado, ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#tablaSamsara').DataTable({
        pageLength: 25,
        order: [[9, 'desc']],
        scrollX: true,
        columnDefs: [{ targets: [3, 4, 5, 6, 7, 8, 10, 11, 12, 13, 14, 15, 16, 17], type: 'num' }],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });
});
</script>
