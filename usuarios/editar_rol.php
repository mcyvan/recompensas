<?php
include("../app/config/config.php");
include("../app/functions/auth.php");
include("../app/functions/consultas.php");
verificarSesion();
$id_rol = $_GET['id_rol'];
$rol = obtenerRolesID($id_rol);
foreach ($rol as $rol) {
}
?>
<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Usuarios - Editar Rol</title>
</head>


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
                            <h3 class="mb-0">Editar Rol</h3>
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
                        <div class="card card-warning card-outline mb-4 col-sm-6">
                            <!--begin::Header-->
                            <div class="card-header">
                                <div class="card-title"><b>Editar Rol</b></div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Form-->
                            <form class="needs-validation" novalidate="" action="../controller/controller_actualizar_rol.php" method="post">
                                <!--begin::Body-->
                                <div class="card-body">
                                    <!--begin::Row-->
                                    <div class="row g-3">
                                        <!--begin::Col-->
                                        <div class="col-md-5">
                                            <label for="" class="form-label"><b>Rol</b></label>
                                            <input type="text" class="form-control" id="" value="<?php echo $rol['rol']; ?>" name="rol" required>
                                            <input type="hidden" class="form-control" id="" value="<?php echo $rol['id_rol']; ?>" name="id_rol" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label for="" class="form-label"><b>Estatus</b></label>
                                            <select name="estatus" id="estatus" class="form-control">
                                                <option value="1" <?php echo $rol['estatus'] == 1 ? 'selected' : ''; ?>>Activo</option>
                                                <option value="0" <?php echo $rol['estatus'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                            </select>
                                        </div>
                                        <!--end::Col-->
                                        <!--begin::Footer-->
                                        <div class="card-footer mt-2">
                                            <button class="btn btn-outline-warning" type="submit">Actualizar Rol</button>
                                            <a href="../usuarios/registrar_rol.php" class="btn btn-outline-secondary">Cancelar</a>
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