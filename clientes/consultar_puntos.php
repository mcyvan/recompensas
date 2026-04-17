<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
verificarSesion();
$clientes = obtenerPuntoClientes();


if (isset($_SESSION['mensaje_registro_clientes_correcto'])) {
    $mensaje_registro_clientes_correcto = $_SESSION['mensaje_registro_clientes_correcto'];
    unset($_SESSION['mensaje_registro_clientes_correcto']);
} else {
    $mensaje_registro_clientes_correcto = null;
}
if (isset($_SESSION['mensaje_registro_cliente_existe'])) {
    $mensaje_registro_cliente_existe = $_SESSION['mensaje_registro_cliente_existe'];
    unset($_SESSION['mensaje_registro_cliente_existe']);
} else {
    $mensaje_registro_cliente_existe = null;
}
if (isset($_SESSION['mensaje_actualizar_cliente_correcto'])) {
    $mensaje_actualizar_cliente_correcto = $_SESSION['mensaje_actualizar_cliente_correcto'];
    unset($_SESSION['mensaje_actualizar_cliente_correcto']);
} else {
    $mensaje_actualizar_cliente_correcto = null;
}
if (isset($_SESSION['mensaje_registro_cliente_eliminado'])) {
    $mensaje_registro_cliente_eliminado = $_SESSION['mensaje_registro_cliente_eliminado'];
    unset($_SESSION['mensaje_registro_cliente_eliminado']);
} else {
    $mensaje_registro_cliente_eliminado = null;
}

?>
<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Clientes - Consultar Puntos</title>
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">

    <?php if ($mensaje_registro_clientes_correcto): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_clientes_correcto; ?>",
                icon: "success"
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_registro_cliente_existe): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_cliente_existe; ?>",
                icon: "error"
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_actualizar_cliente_correcto): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_actualizar_cliente_correcto; ?>",
                icon: "success"
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_registro_cliente_eliminado): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_cliente_eliminado; ?>",
                icon: "success"
            });
        </script>
    <?php endif; ?>

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
                            <h3 class="mb-0">Consulta de Puntos</h3>
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
                                <div class="card-title"><b>Consulta de Puntos</b></div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Form-->
                            <form class="needs-validation" novalidate="" action="" method="post">
                                <!--begin::Body-->
                                <div class="card-body">
                                    <!--begin::Row-->
                                    <div class="row g-3">
                                        <!--begin::Col-->
                                        <div class="col-md-5">
                                            <label for="inputTelefono" class="form-label"><b>Numero Registrado</b></label>
                                            <input type="number" class="form-control" id="inputTelefono" value="" name="numero" required>
                                        </div>
                                        <!--end::Col-->
                                    </div>
                                    <!--begin::Footer-->
                                    <div class="card-footer mt-3 d-flex justify-content-between justify-content-md-center gap-md-3">
                                        <button class="btn btn-outline-primary" type="submit">Consultar Puntos</button>
                                        <?php if ($_SESSION['rol'] == "VENDEDOR") { ?>
                                            <a href="../vendedor/menu_vendedor.php" class="btn btn-outline-secondary">
                                                <svg width="60px" height="60px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
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
                    <!--begin::Row-->
                    <?php if ($_SESSION['rol'] == "ADMINISTRADOR" || $_SESSION['rol'] == "ADMINISTRACION") { ?>
                        <div class="row justify-content-center align-items-center">
                            <div class="card card-danger card-outline mb-4 col-sm-12">
                                <!--begin::Header-->
                                <div class="card-header">
                                    <div class="card-title col-sm-3">
                                        <b>Tabla Clientes</b>
                                    </div>
                                    <!-- <div class="card-title col-sm-4">
                                    <button id="exportExcel" class="btn btn-success mr-2">Exportar a Excel</button>
                                </div>  -->
                                </div>
                                <!--end::Header-->
                                <div class="card-body p-1">
                                    <div class="table-responsive">
                                        <table id="tablaClientes" class="table table-striped table-bordered" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th style="width: 20px">#</th>
                                                    <th>Nombre</th>
                                                    <th>Apellido P</th>
                                                    <th>Apellido M</th>
                                                    <th>Correo</th>
                                                    <th>Telefono</th>
                                                    <th>Fecha Registro</th>
                                                    <th>Vendedor</th>
                                                    <th>Puntos</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $cont_clientes = 0;
                                                foreach ($clientes as $cliente) {
                                                    $cont_clientes = $cont_clientes + 1;
                                                ?>
                                                    <tr>
                                                        <td><?php echo $cont_clientes; ?></td>
                                                        <td><?php echo $cliente['nombres']; ?></td>
                                                        <td><?php echo $cliente['apellido_p']; ?></td>
                                                        <td><?php echo $cliente['apellido_m']; ?></td>
                                                        <td><?php echo $cliente['correo']; ?></td>
                                                        <td><?php echo $cliente['telefono']; ?></td>
                                                        <td><?php echo $cliente['fecha_registro']; ?></td>
                                                        <td><?php echo $cliente['usuario']; ?></td>
                                                        <td>
                                                            <?php
                                                            if (isset($cliente['puntos'])) {
                                                                echo $cliente['puntos'];
                                                            } else {
                                                                echo "0";
                                                            }
                                                            ?></td>

                                                    </tr>
                                                <?php
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div><!--End Card Body-->
                            </div><!--End Card-->
                        </div><!-- /.row (main row) -->
                    <?php } ?>
                </div><!--end::Container-->
            </div><!--end::App Content-->
        </main><!--end::App Main-->
        <!-- Modal de Resultado de Puntos -->
        <div class="modal fade" id="modalPuntos" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"> <!-- Centrado vertical -->
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Resultado de Consulta</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center" id="contenidoModal">
                        <!-- Aquí se cargará la info con JS -->
                        <div class="spinner-border text-danger" role="status"></div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!--begin::Footer-->
        <?php include("../app/layout/footer.php"); ?>
        <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <?php include("../app/layout/footer_links.php"); ?>

    <script>
        $(document).ready(function() {
            $('#tablaClientes').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const formConsulta = document.querySelector('form'); // Tu formulario
            const myModal = new bootstrap.Modal(document.getElementById('modalPuntos'));

            formConsulta.addEventListener('submit', function(e) {
                e.preventDefault(); // Evita que la página se refresque

                const numero = document.querySelector('input[name="numero"]').value;

                if (numero === "") return; // No hacer nada si está vacío

                // Abrimos el modal y mostramos que está cargando
                myModal.show();
                document.getElementById('contenidoModal').innerHTML = '<div class="spinner-border text-danger"></div>';

                // Enviamos los datos al controlador por AJAX
                const formData = new FormData();
                formData.append('numero', numero);

                fetch('../controller/controller_consultar_puntos.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('contenidoModal').innerHTML = `
                    <p class="mb-1">Cliente: <b>${data.nombre}</b></p>
                    <h2 class="display-4 text-success fw-bold">${data.puntos}</h2>
                    <p class="text-muted">Puntos Acumulados</p>
                `;
                            document.getElementById('inputTelefono').value = ''; // Limpiar el campo después de mostrar el resultado  
                        } else {
                            document.getElementById('contenidoModal').innerHTML = `<p class="text-danger">${data.mensaje}</p>`;
                            document.getElementById('inputTelefono').value = ''; // Limpiar el campo después de mostrar el resultado  
                        }
                    });
            });
        });
    </script>
</body>
<!--end::Body-->

</html>