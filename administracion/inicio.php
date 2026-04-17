<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
verificarSesion();
$clientesxvendedor = obtenerTotalClientesxVendedor();
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
                        <div class="row m-2">
                            <?php
                            foreach ($clientesxvendedor as $clientes) {
                            ?>
                                <div class="col-lg-2 col-6">
                                    <!--begin::Small Box Widget 1-->
                                    <div class="small-box text-bg-primary">
                                        <div class="inner">
                                            <h3><?php echo $clientes['total_clientes']; ?></h3>
                                            <small><?php echo $clientes['usuario']; ?></small>
                                        </div>
                                        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
                                        </svg>
                                        <h7 href="#" class="small-box-footer align-items-center">
                                            <p><?php echo $clientes['nombres'] . ' ' . $clientes['apellido_p']; ?></p>
                                        </h7>
                                    </div>
                                </div>
                                <!--end::Small Box Widget 1-->
                            <?php } ?>
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