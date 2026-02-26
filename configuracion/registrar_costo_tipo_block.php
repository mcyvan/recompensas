<?php
include ("../app/config/config.php");
include ("../app/functions/auth.php");
include ("../app/functions/consultas.php");
verificarSesion();
$plantas = obtenerPlantas();

$costoproductos = obtenerTablaCostoProducto();
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
            <div class="col-sm-6"><h3 class="mb-0">Registro de Costo Block</h3></div>
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
                    <div class="card-title"><b>Registro Costo Block</b></div>                       
                    </div>
                    <!--end::Header-->
                    <!--begin::Form-->
                    <form class="needs-validation" novalidate="" action="../controller/controller_registrar_costo_tipo_block.php" method="post">                      
                        <div class="card-body">
                          <div class="row g-3">
                            <div class="col-md-5">
                                <label  class="form-label"><b>Planta</b></label>
                                <select name="id_planta" id="id_planta" class="form-control">
                                        <option value="">Seleccione Planta</option>
                                    <?php foreach($plantas as $planta) { ?>
                                        <option value="<?php echo $planta['id_planta'];?>"><?php echo $planta['planta'];?></option>
                                    <?php } ?>
                                </select>                                                          
                            </div> 
                            <div class="col-md-5">
                                <label  class="form-label"><b>Producto Block</b></label>
                                <select name="id_producto" id="id_producto" class="form-control">
                                    <option value="">Seleccione Producto</option>                  
                                </select>                           
                            </div> 
                            <div class="col-md-3">
                                <label  class="form-label"><b>Costo Total</b></label>
                                <input type="number" class="form-control" value="" name="costo_total" required>                            
                            </div>
                            <div class="col-md-3">
                                <label  class="form-label"><b>Costo Directo</b></label>
                                <input type="number" class="form-control" value="" name="costo_directo" required>                            
                            </div>
                          </div>  
                          <div class="card-footer mt-2">
                            <button class="btn btn-outline-primary" type="submit">Registrar Costo Producto</button>
                          </div>
                        </div>
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
                    <table id="tablaCostoBlock" class="table table-hover" style="width:100%">
                          <thead>
                            <tr>
                              <th>#</th>
                              <th>Planta</th>                      
                              <th>Producto</th>                      
                              <th>Costo Producto</th>
                              <th>Costo Directo</th>                      
                              <th>Utilidad Producto</th>                      
                              <th>Usuario</th>                    
                              <th>Fecha Registro</th>                    
                              <th>Acciones</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $cont_productos = 0;
                            foreach ($costoproductos as $costoproducto) {
                              $cont_productos++;
                            ?>
                            <tr>                      
                              <td><?php echo $cont_productos;?></td>
                              <td><?php echo $costoproducto['planta']; ?></td>                      
                              <td><?php echo $costoproducto['producto']; ?></td>                      
                              <td><?php echo $costoproducto['costo_total']; ?></td>
                              <td><?php echo $costoproducto['costo_directo']; ?></td>                      
                              <td><?php echo $costoproducto['utilidad_producto']; ?></td>                      
                              <td><?php echo $costoproducto['usuario_mod']; ?></td>
                              <td><?php echo $costoproducto['fecha_registro']; ?></td>
                              <td>
                                <a href="../controller/controller_eliminar_costo_producto.php?id_costo_producto=<?php echo $costoproducto['id_costo_producto'];?>" class="btn btn-outline-danger">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash3-fill" viewBox="0 0 16 16">
                                    <path d="M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5m-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 
                                    0 1 0-.998.06m6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528M8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5"/>
                                  </svg>
                                </a>
                                <a href="../configuracion/editar_costo_tipo_block.php?id_costo_producto=<?php echo $costoproducto['id_costo_producto'];?>" class="btn btn-outline-warning">
                                  <i class="bi bi-pencil-fill"></i>
                                </a>
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
            function obtenerProductoxplanta() {
                var id_planta = document.getElementById("id_planta").value;

                // Verificar si se seleccionó una planta
                if (id_planta) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'consulta_plantas_costo_block.php?id_planta=' + id_planta, true);

                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);

                                // Verifica si la respuesta contiene productos
                                if (Array.isArray(response) && response.length > 0) {
                                    var select = document.getElementById("id_producto");
                                    select.innerHTML = '<option value="">Seleccione Producto</option>';

                                    response.forEach(function(producto) {
                                        var option = document.createElement("option");
                                        option.value = producto.id_producto;
                                        option.textContent = producto.producto;
                                        select.appendChild(option);
                                    });
                                } else {
                                    var select = document.getElementById("id_producto");
                                    select.innerHTML = '<option value="">No se encontraron productos</option>';
                                }
                            } catch (e) {
                                console.error("Error al procesar la respuesta JSON:", e);
                                alert("Error al procesar los productos de la planta.");
                            }
                        } else {
                            console.error("Error en la solicitud AJAX:", xhr.status);
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

            // Llamar a la función cuando cambie la selección de la planta
            document.getElementById("id_planta").addEventListener("change", obtenerProductoxplanta);
        });

      
      $('#tablaCostoBlock').DataTable({
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
