<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <!--begin::Brand Link-->

    <!--begin::Brand Image-->
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="bi bi-boxes" viewBox="0 0 16 16">
      <path d="M7.752.066a.5.5 0 0 1 .496 0l3.75 2.143a.5.5 0 0 1 .252.434v3.995l3.498 2A.5.5 0 0 1 16 9.07v4.286a.5.5 0 0 1-.252.434l-3.75 2.143a.5.5 0 0 1-.496 0l-3.502-2-3.502 2.001a.5.5 0 0 1-.496 0l-3.75-2.143A.5.5 0 0 1 0 13.357V9.071a.5.5 0 0 1 .252-.434L3.75 6.638V2.643a.5.5 0 0 1 .252-.434zM4.25 7.504 1.508 9.071l2.742 1.567 2.742-1.567zM7.5 9.933l-2.75 1.571v3.134l2.75-1.571zm1 3.134 2.75 1.571v-3.134L8.5 9.933zm.508-3.996 2.742 1.567 2.742-1.567-2.742-1.567zm2.242-2.433V3.504L8.5 5.076V8.21zM7.5 8.21V5.076L4.75 3.504v3.134zM5.258 2.643 8 4.21l2.742-1.567L8 1.076zM15 9.933l-2.75 1.571v3.134L15 13.067zM3.75 14.638v-3.134L1 9.933v3.134z" />
    </svg>
    <!--end::Brand Image-->
    <!--begin::Brand Text-->
    <span class="brand-text fw-light">RECOMPENSAS</span>
    <!--end::Brand Text-->

    <!--end::Brand Link-->
  </div>
  <!--end::Sidebar Brand-->
  <!--begin::Sidebar Wrapper-->
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <!--begin::Sidebar Menu-->
      <ul
        class="nav sidebar-menu flex-column"
        data-lte-toggle="treeview"
        role="menu"
        data-accordion="false">
        <?php if ($_SESSION['rol'] == "ADMINISTRADOR") {; ?>
          <li class="nav-header">CLIENTES</li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="bi bi-window-plus"></i>
              <p>CLIENTES<i class="nav-arrow bi bi-chevron-right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../clientes/registrar_cliente.php" class="nav-link">
                  <i class="bi bi-person-plus"></i>
                  <p>ALTA CLIENTES</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../clientes/consultar_puntos.php" class="nav-link">
                  <i class="bi bi-search"></i>
                  <p>CONSULTA PUNTOS</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-header">CONFIGURACION</li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="bi bi-people"></i>
              <p>USUARIOS<i class="nav-arrow bi bi-chevron-right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../usuarios/registrar_usuarios.php" class="nav-link">
                  <i class="bi bi-person-plus-fill"></i>
                  <p>REGISTRAR USUARIO</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../usuarios/registrar_rol.php" class="nav-link">
                  <i class="bi bi-person-lines-fill"></i>
                  <p>REGISTRAR ROL</p>
                </a>
              </li>
            </ul>
          </li>
          <!-- <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="bi bi-journals"></i>
              <p>CATALOGOS<i class="nav-arrow bi bi-chevron-right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../configuracion/registrar_tipo_block.php" class="nav-link">
                  <i class="bi bi-journal-plus"></i>
                  <p>TIPO DE BLOCK</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../configuracion/registrar_costo_tipo_block.php" class="nav-link">
                  <i class="bi bi-currency-dollar"></i>
                  <p>COSTO DE BLOCK</p>
                </a>
              </li>
            </ul>
          </li> -->

          <!-- <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="bi bi-calendar2-week"></i>
              <p>CAPTURA PLAN PROD<i class="nav-arrow bi bi-chevron-right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../configuracion/captura_plan_oeste.php" class="nav-link">
                  <i class="bi bi-calendar-month"></i>
                  <p>CAPTURA OESTE</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../configuracion/captura_plan_sur.php" class="nav-link">
                  <i class="bi bi-calendar-month"></i>
                  <p>CAPTURA SUR</p>
                </a>
              </li>
            </ul>
          </li> -->
        <?php
        } elseif ($_SESSION['rol'] == "VENDEDORES") {; ?>
          <li class="nav-item">
            <a href="../clientes/registrar_cliente.php" class="nav-link">
              <i class="bi bi-window-plus"></i>
              <p>ALTA CLIENTES</p>
            </a>
          </li>

        <?php } elseif ($_SESSION['rol'] == "ADMINISTRACION") {; ?>
          <li class="nav-header">CLIENTES</li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="bi bi-window-plus"></i>
              <p>CLIENTES<i class="nav-arrow bi bi-chevron-right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../clientes/registrar_cliente.php" class="nav-link">
                  <i class="bi bi-window-plus"></i>
                  <p>ALTA CLIENTES</p>
                </a>
              </li>
            </ul>
            <!-- <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../captura/captura_produccion_sur.php" class="nav-link">
                  <i class="bi bi-window-plus"></i>
                  <p>CONSULTA PUNTOS</p>
                </a>
              </li>
            </ul> -->
          </li>

        <?php } ?>
      </ul>
      <!--end::Sidebar Menu-->
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->
</aside>