<?php
include ("../app/config/config.php");
include ("../app/functions/auth.php");
include ("../app/functions/consultas.php");
verificarSesion();
$id_plan_produccion=$_GET['id_plan_produccion'];
$plan_produccion=obtenerPlanProduccion($id_plan_produccion);
foreach($plan_produccion as $plan){
    $id_plan_produccion=$plan['id_plan_produccion'];
    $id_producto=$plan['id_producto'];
    $fecha_plan=$plan['fecha_plan'];
    $producto=$plan['producto'];
}
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
                                <div class="col-sm-6">
                                    <h3 class="mb-0">Captura Plan Produccion Oeste</h3>
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
                                            <div class="card-title">
                                                <b>Captura Plan Produccion Oeste - <?php echo $id_plan_produccion ?></b>
                                            </div>                        
                                        </div>
                                        <!--end::Header-->
                                        <!--begin::Form-->
                                        <form class="needs-validation" novalidate="" action="../controller/controller_actualizar_plan_captura_oeste.php" method="post">                      
                                            <!--begin::Body-->
                                            <div class="card-body">
                                                <!--begin::Row-->
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <input type="hidden" class="form-control" id="id_plan_produccion" value="<?php echo $id_plan_produccion;?>" name="id_plan_produccion" required>
                                                        <label for="" class="form-label"><b>Tipo de Pieza</b></label>
                                                        <select name="id_producto" class="form-control" id="" required>
                                                            <option value="<?php echo $id_producto;?>"><?php echo $producto;?></option>
                                                            <?php foreach($productos as $producto):?>
                                                                <option value="<?php echo $producto['id_producto'];?>"><?php echo $producto['producto'];?></option>
                                                            <?php endforeach;?>
                                                        </select>
                                                    </div>                           
                                                <!--begin::Col-->
                                                <div class="col-md-4">
                                                    <label for="" class="form-label"><b>Fecha Produccion</b></label>
                                                    <input type="date" class="form-control" id="fecha" value="<?php echo $fecha_plan;?>" name="fecha_plan" required>                            
                                                </div>
                                            </div>                         
                                        </div><!--end::Body-->
                                        <!--begin::Footer-->
                                        <div class="card-footer mt-2">
                                            <button class="btn btn-warning" type="submit">Actualizar Dia</button>                            
                                            <a href="../configuracion/captura_plan_oeste.php" class="btn btn-secondary">Regresar</a>
                                        </div><!--end::Footer-->
                                    </form><!--end::Form-->
                                </div>
                            </div><!-- /.row (main row) -->                    
                        <!--end::Container-->
                    </div>
                <!--end::App Content-->
            </div>
             
      </main>
      <!--end::App Main-->
   
      <!--begin::Footer-->
      <?php include("../app/layout/footer.php");?>
      <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <?php include("../app/layout/footer_links.php");?>

  </body>
  <!--end::Body-->
</html>
