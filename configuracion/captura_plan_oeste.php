<?php
include ("../app/config/config.php");
include ("../app/functions/auth.php");
include ("../app/functions/consultas.php");
verificarSesion();
$productos = obtenerProductosPlanta($planta=1);//1=Oeste, 2=Sur
$plantas = obtenerPlantas();
$producciones=obtenerTablaPlan($planta=1);//1=Este, 2=Sur
?>
<!doctype html>
<html lang="es">
  <!--begin::Head-->
  <head>
    <?php include('../app/layout/head.php'); ?>
    <title>Captura - Plan</title>
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
              <div class="col-sm-6"><h3 class="mb-0">Captura Plan Produccion Oeste</h3></div>
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
                <div class="card card-warning card-outline mb-4 col-sm-6">
                    <!--begin::Header-->
                    <div class="card-header">
                        <div class="card-title"><b>Captura Plan Produccion Oeste</b></div>                        
                    </div>
                    <!--end::Header-->
                    <!--begin::Form-->
                    <form class="needs-validation" novalidate="" action="../controller/controller_registrar_plan_captura_oeste.php" method="post">                      
                        <!--begin::Body-->
                        <div class="card-body">
                        <!--begin::Row-->
                          <div class="row g-3">
                            <div class="col-md-4">
                                <label for="" class="form-label"><b>Tipo de Pieza</b></label>
                                <select name="id_producto" class="form-control" id="" required>
                                  <option value="">SELECCIONA</option>
                                  <?php foreach($productos as $producto):?>
                                    <option value="<?php echo $producto['id_producto'];?>"><?php echo $producto['producto'];?></option>
                                  <?php endforeach;?>
                                </select>
                            </div>                           
                            <!--begin::Col-->
                            <div class="col-md-4">
                                <label for="" class="form-label"><b>Fecha Plan Produccion</b></label>
                                <input type="date" class="form-control" id="fecha" value="" name="fecha_plan" required>                            
                            </div>
                          </div>
                         
                          <!--end::Col-->    
                          <!--end::Body-->
                          <!--begin::Footer-->
                          <div class="card-footer mt-2">
                            <button class="btn btn-warning" type="submit">Registrar Dia</button>
                            
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
                <div class="card card-warning card-outline mb-4 col-sm-12">
                    <!--begin::Header-->
                    <div class="card-header">
                        <div class="card-title col-sm-3">
                          <b>Tabla Captura Oeste</b>
                        </div> 
                        <div class="card-title col-sm-4">
                        <button id="exportExcel" class="btn btn-success mr-2">Exportar a Excel</button>
                        </div> 
                    </div>
                    <!--end::Header-->
                    <div class="card-body p-1">
              <table id="tablaCapturaPlanOeste" class="table table-hover" style="width:100%">
                  <thead>
                    <tr>
                      <th style="width: 10px">#</th>
                      <th>Fecha Plan Produccion</th>                      
                      <th>Mes</th>
                      <th>Planta</th>
                      <th>Tipo Producto</th>                      
                      <th>Usuario Registro</th>                      
                      <th>Fecha de Registro</th>                      
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $cont_produccion_oeste=0;
                    foreach ($producciones as $produccion) {
                      $cont_produccion_oeste=$cont_produccion_oeste+1;
                    ?>
                    <tr>                      
                      <td><?php echo $cont_produccion_oeste;?></td>
                      <td><?php echo $produccion['fecha_plan']; ?></td>                      
                      <td><?php echo $produccion['mes']; ?></td>                      
                      <td><?php echo $produccion['planta']; ?></td>
                      <td><?php echo $produccion['producto']; ?></td>
                      <td><?php echo $produccion['usuario_mod']; ?></td>                      
                      <td><?php echo $produccion['fecha_registro']; ?></td>                      
                      <td><a href="../controller/controller_eliminar_plan_produccion_oeste.php?id_plan_produccion=<?php echo $produccion['id_plan_produccion'];?>" class="btn btn-outline-danger m-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16">
                        <path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"/>
                        </svg></a><a href="../configuracion/editar_plan_oeste.php?id_plan_produccion=<?php echo $produccion['id_plan_produccion'];?>" class="btn btn-outline-warning "><i class="bi bi-pencil-fill"></i></a>
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
        
    // $(document).ready(function() {

    //   document.getElementById('fecha').addEventListener('input', function() {
    //     var fecha = new Date(this.value);
    //     var dia = fecha.getDay(); // 0 es domingo, 6 es sábado
        
    //     if (dia === 5 || dia === 6) {  // Si es sábado (6) o domingo (0)
    //         alert("No se pueden seleccionar sábados ni domingos.");
    //         this.setCustomValidity("No se pueden seleccionar sábados ni domingos."); // Establece un mensaje de error personalizado
    //     } else {
    //         this.setCustomValidity(""); // Si no es sábado ni domingo, se permite
    //     }
    // });


      document.getElementById('exportExcel').addEventListener('click', function() {
  var table = document.getElementById('tablaCapturaOeste');
  
  // Filtrar las filas que quieres exportar
  var filteredRows = [];
  var headerRow = table.rows[0]; // Primera fila de la tabla (encabezados)
  var headerIndex = []; // Índices de las columnas que quieres incluir
  
  // Recorrer el encabezado para encontrar las columnas deseadas
  var desiredHeaders = [
    "#", "Fecha Plan", "Mes", "Planta", "Tipo Producto", "Usuario Registro", "Fecha de Registro"
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
  XLSX.writeFile(wb, 'tabla_captura_plan_produccion.xlsx');
});



      
      $('#tablaCapturaPlanOeste').DataTable({
        
       
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
