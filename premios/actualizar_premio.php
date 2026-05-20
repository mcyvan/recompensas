<?php
include("../app/config/config.php");
include("../app/functions/auth.php");
include("../app/functions/consultas.php");
verificarSesion();
$id_premio = $_GET['id'];
$premios = obtenerPremiosxId($id_premio);
$categorias = obtenerCategorias();

?>
<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Premios - Actualizar</title>
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
                            <h3 class="mb-0">Actualizar Premio</h3>
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

                        <div class="card card-info card-outline mb-4 col-sm-8">

                            <!--begin::Header-->
                            <div class="card-header">
                                <div class="card-title">
                                    <b>Actualizar Premio</b>
                                </div>
                            </div>
                            <!--end::Header-->

                            <!--begin::Form-->
                            <form
                                class="needs-validation"
                                novalidate
                                action="../controller/controller_actualizar_premio.php"
                                method="POST"
                                enctype="multipart/form-data">

                                <input type="hidden" name="id_premio" value="<?php echo $premios['id_premio']; ?>">
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
                                                value="<?php echo $premios['premio']; ?>"
                                                required>

                                            <label class="form-label">
                                                <b>Categoría</b>
                                            </label>
                                            <select name="id_categoria" class="form-control" required>

                                                <?php foreach ($categorias as $categoria): ?>

                                                    <option
                                                        value="<?php echo $categoria['id_categoria']; ?>"

                                                        <?php
                                                        if ($premios['id_categoria'] == $categoria['id_categoria']) {
                                                            echo 'selected';
                                                        }
                                                        ?>>

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
                                                    value="<?php echo $premios['puntos_requeridos']; ?>"
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
                                                    value="<?php echo $premios['stock']; ?>"
                                                    min="0"
                                                    required>


                                            </div>


                                            <label class="form-label">
                                                <b>Estatus</b>
                                            </label>

                                            <select name="activo" class="form-control" required>

                                                <option value="1" <?php echo ($premios['activo'] == 1) ? 'selected' : ''; ?>>
                                                    ACTIVO
                                                </option>

                                                <option value="0" <?php echo ($premios['activo'] == 0) ? 'selected' : ''; ?>>
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
                                                src="<?php echo $premios['imagen']; ?>"
                                                width="220"
                                                class="img-thumbnail mb-2" style="width: 250px; height: 250px; object-fit: cover;">

                                            <input
                                                type="file"
                                                class="form-control"
                                                name="imagen"
                                                id="imagen"
                                                accept=".jpg,.jpeg,.png,.webp"
                                                value="<?php echo $premios['imagen']; ?>">


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
                                                required><?php echo $premios['descripcion']; ?></textarea>

                                        </div>

                                    </div>

                                </div>
                                <!--end::Body-->

                                <!--begin::Footer-->
                                <div class="card-footer">

                                    <button
                                        class="btn btn-outline-info"
                                        type="submit">

                                        Actualizar Premio

                                    </button>
                                    <a href="registrar_premio.php" class="btn btn-outline-secondary">Regresar</a>

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