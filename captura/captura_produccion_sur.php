<?php
include ("../app/config/config.php");
include ("../app/functions/auth.php");
include ("../app/functions/consultas.php");
verificarSesion();
$productos = obtenerProductosPlanta($planta=2);//1=Oeste, 2=Sur
$plantas = obtenerPlantas();
$producciones=obtenerTablaProduccion($planta=2);//1=Oeste, 2=Sur
?>
<!doctype html>
<html lang="es">
  <!--begin::Head-->
  <head>
    <?php include('../app/layout/head.php'); ?>
    <title>Captura - Block</title>
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
              <div class="col-sm-6"><h3 class="mb-0">Captura Diaria Sur</h3></div>
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
                <div class="card card-info card-outline mb-4 col-sm-6">
                    <!--begin::Header-->
                    <div class="card-header">
                        <div class="card-title"><b>Registro Captura Sur</b></div>                        
                    </div>
                    <!--end::Header-->
                    <form class="needs-validation" novalidate="" action="../controller/controller_registrar_captura_sur.php" method="post">                      
                        <!--begin::Body-->
                        <div class="card-body">
                        <!--begin::Row-->
                        <div class="row g-3">
    <div class="col-md-4">
        <label for="" class="form-label"><b>Tipo de Pieza</b></label>
        <select name="id_producto" class="form-control" id="id_producto" required>
            <option value="">SELECCIONA</option>
            <?php foreach($productos as $producto):?>
                <option value="<?php echo $producto['id_producto'];?>"><?php echo $producto['producto'];?></option>
            <?php endforeach;?>
        </select>
    </div>
    <!--end::Col-->                               
    <!--begin::Col-->
    <div class="col-md-3">
        <label for="" class="form-label"><b>Fecha Producción</b></label>
        <select name="id_plan_produccion" id="fecha_produccion" class="form-control">
            <option value="">Selecciona una fecha</option>
        </select>                            
    </div>
    <!--end::Col-->  
    <!--begin::Col-->
    <div class="col-md-3">
        <label for="" class="form-label"><b>Cantidad de Placas</b></label>
        <input type="number" class="form-control" value="" name="cantidad" required>                            
    </div>
    <!--end::Col-->  
