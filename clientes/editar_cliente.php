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
$clienteSinCorreo = str_starts_with((string) ($cliente['correo'] ?? ''), 'sin-correo-');

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
                                            <input type="email" class="form-control" id="correo" value="<?php echo $clienteSinCorreo ? '' : $cliente['correo']; ?>" name="correo" <?php echo $clienteSinCorreo ? 'disabled' : 'required'; ?>>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" value="1" id="sin_correo" name="sin_correo" <?php echo $clienteSinCorreo ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="sin_correo">
                                                    Sin correo electronico
                                                </label>
                                            </div>
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
                                    <div class="card-footer mt-3 d-flex flex-column align-items-stretch">
                                        <button class="btn btn-primary m-2" type="submit">GUARDAR</button>
                                        <a href="<?php echo ($_SESSION['rol'] == 'VENDEDOR') ? '../vendedor/menu_vendedor.php' : '../clientes/registrar_cliente.php'; ?>" class="btn btn-secondary m-2">
                                            CANCELAR
                                        </a>
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
            <script>
                const correoInput = document.getElementById('correo');
                const sinCorreoInput = document.getElementById('sin_correo');

                sinCorreoInput.addEventListener('change', function() {
                    if (this.checked) {
                        correoInput.value = '';
                        correoInput.disabled = true;
                        correoInput.required = false;
                        correoInput.placeholder = 'Sin correo electronico';
                    } else {
                        correoInput.disabled = false;
                        correoInput.required = true;
                        correoInput.placeholder = '';
                        correoInput.focus();
                    }
                });
            </script>

</body>
<!--end::Body-->

</html>
