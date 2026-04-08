<?php
include("../app/config/config.php");
include("../app/functions/auth.php");
include("../app/functions/consultas.php");
verificarSesion();
$roles = obtenerRoles();

if (isset($_SESSION['mensaje_registro_rol_correcto'])) {
    $mensaje_registro_rol_correcto = $_SESSION['mensaje_registro_rol_correcto'];
    unset($_SESSION['mensaje_registro_rol_correcto']);
} else {
    $mensaje_registro_rol_correcto = null;
}
if (isset($_SESSION['mensaje_registro_rol_existe'])) {
    $mensaje_registro_rol_existe = $_SESSION['mensaje_registro_rol_existe'];
    unset($_SESSION['mensaje_registro_rol_existe']);
} else {
    $mensaje_registro_rol_existe = null;
}
if (isset($_SESSION['mensaje_registro_rol_actualizado'])) {
    $mensaje_registro_rol_actualizado = $_SESSION['mensaje_registro_rol_actualizado'];
    unset($_SESSION['mensaje_registro_rol_actualizado']);
} else {
    $mensaje_registro_rol_actualizado = null;
}
if (isset($_SESSION['mensaje_registro_rol_eliminado'])) {
    $mensaje_registro_rol_eliminado = $_SESSION['mensaje_registro_rol_eliminado'];
    unset($_SESSION['mensaje_registro_rol_eliminado']);
} else {
    $mensaje_registro_rol_eliminado = null;
}

