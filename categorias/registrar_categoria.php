<?php
include("../app/config/config.php");
include("../app/functions/auth.php");
include("../app/functions/consultas.php");
verificarSesion();

$categorias = obtenerCategorias();


if (isset($_SESSION['mensaje_registro_categoria_existe'])) {
    $mensaje_registro_categoria_existe =  $_SESSION['mensaje_registro_categoria_existe'];
    unset($_SESSION['mensaje_registro_categoria_existe']);
} else {
    $mensaje_registro_categoria_existe = null;
}
if (isset($_SESSION['mensaje_registro_categoria_correcto'])) {
    $mensaje_registro_categoria_correcto =  $_SESSION['mensaje_registro_categoria_correcto'];
    unset($_SESSION['mensaje_registro_categoria_correcto']);
} else {
    $mensaje_registro_categoria_correcto = null;
}

?>
<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Categoria - Registrar</title>
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <?php if ($mensaje_registro_categoria_existe): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_categoria_existe; ?>",
                icon: "error"
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_registro_categoria_correcto): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_categoria_correcto; ?>",
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
                            <h3 class="mb-0">Registro de Categorías</h3>
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

                        <div class="card card-success card-outline mb-4 col-sm-8">

                            <!--begin::Header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <b>Registro de Categoría Nueva</b>
                                </div>
                            </div>
                            <!--end::Header-->

                            <!--begin::Form-->
                            <form
                                class="needs-validation"
                                novalidate
                                action="../controller/controller_registrar_categoria.php"
                                method="POST"
                                enctype="multipart/form-data">

                                <!--begin::Body-->
                                <div class="card-body">

                                    <!-- ROW -->
                                    <div class="row g-3 mb-3">

                                        <!-- CATEGORIA -->
                                        <div class="col-md-5">
                                            <label class="form-label">
                                                <b>Nombre de la Categoría</b>
                                            </label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="categoria"
                                                value=""
                                                required>


                                            <label class="form-label">
                                                <b>Estatus</b>
                                            </label>

                                            <select name="activo" class="form-control" required>

                                                <option value="" selected>
                                                    Seleccionar Estatus
                                                </option>

                                                <option value="1">
                                                    ACTIVO
                                                </option>

                                                <option value="0">
                                                    INACTIVO
                                                </option>

                                            </select>
                                        </div>

                                        <!-- DESCRIPCION -->
                                        <div class="col-md-5">

                                            <label class="form-label">
                                                <b>Descripción</b>
                                            </label>

                                            <textarea
                                                class="form-control"
                                                name="descripcion"
                                                rows="4"
                                                placeholder="Describe la categoría..."
                                                required></textarea>

                                        </div>

                                    </div>
                                    <!--end::Body-->

                                    <!--begin::Footer-->
                                    <div class="card-footer">

                                        <button
                                            class="btn btn-outline-success"
                                            type="submit">

                                            Registrar Categoria

                                        </button>

                                    </div>
                                    <!--end::Footer-->

                            </form>
                            <!--end::Form-->

                        </div>

                    </div>
                    <!-- /.row -->

                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content-->
            <div class="row m-3">
                <div class="card card-success card-outline mb-4 col-sm-12">

                    <div class="card-header">
                        <div class="card-title">
                            <b>Tabla Categorías</b>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table id="tablaCategorias" class="table table-striped table-bordered align-middle">
                                <thead>

                                    <tr>

                                        <th class="text-center align-middle">#</th>
                                        <th>Categoria</th>
                                        <th>Descripción</th>
                                        <th>Estatus</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php
                                    $contador = 0;

                                    foreach ($categorias as $categoria):

                                        $contador++;
                                    ?>

                                        <tr>

                                            <td><?php echo $contador; ?></td>

                                            <td>
                                                <?php echo $categoria['categoria']; ?>
                                            </td>

                                            <td>
                                                <?php echo $categoria['descripcion']; ?>
                                            </td>


                                            <td>

                                                <?php if ($categoria['estatus'] == 1): ?>

                                                    <span class="badge bg-success">
                                                        ACTIVO
                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge bg-danger">
                                                        INACTIVO
                                                    </span>

                                                <?php endif; ?>

                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($categoria['fecha_registro'])); ?>
                                            </td>

                                            <td>

                                                <a
                                                    href="editar_categoria.php?id=<?php echo $categoria['id_categoria']; ?>"
                                                    class="btn btn-outline-warning btn-sm">

                                                    Editar

                                                </a>

                                                <a
                                                    href="../controller/controller_eliminar_categoria.php?id=<?php echo $categoria['id_categoria']; ?>"
                                                    class="btn btn-outline-danger btn-sm btn-eliminar">

                                                    Eliminar

                                                </a>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>
            </div>
        </main>
        <!--end::App Main-->

        <!--begin::Footer-->
        <?php include("../app/layout/footer.php"); ?>
        <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <?php include("../app/layout/footer_links.php"); ?>
    <script>
        $(document).ready(function() {
            $('#tablaUsuarios').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                }
            });
        });


        document.querySelectorAll('.btn-eliminar').forEach(boton => {

            boton.addEventListener('click', function(e) {

                e.preventDefault();

                const url = this.href;

                Swal.fire({

                    title: '¿Eliminar premio?',
                    text: 'Esta acción eliminará el premio y su imagen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'

                }).then((result) => {

                    if (result.isConfirmed) {

                        window.location.href = url;

                    }

                });

            });

        });



        document.getElementById('imagen').addEventListener('change', function(e) {

            const archivo = e.target.files[0];

            if (archivo) {

                const lector = new FileReader();

                lector.onload = function(event) {

                    document.getElementById('preview-imagen').src = event.target.result;

                }

                lector.readAsDataURL(archivo);

            }

        });
    </script>


</body>
<!--end::Body-->

</html>