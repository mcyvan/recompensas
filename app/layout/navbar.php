<!--begin::Start Navbar Links-->
<ul class="navbar-nav">
  <li class="nav-item">
    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
      <i class="bi bi-list fs-4"></i>
    </a>
  </li>
</ul>
<!--end::Start Navbar Links-->

<!--begin::End Navbar Links-->
<ul class="navbar-nav ms-auto">
  <?php if (in_array($_SESSION['rol'] ?? '', ['ADMINISTRADOR', 'ADMINISTRACION'], true)) { ?>
    <li class="nav-item">
      <a class="nav-link" href="../administracion/inicio.php" title="Inicio">
        <i class="bi bi-house-door-fill fs-5"></i>
      </a>
    </li>
  <?php } ?>

  <li class="nav-item d-flex align-items-center px-2">
    <span class="me-2 d-flex align-items-center gap-2">
      <span class="fw-semibold">
        <?php echo htmlspecialchars($_SESSION['usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
      </span>

      <span class="badge text-bg-secondary">
        <?php echo htmlspecialchars($_SESSION['rol'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
      </span>
    </span>
    </span>

    <a href="../login/cerrar_sesion.php" id="btn-cerrar-sesion" class="btn btn-outline-secondary btn-sm" title="Cerrar sesion">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </li>
</ul>
<!--end::End Navbar Links-->

<script>
  document.getElementById('btn-cerrar-sesion').addEventListener('click', function(evento) {
    evento.preventDefault();
    const urlCerrarSesion = this.href;

    Swal.fire({
      icon: 'question',
      title: '\u00bfDesea cerrar sesi\u00f3n?',
      showCancelButton: true,
      confirmButtonText: 'S\u00ed, salir',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545'
    }).then(resultado => {
      if (resultado.isConfirmed) {
        window.location.href = urlCerrarSesion;
      }
    });
  });
</script>