?>
<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Usuarios - Rol</title>
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <?php if ($mensaje_registro_rol_correcto): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: "",
                    text: "<?php echo $mensaje_registro_rol_correcto; ?>",
                    icon: "success"
                });
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_registro_rol_existe): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: "",
                    text: "<?php echo $mensaje_registro_rol_existe; ?>",
                    icon: "error"
                });
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_registro_rol_actualizado): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: "",
                    text: "<?php echo $mensaje_registro_rol_actualizado; ?>",
                    icon: "success"
                });
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_registro_rol_eliminado): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: "",
                    text: "<?php echo $mensaje_registro_rol_eliminado; ?>",
                    icon: "success"
                });
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
                            <h3 class="mb-0">Registro de Roles</h3>
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
                                <div class="card-title"><b>Registro de Rol Nuevo</b></div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Form-->
                            <form class="needs-validation" novalidate="" action="../controller/controller_registrar_rol.php" method="post">
                                <!--begin::Body-->
                                <div class="card-body">
                                    <!--begin::Row-->
                                    <div class="row g-3">
                                        <!--begin::Col-->
                                        <div class="col-md-5">
                                            <label for="" class="form-label"><b>Rol</b></label>
                                            <input type="text" class="form-control" id="" value="" name="rol" required>
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Footer-->
                                        <div class="card-footer mt-2">
                                            <button class="btn btn-outline-primary" type="submit">Registrar Rol</button>

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
                    <div class="row justify-content-center align-items-center">
                        <div class="card card-danger card-outline mb-4 col-sm-6">
                            <!--begin::Header-->
                            <div class="card-header">
                                <div class="card-title col-sm-3">
                                    <b>Tabla Roles</b>
                                </div>
                                <!-- <div class="card-title col-sm-4">
                        <button id="exportExcel" class="btn btn-success mr-2">Exportar a Excel</button>
                        </div>  -->
                            </div>
                            <!--end::Header-->
                            <div class="card-body p-1">
                                <table id="tablaRoles" class="hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="width: 20px">#</th>
                                            <th>Rol</th>
                                            <th>Estatus</th>
                                            <th>Fecha Registro</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $cont_roles = 0;
                                        foreach ($roles as $rol) {
                                            $cont_roles = $cont_roles + 1;
                                        ?>
                                            <tr>
                                                <td><?php echo $cont_roles; ?></td>
                                                <td><?php echo $rol['rol']; ?></td>
                                                <td><?php echo $rol['estatus'] == 1 ? 'Activo' : 'Inactivo'; ?></td>
                                                <td><?php echo $rol['fecha_registro']; ?></td>
                                                <td><a href="../controller/controller_eliminar_rol.php?id_rol=<?php echo $rol['id_rol']; ?>" class="btn btn-outline-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-dash-fill" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd" d="M11 7.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5" />
                                                            <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
                                                        </svg></a>
                                                    <a href="../usuarios/editar_rol.php?id_rol=<?php echo $rol['id_rol']; ?>" class="btn btn-outline-warning"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-lines-fill" viewBox="0 0 16 16">
                                                            <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5 6s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zM11 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5m.5 2.5a.5.5 0 0 0 0 1h4a.5.5 0 0 0 0-1zm2 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1zm0 3a.5.5 0 0 0 0 1h2a.5.5 0 0 0 0-1z" />
                                                        </svg></a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- /.row (main row) -->
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content-->
        </main>
        <!--end::App Main-->
        <script>
            $(document).ready(function() {
                document.getElementById('exportExcel').addEventListener('click', function() {
                    var table = document.getElementById('tablaRoles');

                    // Filtrar las filas que quieres exportar
                    var filteredRows = [];
                    var headerRow = table.rows[0]; // Primera fila de la tabla (encabezados)
                    var headerIndex = []; // Índices de las columnas que quieres incluir

                    // Recorrer el encabezado para encontrar las columnas deseadas
                    var desiredHeaders = [
                        "#", "Fecha", "Mes", "Placas", "Meta", "Eficiencia", "Personal T.E",
                        "Planta", "Tipo Block", "No. Piezas", "Costo Total", "Costo Directo",
                        "Costo - EF", "Tiempo Extra", "$ Eficiencia", "Horas"
                    ];

                    for (var i = 0; i < headerRow.cells.length; i++) {
                        var headerText = headerRow.cells[i].textContent.trim(); // Elimina espacios antes y después del texto del encabezado
                        if (desiredHeaders.includes(headerText)) {
                            headerIndex.push(i); // Almacenar los índices de las columnas deseadas
                        }
                    }

                    // Recorre las filas de la tabla y selecciona solo las columnas deseadas
                    for (var i = 1; i < table.rows.length; i++) { // Comienza desde 1 para omitir la fila de encabezado
                        var rowData = [];
                        for (var j = 0; j < headerIndex.length; j++) {
                            rowData.push(table.rows[i].cells[headerIndex[j]].textContent); // Obtener los valores de las celdas seleccionadas
                        }
                        filteredRows.push(rowData);
                    }

                    // Crear una nueva tabla solo con las columnas seleccionadas
                    var newTable = document.createElement('table');
                    var newHeaderRow = newTable.insertRow();

                    // Agregar los encabezados seleccionados
                    headerIndex.forEach(index => {
                        newHeaderRow.insertCell().textContent = headerRow.cells[index].textContent.trim(); // Agregar encabezados seleccionados
                    });

                    // Agregar las filas filtradas a la nueva tabla
                    filteredRows.forEach(row => {
                        var newRow = newTable.insertRow();
                        row.forEach(cell => {
                            newRow.insertCell().textContent = cell;
                        });
                    });

                    // Convertir la nueva tabla a un archivo de Excel
                    var wb = XLSX.utils.table_to_book(newTable, {
                        sheet: "Sheet 1"
                    });
                    XLSX.writeFile(wb, 'tabla_usuarios.xlsx');
                });




                $('#tablaRoles').DataTable({


                    "language": {
                        "lengthMenu": "Mostrar _MENU_ registros por página",
                        "zeroRecords": "Nada encontrado - lo siento",
                        "info": "Mostrando la página _PAGE_ de _PAGES_",
                        "infoEmpty": "No hay registros disponibles",
                        "infoFiltered": "(filtrado de _MAX_ registros totales)",
                        "search": "Buscar:",
                        "paginate": {
                            "first": "Primero",
                            "last": "Último",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        },
                    }
                });

            });
        </script>
        <!--begin::Footer-->
        <?php include("../app/layout/footer.php"); ?>
        <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <?php include("../app/layout/footer_links.php"); ?>

</body>
<!--end::Body-->

</html>