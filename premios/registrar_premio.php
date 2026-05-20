<?php
include("../app/config/config.php");
include("../app/functions/auth.php");
include("../app/functions/consultas.php");
verificarSesion();
$categorias = obtenerCategorias();
$premios = obtenerPremios();

if (isset($_SESSION['mensaje_registro_premio_correcto'])) {
    $mensaje_registro_premio_correcto =  $_SESSION['mensaje_registro_premio_correcto'];
    unset($_SESSION['mensaje_registro_premio_correcto']);
} else {
    $mensaje_registro_premio_correcto = null;
}
if (isset($_SESSION['error_registro_premio'])) {
    $error_registro_premio = $_SESSION['error_registro_premio'];
    unset($_SESSION['error_registro_premio']);
} else {
    $error_registro_premio = null;
}
if (isset($_SESSION['mensaje_premio_actualizado'])) {
    $mensaje_premio_actualizado = $_SESSION['mensaje_premio_actualizado'];
    unset($_SESSION['mensaje_premio_actualizado']);
} else {
    $mensaje_premio_actualizado = null;
}
if (isset($_SESSION['mensaje_registro_usuario_eliminado'])) {
    $mensaje_registro_usuario_eliminado = $_SESSION['mensaje_registro_usuario_eliminado'];
    unset($_SESSION['mensaje_registro_usuario_eliminado']);
} else {
    $mensaje_registro_usuario_eliminado = null;
}

?>
<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Premios - Registrar</title>
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <?php if ($mensaje_registro_premio_correcto): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_premio_correcto; ?>",
                icon: "success"
            });
        </script>
    <?php endif; ?>
    <?php if ($error_registro_premio): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $error_registro_premio; ?>",
                icon: "error"
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_premio_actualizado): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_premio_actualizado; ?>",
                icon: "success"
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_registro_usuario_eliminado): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_usuario_eliminado; ?>",
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
                            <h3 class="mb-0">Registro de Premios</h3>
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
                                    <b>Registro de Premio Nuevo</b>
                                </div>
                            </div>
                            <!--end::Header-->

                            <!--begin::Form-->
                            <form
                                class="needs-validation"
                                novalidate
                                action="../controller/controller_registrar_premio.php"
                                method="POST"
                                enctype="multipart/form-data">

                                <!--begin::Body-->
                                <div class="card-body">

                                    <!-- ROW -->
                                    <div class="row g-3 ">

                                        <!-- PREMIO -->
                                        <div class="col-md-6">
                                            <label class="form-label">
                                                <b>Nombre del Premio</b>
                                            </label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="premio"
                                                value=""
                                                required>

                                            <label class="form-label">
                                                <b>Categoría</b>
                                            </label>
                                            <select name="id_categoria" class="form-control" required>
                                                <option value="" selected>
                                                    Seleccionar Categoría
                                                </option>
                                                <?php foreach ($categorias as $categoria): ?>
                                                    <option value="<?php echo $categoria['id_categoria']; ?>">
                                                        <?php echo $categoria['categoria']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <!-- PUNTOS -->
                                            <div class="col-sm-6">

                                                <label class="form-label ">
                                                    <b>Puntos Requeridos</b>
                                                </label>

                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    name="puntos_requeridos"
                                                    value=""
                                                    min="1"
                                                    required>


                                                <!-- STOCK -->

                                                <label class="form-label ">
                                                    <b>Stock</b>
                                                </label>

                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    name="stock"
                                                    value=""
                                                    min="0"
                                                    required>


                                            </div>


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
                                        <div class="col-md-5 text-center">

                                            <!-- IMAGEN -->

                                            <label class="form-label">
                                                <b>Imagen del Premio</b>
                                            </label>

                                            <br>

                                            <img
                                                id="preview-imagen"
                                                src="../premios/img/regalo_default.png"
                                                class="img-thumbnail mb-2"
                                                style="width: 250px; height: 250px; object-fit: cover;">

                                            <input
                                                type="file"
                                                class="form-control"
                                                name="imagen"
                                                id="imagen"
                                                accept=".jpg,.jpeg,.png,.webp"
                                                value="" required>


                                        </div>


                                    </div>


                                    <!-- CATEGORIA -->
                                    <div class="col-md-4">

                                    </div>

                                    <!-- ESTATUS -->
                                    <div class="col-md-3">

                                    </div>

                                    <!-- IMAGEN -->



                                    <!-- ROW -->
                                    <div class="row g-3 mt-2">

                                        <!-- DESCRIPCION -->
                                        <div class="col-md-12">

                                            <label class="form-label">
                                                <b>Descripción</b>
                                            </label>

                                            <textarea
                                                class="form-control"
                                                name="descripcion"
                                                rows="4"
                                                placeholder="Describe el premio..."
                                                required></textarea>

                                        </div>

                                    </div>

                                </div>
                                <!--end::Body-->

                                <!--begin::Footer-->
                                <div class="card-footer">

                                    <button
                                        class="btn btn-outline-success"
                                        type="submit">

                                        Registrar Premio

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
                            <b>Tabla Premios</b>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table id="tablaPremios" class="table table-striped table-bordered align-middle">
                                <thead>

                                    <tr>

                                        <th class="text-center align-middle">#</th>
                                        <th>Imagen</th>
                                        <th>Premio</th>
                                        <th>Categoría</th>
                                        <th>Puntos</th>
                                        <th>Stock</th>
                                        <th>Estatus</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php
                                    $contador = 0;

                                    foreach ($premios as $premio):

                                        $contador++;
                                    ?>

                                        <tr>

                                            <td><?php echo $contador; ?></td>

                                            <td class="text-center">

                                                <img
                                                    src="<?php echo $premio['imagen']; ?>"
                                                    class="img-thumbnail shadow-sm"
                                                    style="width:100px;height:100px;object-fit:cover;">

                                            </td>

                                            <td>
                                                <?php echo $premio['premio']; ?>
                                            </td>

                                            <td>
                                                <?php echo $premio['categoria']; ?>
                                            </td>

                                            <td>
                                                <?php echo number_format($premio['puntos_requeridos']); ?>
                                            </td>

                                            <td>
                                                <?php echo $premio['stock']; ?>
                                            </td>

                                            <td>

                                                <?php if ($premio['activo'] == 1): ?>

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
                                                <?php echo date('d/m/Y', strtotime($premio['fecha_registro'])); ?>
                                            </td>

                                            <td>

                                                <a
                                                    href="actualizar_premio.php?id=<?php echo $premio['id_premio']; ?>"
                                                    class="btn btn-outline-warning btn-sm">

                                                    Editar

                                                </a>

                                                <a
                                                    href="../controller/controller_eliminar_premio.php?id=<?php echo $premio['id_premio']; ?>"
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