<?php
include("../app/config/config.php");
include("../app/functions/auth.php");
include("../app/functions/consultas.php");
verificarSesion();
$roles = obtenerRoles();
$usuarios = obtenerUsuarios();

if (isset($_SESSION['mensaje_registro_usuario_correcto'])) {
  $mensaje_registro_usuario_correcto = $_SESSION['mensaje_registro_usuario_correcto'];
  unset($_SESSION['mensaje_registro_usuario_correcto']);
} else {
  $mensaje_registro_usuario_correcto = null;
}
if (isset($_SESSION['mensaje_registro_usuario_existe'])) {
  $mensaje_registro_usuario_existe = $_SESSION['mensaje_registro_usuario_existe'];
  unset($_SESSION['mensaje_registro_usuario_existe']);
} else {
  $mensaje_registro_usuario_existe = null;
}
if (isset($_SESSION['mensaje_actualizar_usuario_correcto'])) {
  $mensaje_actualizar_usuario_correcto = $_SESSION['mensaje_actualizar_usuario_correcto'];
  unset($_SESSION['mensaje_actualizar_usuario_correcto']);
} else {
  $mensaje_actualizar_usuario_correcto = null;
}
if (isset($_SESSION['mensaje_registro_usuario_eliminado'])) {
  $mensaje_registro_usuario_eliminado = $_SESSION['mensaje_registro_usuario_eliminado'];
  unset($_SESSION['mensaje_registro_usuario_eliminado']);
} else {
  $mensaje_registro_usuario_eliminado = null;
}

