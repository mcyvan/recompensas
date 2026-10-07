<?php
$totalConciliacion = (int) ($resumenConciliacion['remisiones_venta'] ?? 0);
$registradasConciliacion = (int) ($resumenConciliacion['remisiones_registradas'] ?? 0);
$faltantesConciliacion = (int) ($resumenConciliacion['faltantes'] ?? 0);
$porcentajeConciliacion = (float) ($resumenConciliacion['porcentaje'] ?? 0);
?>
<form class="row g-3 align-items-end mb-4" method="get" action="index.php">
    <input type="hidden" name="tab" value="conciliacion">
    <div class="col-md-2"><label class="form-label"><b>Fecha inicial</b></label><input class="form-control" type="date" name="conc_fecha_inicio" value="<?= htmlspecialchars($filtrosConciliacion['fecha_inicio'], ENT_QUOTES, 'UTF-8') ?>"></div>
    <div class="col-md-2"><label class="form-label"><b>Fecha final</b></label><input class="form-control" type="date" name="conc_fecha_fin" value="<?= htmlspecialchars($filtrosConciliacion['fecha_fin'], ENT_QUOTES, 'UTF-8') ?>"></div>
    <div class="col-md-2">
        <label class="form-label"><b>Planta</b></label>
        <select class="form-select" name="conc_planta">
            <option value="">Todas</option>
            <?php foreach ($plantasConciliacion as $planta): ?>
                <option value="<?= htmlspecialchars($planta, ENT_QUOTES, 'UTF-8') ?>" <?= $filtrosConciliacion['planta'] === $planta ? 'selected' : '' ?>><?= htmlspecialchars($planta, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label"><b>Estado</b></label>
        <select class="form-select" name="conc_estado">
            <option value="todos" <?= $estadoConciliacion === 'todos' ? 'selected' : '' ?>>Todas</option>
            <option value="registradas" <?= $estadoConciliacion === 'registradas' ? 'selected' : '' ?>>En Recompensas</option>
            <option value="faltantes" <?= $estadoConciliacion === 'faltantes' ? 'selected' : '' ?>>Faltantes</option>
        </select>
    </div>
    <div class="col-md-3 d-grid"><button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Aplicar filtros</button></div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3"><div class="conc-metric"><small>Remisiones de concreto vendidas</small><strong><?= number_format($totalConciliacion) ?></strong><span><?= number_format((float) ($resumenConciliacion['metros_venta'] ?? 0), 2) ?> m&sup3;</span></div></div>
    <div class="col-md-6 col-xl-3"><div class="conc-metric success"><small>Registradas en Recompensas</small><strong class="text-success"><?= number_format($registradasConciliacion) ?></strong><span><?= number_format((float) ($resumenConciliacion['metros_registrados'] ?? 0), 2) ?> m&sup3;</span></div></div>
    <div class="col-md-6 col-xl-3"><div class="conc-metric danger"><small>Remisiones faltantes</small><strong class="text-danger"><?= number_format($faltantesConciliacion) ?></strong><span><?= number_format(max(0, (float) ($resumenConciliacion['metros_venta'] ?? 0) - (float) ($resumenConciliacion['metros_registrados'] ?? 0)), 2) ?> m&sup3;</span></div></div>
    <div class="col-md-6 col-xl-3"><div class="conc-metric info"><small>Cobertura de Recompensas</small><strong><?= number_format($porcentajeConciliacion, 1) ?>%</strong><div class="progress mt-2" style="height:12px"><div class="progress-bar bg-success" style="width:<?= min(100, max(0, $porcentajeConciliacion)) ?>%"></div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7"><div class="card h-100"><div class="card-header"><b>Cobertura por dia</b></div><div class="card-body"><canvas id="graficaConciliacionDia" height="120"></canvas></div></div></div>
    <div class="col-xl-5"><div class="card h-100"><div class="card-header"><b>Resumen diario</b></div><div class="card-body table-responsive">
        <table class="table table-sm table-striped align-middle"><thead><tr><th>Fecha</th><th>Vendidas</th><th>Dentro</th><th>Faltan</th><th>%</th><th>m&sup3;</th></tr></thead><tbody>
        <?php foreach ($porDiaConciliacion as $dia): $diaTotal=(int)$dia['remisiones_venta']; $diaDentro=(int)$dia['remisiones_registradas']; $diaPct=$diaTotal>0?$diaDentro*100/$diaTotal:0; ?>
            <tr><td><?= date('d/m/Y', strtotime($dia['fecha'])) ?></td><td><?= number_format($diaTotal) ?></td><td class="text-success fw-bold"><?= number_format($diaDentro) ?></td><td class="text-danger fw-bold"><?= number_format($diaTotal-$diaDentro) ?></td><td><?= number_format($diaPct,1) ?>%</td><td><?= number_format((float)$dia['metros_venta'],2) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$porDiaConciliacion): ?><tr><td colspan="6" class="text-center text-muted">No hay ventas de concreto en el periodo.</td></tr><?php endif; ?>
        </tbody></table>
    </div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header"><b>Cobertura por vendedor</b></div>
    <div class="card-body">
        <div style="height:300px" class="mb-4"><canvas id="graficaConciliacionVendedores"></canvas></div>
        <div class="table-responsive"><table id="tablaConciliacionVendedores" class="table table-striped table-bordered table-sm align-middle" style="width:100%">
            <thead><tr><th>Vendedor</th><th>Remisiones</th><th>En Recompensas</th><th>Faltantes</th><th>Cobertura</th><th>Metros</th><th>Metros en Recompensas</th></tr></thead><tbody>
            <?php foreach ($porVendedorConciliacion as $vendedor): $tv=(int)$vendedor['remisiones_venta']; $rv=(int)$vendedor['remisiones_registradas']; $pv=$tv>0?$rv*100/$tv:0; ?>
                <tr><td><b><?= htmlspecialchars($vendedor['vendedor'],ENT_QUOTES,'UTF-8') ?></b></td><td><?= number_format($tv) ?></td><td class="text-success fw-bold"><?= number_format($rv) ?></td><td class="text-danger fw-bold"><?= number_format(max(0,$tv-$rv)) ?></td><td data-order="<?= $pv ?>"><?= number_format($pv,1) ?>%</td><td><?= number_format((float)$vendedor['metros_venta'],2) ?></td><td><?= number_format((float)$vendedor['metros_registrados'],2) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
