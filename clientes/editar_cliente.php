<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
verificarSesion();
$id_cliente = $_GET['id_cliente'];
$vendedores = obtenerVendedores();
$clientes = obtenerClientesID($id_cliente);
foreach ($clientes as $cliente) {
}

?>
<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Clientes - Editar</title>
</head>
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
                    <!--begin::Row-->
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Edición de Clientes</h3>
                        </div>
                        <div class="col-sm-6">
                        </div>
                    </div>
                    <!--end::Row-->
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content Header-->
            <!--begin::App Content-->
            <div class="app-content">
                <!--begin::Container-->
                <div class="container-fluid">
                    <!--begin::Row-->
                    <div class="row justify-content-center align-items-center">
                        <div class="card card-danger card-outline mb-4 col-sm-6">
                            <!--begin::Header-->
                            <div class="card-header">
                                <div class="card-title"><b>Editar de Cliente</b></div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Form-->
                            <form class="needs-validation" novalidate="" action="../controller/controller_actualizar_cliente.php" method="post">
                                <!--begin::Body-->
                                <div class="card-body">
                                    <!--begin::Row-->
                                    <div class="row g-3">
                                        <!--begin::Col-->
                                        <div class="col-md-5">
                                            <label for="" class="form-label"><b>Nombre (s)</b></label>
                                            <input type="text" class="form-control" id="" value="<?php echo $cliente['nombres']; ?>" name="nombre" required>
                                            <input type="hidden" class="form-control" id="" value="<?php echo $cliente['id_cliente']; ?>" name="id_cliente" required>
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col-md-3">
                                            <label for="" class="form-label"><b>Apellido Paterno</b></label>
                                            <input type="text" class="form-control" id="" value="<?php echo $cliente['apellido_p']; ?>" name="apellido_p" required>
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col-md-3">
                                            <label for="" class="form-label"><b>Apellido Materno</b></label>
                                            <input type="text" class="form-control" id="" value="<?php echo $cliente['apellido_m']; ?>" name="apellido_m" required>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--begin::Row-->
                                    <div class="row g-3 mt-2">
                                        <!--begin::Col-->
                                        <div class="col-md-5">
                                            <label for="" class="form-label"><b>Correo</b></label>
                                            <input type="email" class="form-control" id="" value="<?php echo $cliente['correo']; ?>" name="correo" required>
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col-md-3">
                                            <label for="" class="form-label"><b>Telefono</b></label>
                                            <input type="tel" class="form-control" id="" value="<?php echo $cliente['telefono']; ?>" name="telefono" pattern="[0-9]{10}" required>
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col-md-3">
                                            <label for="" class="form-label"><b>Fecha Nacimiento</b></label>
                                            <input type="date" class="form-control" id="" value="<?php echo $cliente['fecha_nacimiento']; ?>" name="fecha_nacimiento" required>
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Col-->
                                        <div class="col-md-5">
                                            <label for="" class="form-label"><b>Vendedor</b></label>
                                            <select name="id_vendedor" id="vendedor" class="form-control">
                                                <option value="">Seleccione un vendedor</option>
                                                <?php foreach ($vendedores as $vendedor): ?>
                                                    <option value="<?php echo $vendedor['id_usuario']; ?>" <?php echo ($vendedor['id_usuario'] == $cliente['id_usuario']) ? 'selected' : ''; ?>>
                                                        <?php echo $vendedor['usuario']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <div class="row">
                                        <div class="col-md-5 mt-3">
                                            <label for="" class="form-label"><b>Estatus</b></label>
                                            <select name="id_estatus" id="estatus" class="form-control">
                                                <option value="1" <?php echo ($cliente['estatus'] == '1') ? 'selected' : ''; ?>>ACTIVO</option>
                                                <option value="0" <?php echo ($cliente['estatus'] == '0') ? 'selected' : ''; ?>>INACTIVO</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!--end::Col-->
                                    <!--end::Body-->
                                    <!--begin::Footer-->
                                    <div class="card-footer mt-3 d-flex justify-content-between justify-content-md-center gap-md-3">
                                        <button class="btn btn-outline-primary" type="submit">Actualizar Cliente</button>
                                        <?php if ($_SESSION['rol'] == "VENDEDOR") { ?>
                                            <a href="../vendedor/menu_vendedor.php" class="btn btn-outline-secondary">
                                                <svg width="30px" height="30px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                                    <g id="icomoon-ignore">
                                                    </g>
                                                    <path d="M14.389 7.956v4.374l1.056 0.010c7.335 0.071 11.466 3.333 12.543 9.944-4.029-4.661-8.675-4.663-12.532-4.664h-1.067v4.337l-9.884-7.001 9.884-7zM15.456 5.893l-12.795 9.063 12.795 9.063v-5.332c5.121 0.002 9.869 0.26 13.884 7.42 0-4.547-0.751-14.706-13.884-14.833v-5.381z" fill="#000000"></path>
                                                </svg></a>
                                        <?php } else { ?>
                                            <a href="../clientes/registrar_cliente.php" class="btn btn-outline-secondary">
                                                <svg width="30px" height="30px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                                    <g id="icomoon-ignore">
                                                    </g>
                                                    <path d="M14.389 7.956v4.374l1.056 0.010c7.335 0.071 11.466 3.333 12.543 9.944-4.029-4.661-8.675-4.663-12.532-4.664h-1.067v4.337l-9.884-7.001 9.884-7zM15.456 5.893l-12.795 9.063 12.795 9.063v-5.332c5.121 0.002 9.869 0.26 13.884 7.42 0-4.547-0.751-14.706-13.884-14.833v-5.381z" fill="#000000"></path>
                                                </svg></a>
                                        <?php } ?>
                                    </div>
                                </div>
                                <!--end::Footer-->
                            </form>
                            <!--end::Form-->
                        </div>
                    </div>
                    <!-- /.row (main row) -->
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content-->
            <!--begin::App Content-->
            <div class="app-content">
                <!--begin::Container-->
                <div class="container-fluid">
                </div>
                <!--begin::Footer-->
                <?php include("../app/layout/footer.php"); ?>
                <!--end::Footer-->
            </div>
            <!--end::App Wrapper-->
            <?php include("../app/layout/footer_links.php"); ?>

</body>
<!--end::Body-->

</html>