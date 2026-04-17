<!--begin::Start Navbar Links-->
<ul class="navbar-nav">
  <li class="nav-item">
    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5" />
      </svg>
    </a>
  </li>
</ul>

<!--end::Start Navbar Links-->

<!--begin::End Navbar Links-->
<ul class="navbar-nav ms-auto">
  <?php if ($_SESSION['rol'] == "OPERADOR" || $_SESSION['rol'] == "VENDEDOR") { ?>
    <!--begin::User Footer-->
    <li class="user-footer">
      <a href="../login/cerrar_sesion.php" class="btn btn-default btn-flat float-end">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-door-open-fill" viewBox="0 0 16 16">
          <path d="M1.5 15a.5.5 0 0 0 0 1h13a.5.5 0 0 0 0-1H13V2.5A1.5 1.5 0 0 0 11.5 1H11V.5a.5.5 0 0 0-.57-.495l-7 1A.5.5 0 0 0 3 1.5V15zM11 2h.5a.5.5 0 0 1 .5.5V15h-1zm-2.5 8c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1" />
        </svg>
      </a>
    </li>
    <!--end::User Footer-->
  <?php } else { ?>
    <li class="nav-item">
      <a class="nav-link" href="../administracion/inicio.php" role="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-house-door-fill" viewBox="0 0 16 16">
          <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5" />
        </svg>
      </a>
    </li>


    <!--begin::User Menu Dropdown-->
    <li class="nav-item dropdown user-menu">

      <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
          <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
        </svg>
      </a>

      <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">

        <!--begin::User Header-->
        <li class="user-header text-bg-secondary">

          <?php if ($_SESSION['rol'] == "ADMINISTRADOR") { ?>

            <p>
              <b>Usuario:</b> <?php echo $_SESSION['usuario']; ?>
            </p>

          <?php } elseif ($_SESSION['rol'] == "VENDEDOR" || $_SESSION['rol'] == "ADMINISTRACION") { ?>

            <p>
              <?php echo $_SESSION['nombre']; ?>
            </p>

            <p>
              <b>Usuario:</b> <?php echo $_SESSION['usuario']; ?>
            </p>

            <p>
              <b>Clientes:</b> <?php if (isset($_SESSION['clientes'])) {
                                  echo $_SESSION['clientes'];
                                } ?>
            </p>

          <?php } ?>

        </li>
        <!--end::User Header-->

        <!--begin::User Footer-->
        <li class="user-footer">
          <a href="../login/cerrar_sesion.php" class="btn btn-default btn-flat float-end">
            Cerrar Sesión
          </a>
        </li>
        <!--end::User Footer-->

      </ul>

    </li>
    <!--end::User Menu Dropdown-->
  <?php } ?>

</ul>
<!--end::End Navbar Links-->