</div>
                          <div class="row g-3 mt-2">
                            <div class="col-md-2">
                                <label for="" class="form-label"><b>Personal T.E</b></label>
                                <input type="number" class="form-control" id="" value="" name="personal_TE" required>
                            </div>
                            <!-- <div class="col-md-2">
                                <label for="" class="form-label"><b>Planta</b></label>
                                <select name="id_planta" class="form-control" id="" required>
                                  <option value="">SELECCIONA</option>
                                  <?php foreach($plantas as $planta):?>
                                    <option value="<?php echo $planta['id_planta'];?>"><?php echo $planta['planta'];?></option>
                                  <?php endforeach;?>
                                </select>
                            </div> -->
                            <!-- begin::Col-->
                            <!-- <div class="col-md-3">
                                <label for="" class="form-label"><b>Meta (placas)</b></label>
                                <select class="form-control" name="meta_produccion" id="" requiered>
                                  <option value="">SELECCIONA</option>
                                  <option value="0">0</option>
                                  <option value="2800">2,800</option>
                                </select>                           
                            </div> -->
                            <!--end::Col-->
                           
                          </div>
                          <!--end::Col-->    
                          <!--end::Body-->
                          <!--begin::Footer-->
                          <div class="card-footer mt-2">
                            <button class="btn btn-info" type="submit">Registrar Produccion</button>
                            <a href="../inicio/dashboard_oeste.php" class="btn btn-light">Cancelar</a>
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
                <div class="card card-info card-outline mb-4 col-sm-12">
                    <!--begin::Header-->
                    <div class="card-header">
                        <div class="card-title col-sm-3">
                          <b>Tabla Captura Sur</b>
                        </div> 
                        <div class="card-title col-sm-4">
                        <button id="exportExcel" class="btn btn-success mr-2">Exportar a Excel</button>
                        </div> 
                    </div>
                    <!--end::Header-->
                    <div class="card-body p-1">
              <table id="tablaCapturaSur" class="table table-hover" style="width:100%">
                  <thead>
                    <tr>
                      <th style="width: 10px">#</th>
                      <th>Fecha</th>                      
                      <th>Mes</th>                      
                      <th>Placas</th>
                      <th>Meta</th>
                      <th>Eficiencia</th>
                      <th>Personal T.E</th>
                      <th>Planta</th>
                      <th>Tipo Block</th>
                      <th>No. Piezas</th>
                      <th>Costo Total</th>
                      <th>Costo Directo</th>                      
                      <th>Utilidad</th>
                      <th>Tiempo Extra</th>
                      <th>$ Eficiencia</th>
                      <th>Horas</th>
                      <!-- <th>$ E.T. Dia</th>
                      <th>Horas F/P</th> -->
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
                      <td><?php echo $produccion['fecha']; ?></td>                      
                      <td><?php echo $produccion['mes']; ?></td>                      
                      <td><?php echo $produccion['placas']; ?></td>
                      <td><?php echo $produccion['meta']; ?></td>
                      <td><?php echo $produccion['eficiencia']; ?></td>                      
                      <td><?php echo $produccion['personal_TE']; ?></td>
                      <td><?php echo $produccion['planta']; ?></td>
                      <td><?php echo $produccion['producto']; ?></td>
                      <td><?php echo $produccion['producto_placas']; ?></td>
                      <td><?php echo '$' . number_format($produccion['costo_total'], 2, '.', ','); ?></td>
                      <td><?php echo '$' . number_format($produccion['costo_directo'], 2, '.', ','); ?></td>
                      <td><?php echo '$' . number_format($produccion['utilidad_producto'], 2, '.', ','); ?></td>
                      <td><?php echo '$' . number_format($produccion['TiempoExt'], 2, '.', ','); ?></td>
                      <td><?php echo '$' . number_format($produccion['costo_eficiencia'], 2, '.', ','); ?></td>                      
                      <td><?php echo $produccion['horas_faltantes']; ?></td>
                      <!-- <td><?php echo $produccion['TE']; ?></td>
                      <td><?php echo $produccion['TE']; ?></td> -->
                      <td><a href="../controller/controller_eliminar_produccion_sur.php?id_produccion=<?php echo $produccion['id_produccion'];?>" class="btn btn-outline-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16">
  <path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"/>
</svg></a>
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
      function obtenerFechas() {
    var idProducto = document.getElementById("id_producto").value; // Obtén el id del producto seleccionado

    // Verificar si se seleccionó un producto
    if (idProducto) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'consulta_fechas_produccion.php?id_producto=' + idProducto, true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    // Imprimir la respuesta para depuración
                    console.log(xhr.responseText); 

                    var response = JSON.parse(xhr.responseText);

                    // Verifica si la respuesta contiene un error
                    if (response.error) {
                        console.error(response.error);
                        alert(response.error); // Mostrar mensaje de error
                    } else {
                        // Limpiar el select de fechas antes de llenarlo con nuevos datos
                        var fechaSelect = document.getElementById("fecha_produccion");
                        fechaSelect.innerHTML = '<option value="">Selecciona una fecha</option>'; // Limpiar opciones anteriores

                        // Llenar el select con las fechas obtenidas
                        response.forEach(function(fecha) {
                            var option = document.createElement("option");
                            option.value = fecha.id_plan_produccion;
                            option.textContent = fecha.fecha_plan;
                            fechaSelect.appendChild(option);
                        });
                    }
                } catch (e) {
                    // Si ocurre un error al procesar la respuesta
                    console.error("Error al procesar la respuesta JSON:", e);
                    alert("El producto no tiene fechas planeadas de produccion, solicita la fecha con direccion");
                }
            } else {
                console.error("Error en la solicitud:", xhr.status);
                alert("Error en la solicitud. Código de estado: " + xhr.status);
            }
        };

        xhr.onerror = function () {
            console.error("Error de red al realizar la solicitud");
            alert("Error de red. No se pudo realizar la solicitud.");
        };

        xhr.send();
    }
}