</div>

<div class="card mb-0">
    <div class="card-header d-flex justify-content-between"><b>Detalle de concreto y thermoconcreto</b><span class="badge text-bg-secondary"><?= number_format(count($detalleConciliacion)) ?> resultados</span></div>
    <div class="card-body table-responsive"><table id="tablaDetalleConciliacion" class="table table-striped table-bordered table-sm align-middle" style="width:100%">
        <thead><tr><th>Fecha</th><th>Remision</th><th>Pedido</th><th>Planta</th><th>Vendedor</th><th>Cliente</th><th>Articulo</th><th>Metros</th><th>Recompensas</th></tr></thead><tbody>
        <?php foreach ($detalleConciliacion as $fila): ?><tr><td data-order="<?= htmlspecialchars($fila['fecha']) ?>"><?= date('d/m/Y',strtotime($fila['fecha'])) ?></td><td><b><?= htmlspecialchars($fila['remision'],ENT_QUOTES,'UTF-8') ?></b></td><td><?= htmlspecialchars((string)$fila['pedido'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars((string)$fila['planta'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars((string)$fila['vendedor'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars((string)$fila['cliente'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars((string)$fila['articulos'],ENT_QUOTES,'UTF-8') ?></td><td><?= number_format((float)$fila['metros'],2) ?></td><td><?= (int)$fila['en_recompensas']===1?'<span class="badge text-bg-success">SI</span>':'<span class="badge text-bg-danger">FALTA</span>' ?></td></tr><?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#tablaConciliacionVendedores').DataTable({pageLength:25,order:[[1,'desc']],language:{url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'}});
    $('#tablaDetalleConciliacion').DataTable({pageLength:25,order:[[0,'desc'],[1,'desc']],language:{url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'}});
    const dias=<?= json_encode(array_reverse($porDiaConciliacion),JSON_UNESCAPED_UNICODE) ?>;
    new Chart(document.getElementById('graficaConciliacionDia'),{type:'bar',data:{labels:dias.map(d=>d.fecha.split('-').reverse().join('/')),datasets:[{label:'En Recompensas',data:dias.map(d=>Number(d.remisiones_registradas)),backgroundColor:'#198754'},{label:'Faltantes',data:dias.map(d=>Number(d.remisiones_venta)-Number(d.remisiones_registradas)),backgroundColor:'#dc3545'}]},options:{responsive:true,scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true,ticks:{precision:0}}}}});
    const vendedores=<?= json_encode($porVendedorConciliacion,JSON_UNESCAPED_UNICODE) ?>;
    new Chart(document.getElementById('graficaConciliacionVendedores'),{type:'bar',data:{labels:vendedores.map(v=>v.vendedor),datasets:[{data:vendedores.map(v=>Number(v.remisiones_venta)>0?Number(v.remisiones_registradas)*100/Number(v.remisiones_venta):0),backgroundColor:'#174a94'}]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,max:100,ticks:{callback:v=>v+'%'}}}}});
});
</script>
