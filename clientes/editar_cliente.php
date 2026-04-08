<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
verificarSesion();
$id_cliente = $_GET['id_cliente'];
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
                    <!--begin::Row-->
                    <?php if ($_SESSION['rol'] == "ADMINISTRADOR") { ?>
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
                                    <table id="tablaClientes" class="hover" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th style="width: 10px">#</th>
                                                <th>Nombre</th>
                                                <th>Apellido P</th>
                                                <th>Apellido M</th>
                                                <th>Correo</th>
                                                <th>Telefono</th>
                                                <th>Fecha Nacimiento</th>
                                                <th>Fecha Registro</th>
                                                <th>Vendedor</th>
                                                <th>Estatus</th>
                                                <th>Acciones</th>
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
                                                    <td><?php echo $cliente['fecha_nacimiento']; ?></td>
                                                    <td><?php echo $cliente['usuario']; ?></td>
                                                    <td>
                                                        <?php
                                                        if ($cliente['estatus'] == 1) {
                                                            echo "Activo";
                                                        } else {
                                                            echo "Inactivo";
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><a href="../controller/controller_eliminar_cliente.php?id_usuario=<?php echo $cliente['id_cliente']; ?>" class="btn btn-outline-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-dash-fill" viewBox="0 0 16 16">
                                                                <path fill-rule="evenodd" d="M11 7.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5" />
                                                                <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
                                                            </svg></a>
                                                        <a href="../clientes/editar_cliente.php?id_usuario=<?php echo $cliente['id_cliente']; ?>" class="btn btn-outline-warning"><svg fill="currentColor" width="16" height="16" viewBox="0 0 640 640" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h274.9c-2.4-6.8-3.4-14-2.6-21.3l6.8-60.9 1.2-11.1 7.9-7.9 77.3-77.3c-24.5-27.7-60-45.5-99.9-45.5zm45.3 145.3l-6.8 61c-1.1 10.2 7.5 18.8 17.6 17.6l60.9-6.8 137.9-137.9-71.7-71.7-137.9 137.8zM633 268.9L595.1 231c-9.3-9.3-24.5-9.3-33.8 0l-37.8 37.8-4.1 4.1 71.8 71.7 41.8-41.8c9.3-9.4 9.3-24.5 0-33.9z" />
                                                            </svg></a>
                                                    </td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div><!--End Card Body-->
                            </div><!--End Card-->
                        </div><!-- /.row (main row) -->
                    <?php } ?>
                </div><!--end::Container-->
            </div><!--end::App Content-->
        </main><!--end::App Main-->
        <script>
            $(document).ready(function() {
                document.getElementById('exportExcel').addEventListener('click', function() {
                    var table = document.getElementById('tablaUsuarios');

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




                $('#tablaClientes').DataTable({


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