// Llamar a la función cuando cambie la selección del producto
document.getElementById("id_producto").addEventListener("change", obtenerFechas);
document.getElementById('exportExcel').addEventListener('click', function() {
    // Obtener la tabla de DataTables
    var table = $('#tablaCapturaSur').DataTable();
    
    // Obtener todos los datos (incluyendo paginación)
    var data = table.rows().data().toArray();
    
    // Definir las columnas que queremos exportar (basado en el índice de las columnas)
    var columnsToExport = [
        0,  // #
        1,  // Fecha
        2,  // Mes
        3,  // Placas (numérico)
        4,  // Meta (numérico)
        5,  // Eficiencia (numérico)
        6,  // Personal T.E (numérico)
        7,  // Planta (texto)
        8,  // Tipo Block (texto)
        9,  // No. Piezas (numérico)
        10, // Costo Total (moneda)
        11, // Costo Directo (moneda)
        12, // Utilidad (moneda)
        13, // Tiempo Extra (moneda)
        14, // $ Eficiencia (moneda)
        15  // Horas (numérico)
    ];
    
    // Preparar los encabezados
    var headers = [];
    var headerRow = $('#tablaCapturaSur thead tr')[0];
    columnsToExport.forEach(function(index) {
        headers.push(headerRow.cells[index].textContent.trim());
    });
    
    // Preparar los datos
    var excelData = [headers];
    
    data.forEach(function(row) {
        var rowData = [];
        columnsToExport.forEach(function(index, colIndex) {
            var cellData = row[index];
            
            // Si es un objeto jQuery, extraemos el texto
            if (cellData && cellData.jquery) {
                cellData = cellData.text();
            }
            
            // Procesamiento especial para columnas numéricas
            if ([3, 4, 5, 6, 9, 10, 11, 12, 13, 14, 15].includes(colIndex)) {
                // Limpiar formato monetario y otros caracteres no numéricos
                if (typeof cellData === 'string') {
                    // Eliminar símbolos no numéricos (excepto punto decimal y signo negativo)
                    cellData = cellData.replace(/[^\d.-]/g, '');
                    
                    // Convertir a número
                    cellData = parseFloat(cellData) || 0;
                }
            }
            
            rowData.push(cellData);
        });
        excelData.push(rowData);
    });
    
    // Crear hoja de trabajo
    var ws = XLSX.utils.aoa_to_sheet(excelData);
    
    // Definir estilos para columnas numéricas
    var range = XLSX.utils.decode_range(ws['!ref']);
    for (var C = 0; C <= range.e.c; ++C) {
        // Aplicar formato numérico a columnas específicas
        if ([3, 4, 5, 6, 9, 15].includes(C)) { // Columnas numéricas simples
            for (var R = 1; R <= range.e.r; ++R) {
                var cell = ws[XLSX.utils.encode_cell({r:R, c:C})];
                if (cell) {
                    cell.t = 'n'; // Tipo numérico
                    // Formato de número decimal con 2 decimales
                    cell.z = '0.00';
                }
            }
        }
        else if ([10, 11, 12, 13, 14].includes(C)) { // Moneda
            for (var R = 1; R <= range.e.r; ++R) {
                var cell = ws[XLSX.utils.encode_cell({r:R, c:C})];
                if (cell) {
                    cell.t = 'n';
                    cell.z = '"$"#,##0.00'; // Formato de moneda
                }
            }
        }
    }
    
    // Ajustar el ancho de las columnas
    var wscols = [
        {wch: 5},   // #
        {wch: 12},  // Fecha
        {wch: 10},  // Mes
        {wch: 10},  // Placas
        {wch: 10},  // Meta
        {wch: 12},  // Eficiencia (numérico)
        {wch: 12},  // Personal T.E
        {wch: 12},  // Planta
        {wch: 15},  // Tipo Block
        {wch: 12},  // No. Piezas
        {wch: 15},  // Costo Total
        {wch: 15},  // Costo Directo
        {wch: 15},  // Utilidad
        {wch: 15},  // Tiempo Extra
        {wch: 15},  // $ Eficiencia
        {wch: 10}   // Horas
    ];
    ws['!cols'] = wscols;
    
    // Crear libro de trabajo
    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Captura Sur");
    
    // Exportar el archivo
    XLSX.writeFile(wb, 'Captura_Sur_' + new Date().toISOString().slice(0, 10) + '.xlsx');
});



      
      $('#tablaCapturaSur').DataTable({
  // "paging": false,  // Desactiva la paginación
  // "pageLength": -1, // Muestra todas las filas
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
      <?php include("../app/layout/footer.php");?>
      <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <?php include("../app/layout/footer_links.php");?>

  </body>
  <!--end::Body-->
</html>
