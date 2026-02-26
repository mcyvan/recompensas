<?php
include ("../app/config/config.php");
include ("../app/functions/auth.php");
include ("../app/functions/consultas.php");
verificarSesion();
$plantas = obtenerPlantas();
$productos = obtenerProductosTabla();

$id_producto=$_GET['id_producto'];
$productosEditar=obtenerProductosporID($id_producto);
foreach($productosEditar as $productoEditar){

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
              <div class="col-sm-6"><h3 class="mb-0">Actualizar Block</h3></div>
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
                        <div class="card-title"><b>Actualizar Block </b><?php echo $productoEditar['id_producto']; ?></div>                        
                    </div>
                    <!--end::Header-->
                    <!--begin::Form-->
                    <form class="needs-validation" novalidate="" action="../controller/controller_actualizar_tipo_block.php" method="post">                      
                        <!--begin::Body-->
                        <div class="card-body">
                        <!--begin::Row-->
                          <div class="row g-3">
                            <!--begin::Col-->
                            <div class="col-md-5">
                                <label for="" class="form-label"><b>Nombre Producto</b></label>
                                <input type="text" class="form-control" id="" value="<?php echo $productoEditar['producto']; ?>" name="nombre_producto" required>                            
                                <input type="hidden" class="form-control" id="" value="<?php echo $productoEditar['id_producto']; ?>" name="id_producto">                            
                            </div>
                            <!--end::Col--> 
                            <!--begin::Col-->
                            <div class="col-md-3">
                                <label for="" class="form-label"><b>Producto por Placas</b></label>
                                <input type="number" class="form-control" id="" value="<?php echo $productoEditar['producto_placas']; ?>" name="producto_placas" required>                            
                            </div>
                            <!--end::Col--> 
                            <!--begin::Col-->
                            <div class="col-md-3">
                                <label for="" class="form-label"><b>Meta de Placas</b></label>
                                <input type="number" class="form-control" id="" value="<?php echo $productoEditar['placas_meta']; ?>" name="placas_meta" required>                            
                            </div>
                            <!--end::Col--> 
                          </div>
                          <div class="row g-3 mt-2">
                            <div class="col-md-3">
                                <label for="" class="form-label"><b>Planta</b></label>
                                <select name="id_planta" id="" class="form-control">
                                    <option value="<?php echo $productoEditar['id_planta']; ?>"><?php echo $productoEditar['planta']; ?></option>
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
                            <button class="btn btn-outline-primary" type="submit">Actualizar Producto</button>
                            <a href="registrar_tipo_block.php" class="btn btn-outline-secondary">Regresar</a>
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
                              
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content-->
      </main>
      <!--end::App Main-->
      <script>

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
