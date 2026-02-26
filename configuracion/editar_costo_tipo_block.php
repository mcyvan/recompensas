<?php
include ("../app/config/config.php");
include ("../app/functions/auth.php");
include ("../app/functions/consultas.php");
verificarSesion();
$plantas = obtenerPlantas();
$id = $_GET['id_costo_producto'];
$id_costo_producto = obtenerCostoProductoId($id);
foreach($id_costo_producto as $id_costo_producto){
}
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
        <div class="container-fluid">
          <?php include("../app/layout/navbar.php");?>
        </div>
      </nav>
      <!--end::Header-->
      <!--begin::Sidebar-->
      <?php include("../app/layout/menu.php");?>
      <!--end::Sidebar-->
      <!--begin::App Main-->
      <main class="app-main">
        <div class="app-content-header">
          <div class="container-fluid">
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Actualizar de Costo Block</h3></div>
            </div>
          </div>
        </div>
        <div class="app-content">
          <div class="container-fluid">           
            <div class="row justify-content-center align-items-center">            
                <div class="card card-danger card-outline mb-4 col-sm-6">
                    <div class="card-header">
                        <div class="card-title"><b>Actualizar Costo Block <?php echo $id_costo_producto['id_costo_producto'];?></b></div>                        
                    </div>
                    <form class="needs-validation" novalidate="" action="../controller/controller_actualizar_costo_tipo_block.php" method="post">                      
                        <div class="card-body">
                          <div class="row g-3">
                            <div class="col-md-5">
                                <label for="" class="form-label"><b>Planta</b></label>
                                <input type="text" value="<?php echo $id_costo_producto['planta'];?>" class="form-control" readonly>                                                                                     
                                <input type="hidden" value="<?php echo $id_costo_producto['id_costo_producto'];?>" name="id_costo_producto" class="form-control" readonly>                                                                                     
                            </div> 
                            <div class="col-md-5">
                                <label for="" class="form-label"><b>Producto Block</b></label>
                                <input type="text" value="<?php echo $id_costo_producto['producto'];?>" name="producto" class="form-control" readonly>                                                           
                            </div> 
                            <div class="col-md-3">
                                <label for="" class="form-label"><b>Costo Total</b></label>
                                <input type="number" class="form-control" value="<?php echo $id_costo_producto['costo_total'];?>" name="costo_total" step="0.01" required>                            
                            </div>
                            <div class="col-md-3">
                                <label for="" class="form-label"><b>Costo Directo</b></label>
                                <input type="number" class="form-control"  value="<?php echo $id_costo_producto['costo_directo'];?>" name="costo_directo" step="0.01" required>                            
                            </div>
                          </div>  
                          <div class="card-footer mt-2">
                            <button class="btn btn-outline-primary" type="submit">Actualizar Costo Producto</button>
                            <a href="registrar_costo_tipo_block.php" class="btn btn-outline-secondary">Regresar</a>
                          </div>
                        </div>
                    </form>
                </div>
            </div>                    
          </div>
        </div>

        <!-- Tabla de Productos Registrados -->
        
      </main>

      
    </div>
  </body>
</html>