?>
<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
  <?php include('../app/layout/head.php'); ?>
  <title>Usuarios - Registrar</title>
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <?php if ($mensaje_registro_usuario_correcto): ?>
    <script>
      Swal.fire({
        title: "",
        text: "<?php echo $mensaje_registro_usuario_correcto; ?>",
        icon: "success"
      });
    </script>
  <?php endif; ?>
  <?php if ($mensaje_registro_usuario_existe): ?>
    <script>
      Swal.fire({
        title: "",
        text: "<?php echo $mensaje_registro_usuario_existe; ?>",
        icon: "error"
      });
    </script>
  <?php endif; ?>
  <?php if ($mensaje_actualizar_usuario_correcto): ?>
    <script>
      Swal.fire({
        title: "",
        text: "<?php echo $mensaje_actualizar_usuario_correcto; ?>",
        icon: "success"
      });
    </script>
  <?php endif; ?>
  <?php if ($mensaje_registro_usuario_eliminado): ?>
    <script>
      Swal.fire({
        title: "",
        text: "<?php echo $mensaje_registro_usuario_eliminado; ?>",
        icon: "success"
      });
    </script>
  <?php endif; ?>

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
              <h3 class="mb-0">Registro de Usuarios</h3>
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
                <div class="card-title"><b>Registro de Usuario Nuevo</b></div>
              </div>
              <!--end::Header-->
              <!--begin::Form-->
              <form class="needs-validation" novalidate="" action="../controller/controller_registrar_usuario.php" method="post">
                <!--begin::Body-->
                <div class="card-body">
                  <!--begin::Row-->
                  <div class="row g-3">
                    <!--begin::Col-->
                    <div class="col-md-5">
                      <label for="" class="form-label"><b>Nombre (s)</b></label>
                      <input type="text" class="form-control" id="" value="" name="nombre" required>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Apellido Paterno</b></label>
                      <input type="text" class="form-control" id="" value="" name="apellido_p" required>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Apellido Materno</b></label>
                      <input type="text" class="form-control" id="" value="" name="apellido_m" required>
                    </div>
                    <!--end::Col-->
                  </div>
                  <!--begin::Row-->
                  <div class="row g-3 mt-2">
                    <!--begin::Col-->
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Telefono</b></label>
                      <input type="tel" class="form-control" id="" value="" name="telefono" pattern="[0-9]{10}" required>
                    </div>
                    <!--end::Col-->
                  </div>
                  <div class="row g-3 mt-2">
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Rol</b></label>
                      <select name="id_rol" id="" class="form-control">
                        <option value="">Selecciona Rol</option>
                        <?php foreach ($roles as $rol) { ?>
                          <option value="<?php echo $rol['id_rol']; ?>"><?php echo $rol['rol']; ?></option>
                        <?php } ?>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Usuario</b></label>
                      <input type="text" class="form-control" id="" value="" name="usuario" required>
                    </div>
                    <div class="col-md-3">
                      <label for="" class="form-label"><b>Password</b></label>
                      <input type="password" class="form-control" id="" value="" name="password" required>
                    </div>
                  </div>
                  <!--end::Col-->
                  <!--end::Body-->
                  <!--begin::Footer-->
                  <div class="card-footer mt-2">
                    <button class="btn btn-outline-primary" type="submit">Registrar Usuario</button>

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
                  <b>Tabla Usuarios</b>
                </div>
                <!-- <div class="card-title col-sm-4">
                        <button id="exportExcel" class="btn btn-success mr-2">Exportar a Excel</button>
                        </div>  -->
              </div>
              <!--end::Header-->
              <div class="card-body p-1">
                <table id="tablaUsuarios" class="hover" style="width:100%">
                  <thead>
                    <tr>
                      <th style="width: 10px">#</th>
                      <th>Nombre</th>
                      <th>Apellido P</th>
                      <th>Apellido M</th>
                      <th>Usuario</th>
                      <th>Rol</th>
                      <th>Estatus</th>
                      <th>Fecha Registro</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $cont_usuarios = 0;
                    foreach ($usuarios as $usuario) {
                      $cont_usuarios = $cont_usuarios + 1;
                    ?>
                      <tr>
                        <td><?php echo $cont_usuarios; ?></td>
                        <td><?php echo $usuario['nombres']; ?></td>
                        <td><?php echo $usuario['apellido_p']; ?></td>
                        <td><?php echo $usuario['apellido_m']; ?></td>
                        <td><?php echo $usuario['usuario']; ?></td>
                        <td><?php echo $usuario['rol']; ?></td>
                        <td><?php echo $usuario['estatus'] == 1 ? 'Activo' : 'Inactivo'; ?></td>
                        <td><?php echo $usuario['fecha_registro']; ?></td>
                        <td><a href="../controller/controller_eliminar_usuario.php?id_usuario=<?php echo $usuario['id_usuario']; ?>" class="btn btn-outline-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-dash-fill" viewBox="0 0 16 16">
                              <path fill-rule="evenodd" d="M11 7.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5" />
                              <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
                            </svg></a>
                          <a href="../usuarios/editar_usuario.php?id_usuario=<?php echo $usuario['id_usuario']; ?>" class="btn btn-outline-warning"><svg fill="currentColor" width="16" height="16" viewBox="0 0 640 640" xmlns="http://www.w3.org/2000/svg">
                              <path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h274.9c-2.4-6.8-3.4-14-2.6-21.3l6.8-60.9 1.2-11.1 7.9-7.9 77.3-77.3c-24.5-27.7-60-45.5-99.9-45.5zm45.3 145.3l-6.8 61c-1.1 10.2 7.5 18.8 17.6 17.6l60.9-6.8 137.9-137.9-71.7-71.7-137.9 137.8zM633 268.9L595.1 231c-9.3-9.3-24.5-9.3-33.8 0l-37.8 37.8-4.1 4.1 71.8 71.7 41.8-41.8c9.3-9.4 9.3-24.5 0-33.9z" />
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

    <!--begin::Footer-->
    <?php include("../app/layout/footer.php"); ?>
    <!--end::Footer-->
  </div>
  <!--end::App Wrapper-->
  <?php include("../app/layout/footer_links.php"); ?>
  <script>
    $(document).ready(function() {
      $('#tablaUsuarios').DataTable({
        "language": {
          "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
      });
    });
  </script>


</body>
<!--end::Body-->

</html>