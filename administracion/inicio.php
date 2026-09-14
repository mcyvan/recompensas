<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
require_once('../app/functions/conciliacion_ventas.php');
verificarSesion();
$clientesxvendedor = obtenerTotalClientesxVendedor();
$topClientesPorVendedor = obtenerTopClientesPorVendedor(3);

try {
    $coberturaBruta = obtenerConciliacionPorVendedor($pdo, filtrosConciliacionVentas($pdo, []));
} catch (PDOException $e) {
    error_log($e->getMessage());
    $coberturaBruta = [];
}

// obtenerConciliacionPorVendedor() agrupa por el nombre tal como viene del
// archivo de ventas; se re-normaliza con claveVendedorVenta() (la misma
// funcion que usa para hacer match contra tb_usuarios) para poder cruzarlo
// aqui contra el usuario real del vendedor.
$coberturaPorVendedor = [];
foreach ($coberturaBruta as $fila) {
    $coberturaPorVendedor[claveVendedorVenta((string) $fila['vendedor'])] = $fila;
}
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