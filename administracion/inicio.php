<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
require_once('../app/functions/conciliacion_ventas.php');
verificarSesion();
$clientesxvendedor = obtenerTotalClientesxVendedor();
$topClientesPorVendedor = obtenerTopClientesPorVendedor(3);

try {
    // Sin fechas explicitas, filtrosConciliacionVentas() usa el mes en curso
    // (del dia 1 a la fecha mas reciente cargada en tb_ventas_empresa).
    $filtrosMes = filtrosConciliacionVentas($pdo, []);
    $coberturaVendedorBruta = obtenerConciliacionPorVendedor($pdo, $filtrosMes);
    $coberturaPlantaBruta = obtenerConciliacionPorPlanta($pdo, $filtrosMes);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $filtrosMes = null;
    $coberturaVendedorBruta = [];
    $coberturaPlantaBruta = [];
}

// obtenerConciliacionPorVendedor() agrupa por el nombre tal como viene del
// archivo de ventas; se re-normaliza con claveVendedorVenta() (la misma
// funcion que usa para hacer match contra tb_usuarios) para poder cruzarlo
// aqui contra el usuario real del vendedor.
$coberturaPorVendedor = [];
foreach ($coberturaVendedorBruta as $fila) {
    $coberturaPorVendedor[claveVendedorVenta((string) $fila['vendedor'])] = $fila;
}

$coberturaPorPlanta = [];
foreach ($coberturaPlantaBruta as $fila) {
    $coberturaPorPlanta[$fila['planta']] = $fila;
}

$actividadPorPlanta = [];
if ($filtrosMes) {
    foreach (obtenerActividadPorPlanta($filtrosMes['fecha_inicio'], $filtrosMes['fecha_fin']) as $fila) {
        $actividadPorPlanta[$fila['planta']] = $fila;
    }
}

