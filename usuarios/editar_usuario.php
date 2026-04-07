<?php
include("../app/config/config.php");
include("../app/functions/auth.php");
include("../app/functions/consultas.php");
verificarSesion();
$id_usuario = $_GET['id_usuario'];
$roles = obtenerRoles();
$usuarios = obtenerUsuario($id_usuario);
foreach ($usuarios as $usuario) {
}
?>
<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
  <?php include('../app/layout/head.php'); ?>
  <title>Configuracion - Usuarios</title>
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
              <h3 class="mb-0">Actualizar Usuario</h3>
            </div>
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
              <form class="needs-validation" novalidate="" action="../controller/controller_actualizar_usuario.php" method="post">
                <div class="card-header">
                  <div class="card-title"><b>Actualizar Usuario <?php echo $usuario['id_usuario']; ?></b></div>
                  <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                </div>
                <!--end::Header-->
                <!--begin::Form-->
                <!--begin::Body-->
                <div class="card-body">
                  <!--begin::Row-->
                  <div class="row g-3">
                    <!--begin::Col-->
                    <div class="col-md-5">
                      <label for="" class="form-label"><b>Nombre (s)</b></label>
                      <input type="text" class="form-control" id="" value="<?php echo $usuario['nombres']; ?>" name="nombres" required>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Apellido Paterno</b></label>
                      <input type="text" class="form-control" id="" value="<?php echo $usuario['apellido_p']; ?>" name="apellido_p" required>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Apellido Materno</b></label>
                      <input type="text" class="form-control" id="" value="<?php echo $usuario['apellido_m']; ?>" name="apellido_m" required>
                    </div>
                    <!--end::Col-->
                  </div>
                  <div class="row g-3 mt-2">
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Telefono</b></label>
                      <input type="tel" class="form-control" id="" value="<?php echo $usuario['telefono']; ?>" name="telefono" required>
                    </div>
                  </div>
                  <div class="row g-3 mt-2">
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Rol</b></label>
                      <select name="id_rol" id="" class="form-control">
                        <option value="<?php echo $usuario['id_rol']; ?>"><?php echo $usuario['rol']; ?></option>
                        <?php foreach ($roles as $rol) { ?>
                          <option value="<?php echo $rol['id_rol']; ?>"><?php echo $rol['rol']; ?></option>
                        <?php } ?>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Usuario</b></label>
                      <input type="text" class="form-control" id="" value="<?php echo $usuario['usuario']; ?>" name="usuario" required>
                    </div>
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Password</b></label>
                      <input type="password" class="form-control" id="" value="" name="password" placeholder="Solo llenar para actualizar ">
                    </div>
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Estatus</b></label>
                      <select name="estatus" id="" class="form-control">
                        <option value="<?php echo $usuario['estatus']; ?>"><?php echo $usuario['estatus'] == 1 ? 'Activo' : 'Inactivo'; ?></option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                      </select>
                    </div>
                  </div>
                  <!--end::Col-->
                  <!--end::Body-->
                  <!--begin::Footer-->
                  <div class="card-footer mt-2">
                    <button class="btn btn-outline-warning" type="submit">Actualizar Usuario</button>
                    <a href="registrar_usuarios.php" class="btn btn-outline-secondary">Regresar</a>
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

          <!-- /.row (main row) -->
        </div>
        <!--end::Container-->
      </div>
      <!--end::App Content-->
    </main>
    <!--end::App Main-->

    <!--begin::Footer-->
    <?php include("../app/layout/footer.php"); ?>
    <!--end::Footer-->
  </div>
  <!--end::App Wrapper-->
  <?php include("../app/layout/footer_links.php"); ?>

</body>
<!--end::Body-->

</html>