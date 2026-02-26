<?php
include ("../app/config/config.php");
include ("../app/functions/auth.php");
include ("../app/functions/consultas.php");
verificarSesion();
$plantas = obtenerPlantas();
$productos = obtenerProductosTabla();

?>
<!doctype html>
<html lang="es">
  <!--begin::Head-->
  <head>
    <?php include('../app/layout/head.php'); ?>
    <title>Configuracion - Block</title>
</head> 
  <!--end::Head-->
  <!--begin::Body-->
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body">
        <!--begin::Container-->
        <div class="container-fluid">
          <?php include("../app/layout/navbar.php");?>
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->
      <!--begin::Sidebar-->
      <?php include("../app/layout/menu.php");?>
      <!--end::Sidebar-->
      <!--begin::App Main-->
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Registro de Block</h3></div>
              <div class="col-sm-6">
                <!-- <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="#">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol> -->
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
                        <div class="card-title"><b>Registro de Nuevo Block</b></div>                        
                    </div>
                    <!--end::Header-->
                    <!--begin::Form-->
                    <form class="needs-validation" novalidate="" action="../controller/controller_registrar_tipo_block.php" method="post">                      
                        <!--begin::Body-->
                        <div class="card-body">
                        <!--begin::Row-->
                          <div class="row g-3">
                            <!--begin::Col-->
                            <div class="col-md-5">
                                <label for="" class="form-label"><b>Nombre Producto</b></label>
                                <input type="text" class="form-control" id="" value="" name="nombre_producto" required>                            
                            </div>
                            <!--end::Col--> 
                            <!--begin::Col-->
                            <div class="col-md-3">
                                <label for="" class="form-label"><b>Producto por Placas</b></label>
                                <input type="number" class="form-control" id="" value="" name="producto_placas" required>                            
                            </div>
                            <!--end::Col--> 
                            <!--begin::Col-->
                            <div class="col-md-3">
                                <label for="" class="form-label"><b>Meta de Placas</b></label>
                                <input type="number" class="form-control" id="" value="" name="placas_meta" required>                            
                            </div>
                            <!--end::Col--> 
                          </div>
                          <div class="row g-3 mt-2">
                            <div class="col-md-3">
                                <label for="" class="form-label"><b>Planta</b></label>
                                <select name="id_planta" id="" class="form-control">
                                <?php foreach($plantas as $planta){ ?>
                                    <option value="<?php echo $planta['id_planta'];?>"><?php echo $planta['planta'];?></option>
                                <?php } ?>
                                </select>
                            </div>                                 
                          </div>
                          <!--end::Col-->    
                          <!--end::Body-->
                          <!--begin::Footer-->
                          <div class="card-footer mt-2">
                            <button class="btn btn-outline-primary" type="submit">Registrar Block</button>
                            
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
                <div class="card card-danger card-outline mb-4 col-sm-12">
                    <!--begin::Header-->
                    <div class="card-header">
                        <div class="card-title col-sm-3">
                          <b>Tabla Productos Block</b>
                        </div> 
                        <div class="card-title col-sm-4">
                        <button id="exportExcel" class="btn btn-success mr-2">Exportar a Excel</button>
                        </div> 
                    </div>
                    <!--end::Header-->
                    <div class="card-body p-1">
              <table id="tablaCapturaOeste" class="table table-hover " style="width:100%">
                  <thead>
                    <tr>
                      <th style="width: 10px">#</th>
                      <th>Planta</th>                      
                      <th>Producto</th>                      
                      <th>Placas Producto</th>
                      <th>Placas Meta</th>                      
                      <th>Fecha Registro</th>                    
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $cont_productos=0;
                    foreach ($productos as $producto) {
                      $cont_productos=$cont_productos+1;
                    ?>
                    <tr>                      
                      <td><?php echo $cont_productos;?></td>
                      <td><?php echo $producto['planta']; ?></td>                      
                      <td><?php echo $producto['producto']; ?></td>                      
                      <td><?php echo $producto['producto_placas']; ?></td>
                      <td><?php echo $producto['placas_meta']; ?></td>                      
                      <td><?php echo $producto['fecha_registro']; ?></td>
                      <td><a href="../controller/controller_eliminar_producto.php?id_producto=<?php echo $producto['id_producto'];?>" class="btn btn-outline-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16">
                        <path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 
                              0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"/>
                        </svg></a>
                        <a href="../configuracion/editar_tipo_producto.php?id_producto=<?php echo $producto['id_producto'];?>" class="btn btn-outline-warning"><i class="bi bi-pencil-fill"></i></a>
                      </td>
                    </tr>
                    <?php
                    }
                    ?>
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
  var table = document.getElementById('tablaCapturaOeste');
  
  // Filtrar las filas que quieres exportar
  var filteredRows = [];
  var headerRow = table.rows[0]; // Primera fila de la tabla (encabezados)
  var headerIndex = []; // Índices de las columnas que quieres incluir
  
  // Recorrer el encabezado para encontrar las columnas deseadas
  var desiredHeaders = [
    "#", "Planta", "Producto", "Placas Producto", "Placas Meta", "Fecha Registro"
  ];

  for (var i = 0; i < headerRow.cells.length; i++) {
    var headerText = headerRow.cells[i].textContent.trim(); // Elimina espacios antes y después del texto del encabezado
    if (desiredHeaders.includes(headerText)) {
      headerIndex.push(i); // Almacenar los índices de las columnas deseadas
    }
  }

  // Recorre las filas de la tabla y selecciona solo las columnas deseadas
  for (var i = 1; i < table.rows.length; i++) {  // Comienza desde 1 para omitir la fila de encabezado
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
  var wb = XLSX.utils.table_to_book(newTable, { sheet: "Sheet 1" });
  XLSX.writeFile(wb, 'tablaCapturaOeste.xlsx');
});
      
      $('#tablaCapturaOeste').DataTable({
  "paging": false,  // Desactiva la paginación
  "pageLength": -1, // Muestra todas las filas
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
    </script>
      <!--begin::Footer-->
      <?php include("../app/layout/footer.php");?>
      <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <?php include("../app/layout/footer_links.php");?>

  </body>
  <!--end::Body-->
</html>