// Union de plantas: una planta puede tener ventas cargadas y cero remisiones
// registradas (justo el caso que se quiere detectar), o viceversa.
$nombresPlanta = array_unique(array_merge(array_keys($coberturaPorPlanta), array_keys($actividadPorPlanta)));
usort($nombresPlanta, static function ($a, $b) use ($coberturaPorPlanta) {
    $ventaA = (int) ($coberturaPorPlanta[$a]['remisiones_venta'] ?? 0);
    $ventaB = (int) ($coberturaPorPlanta[$b]['remisiones_venta'] ?? 0);
    return $ventaB <=> $ventaA ?: strcmp($a, $b);
});
?>
<!doctype html>
<html lang="es">
<!--begin::Head-->
<?php include("../app/layout/head.php"); ?>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
        <!--begin::Header-->
        <nav class="app-header navbar navbar-expand bg-body">
            <!--begin::Container-->
            <div class="container-fluid">
                <?php include("../app/layout/navbar.php"); ?>
            </div>
            <!--end::Container-->
        </nav>
        <!--end::Header-->
        <!--begin::Sidebar-->
        <?php include("../app/layout/menu.php"); ?>
        <!--end::Sidebar-->
        <!--begin::App Main-->
        <main class="app-main">
            <!--begin::App Content Header-->
            <div class="app-content-header">
                <!--begin::Container-->
                <div class="container-fluid">
                    <div class="card m-2 shadow-lg">
                        <h4 class="m-2">Clientes por Vendedor</h4>
                        <!--begin::Row-->
                        <div class="row m-2 g-3">
                            <?php foreach ($clientesxvendedor as $clientes):
                                $clave = strtoupper((string) $clientes['usuario']);
                                $topClientes = $topClientesPorVendedor[$clave] ?? [];
                                $cobertura = $coberturaPorVendedor[$clave] ?? null;
                                $ventaTotal = $cobertura ? (int) $cobertura['remisiones_venta'] : 0;
                                $ventaRegistrada = $cobertura ? (int) $cobertura['remisiones_registradas'] : 0;
                                $porcentajeCobertura = $ventaTotal > 0 ? ($ventaRegistrada * 100 / $ventaTotal) : null;
                            ?>
                                <div class="col-lg-2 col-6">
                                    <!--begin::Vendedor Card-->
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header bg-primary text-white p-2">
                                            <h3 class="mb-0"><?php echo $clientes['total_clientes']; ?></h3>
                                            <small><?php echo htmlspecialchars((string) $clientes['usuario'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        </div>
                                        <div class="card-body p-2">
                                            <div class="small text-muted mb-2 text-truncate">
                                                <?php echo htmlspecialchars($clientes['nombres'] . ' ' . $clientes['apellido_p'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>

                                            <div class="d-flex justify-content-between small text-muted">
                                                <span>Cobertura</span>
                                                <span><b><?= $porcentajeCobertura !== null ? number_format($porcentajeCobertura, 1) . '%' : 'N/D' ?></b></span>
                                            </div>
                                            <div class="progress mb-1" style="height:6px">
                                                <div class="progress-bar bg-success" style="width:<?= $porcentajeCobertura !== null ? min(100, max(0, $porcentajeCobertura)) : 0 ?>%"></div>
                                            </div>
                                            <?php if ($porcentajeCobertura !== null): ?>
                                                <div class="text-end text-muted mb-2" style="font-size:.7rem">
                                                    <?= number_format($ventaRegistrada) ?>/<?= number_format($ventaTotal) ?> remisiones
                                                </div>
                                            <?php else: ?>
                                                <div class="mb-2"></div>
                                            <?php endif; ?>

                                            <div class="small text-muted mb-1">Top puntos</div>
                                            <?php if ($topClientes): ?>
                                                <ol class="mb-0 ps-3 small">
                                                    <?php foreach ($topClientes as $cliente): ?>
                                                        <li class="text-truncate">
                                                            <?= htmlspecialchars((string) $cliente['cliente'], ENT_QUOTES, 'UTF-8') ?>
                                                            &mdash; <b><?= number_format((float) $cliente['puntos'], 0) ?></b>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ol>
                                            <?php else: ?>
                                                <div class="text-muted small">Sin puntos aun</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <!--end::Vendedor Card-->
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!--end::Row-->
                    </div>
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content Header-->
            <!--begin::App Content-->
            <div class="app-content">
                <!--begin::Container-->
                <div class="container-fluid">
                    <div class="card m-2 shadow-lg">
                        <h4 class="m-2">Actividad por planta</h4>
                        <?php if ($filtrosMes): ?>
                            <p class="text-muted mx-2 mb-3 small">
                                Mes actual (<?= date('d/m/Y', strtotime($filtrosMes['fecha_inicio'])) ?> al <?= date('d/m/Y', strtotime($filtrosMes['fecha_fin'])) ?>).
                                Cobertura = remisiones cargadas en el archivo de ventas de esa planta que ya estan registradas en Recompensas.
                                Si un chofer no aparece en la lista, no ha subido remisiones en el periodo.
                            </p>
                        <?php else: ?>
                            <p class="text-muted mx-2 mb-3 small">
                                No fue posible calcular la cobertura de ventas (falta cargar el modulo de conciliacion).
                            </p>
                        <?php endif; ?>
                        <div class="row m-2 g-3">
                            <?php foreach ($nombresPlanta as $nombrePlanta):
                                $cobertura = $coberturaPorPlanta[$nombrePlanta] ?? null;
                                $ventaTotal = $cobertura ? (int) $cobertura['remisiones_venta'] : 0;
                                $ventaRegistrada = $cobertura ? (int) $cobertura['remisiones_registradas'] : 0;
                                $porcentajeCobertura = $ventaTotal > 0 ? ($ventaRegistrada * 100 / $ventaTotal) : null;
                                $actividad = $actividadPorPlanta[$nombrePlanta] ?? ['remisiones' => 0, 'choferes' => []];
                            ?>
                                <div class="col-lg-3 col-md-4 col-6">
                                    <!--begin::Planta Card-->
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-header bg-primary text-white p-2">
                                            <h3 class="mb-0"><?= (int) $actividad['remisiones'] ?></h3>
                                            <small><?= htmlspecialchars($nombrePlanta, ENT_QUOTES, 'UTF-8') ?></small>
                                        </div>
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between small text-muted">
                                                <span>Cobertura</span>
                                                <span><b><?= $porcentajeCobertura !== null ? number_format($porcentajeCobertura, 1) . '%' : 'N/D' ?></b></span>
                                            </div>
                                            <div class="progress mb-1" style="height:6px">
                                                <div class="progress-bar bg-success" style="width:<?= $porcentajeCobertura !== null ? min(100, max(0, $porcentajeCobertura)) : 0 ?>%"></div>
                                            </div>
                                            <?php if ($porcentajeCobertura !== null): ?>
                                                <div class="text-end text-muted mb-2" style="font-size:.7rem">
                                                    <?= number_format($ventaRegistrada) ?>/<?= number_format($ventaTotal) ?> remisiones deberian llevar
                                                </div>
                                            <?php else: ?>
                                                <div class="mb-2"></div>
                                            <?php endif; ?>

                                            <div class="small text-muted mb-1">Choferes</div>
                                            <?php if ($actividad['choferes']): ?>
                                                <ol class="mb-0 ps-3 small">
                                                    <?php foreach ($actividad['choferes'] as $chofer): ?>
                                                        <li class="text-truncate">
                                                            <?= htmlspecialchars((string) $chofer['chofer'], ENT_QUOTES, 'UTF-8') ?>
                                                            &mdash; <b><?= (int) $chofer['remisiones'] ?></b>
                                                            <div class="text-muted" style="font-size:.7rem">
                                                                ultima: <?= date('d/m H:i', strtotime((string) $chofer['ultima_remision'])) ?>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ol>
                                            <?php else: ?>
                                                <div class="text-muted small">Sin remisiones en el periodo</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <!--end::Planta Card-->
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$nombresPlanta): ?>
                                <div class="col-12 text-muted">No hay datos de plantas en el periodo.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content-->
        </main>
        <!--end::App Main-->
        <!--begin::Footer-->
        <?php include("../app/layout/footer.php"); ?>
        <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <?php include("../app/layout/footer_links.php"); ?>
</body>
<!--end::Body-->

</html>