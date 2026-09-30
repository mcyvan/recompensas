<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
require_once('../app/functions/configuracion_canje.php');
verificarSesion();

$configuracionCentroCanje = obtenerConfiguracionCentroCanje($pdo);

$rol = $_SESSION['rol'] ?? '';
$idUsuario = (int) ($_SESSION['id_usuario_login'] ?? 0);
$esAdministracion = in_array($rol, ['ADMINISTRADOR', 'ADMINISTRACION'], true);
$esVendedor = $rol === 'VENDEDOR';
$esLogistica = $rol === 'LOGISTICA';
$puedeAdministrarClientes = $esAdministracion || $esVendedor;
$puedeDarAltaClientes = $puedeAdministrarClientes || $esLogistica;
$puedeElegirVendedorCliente = $esAdministracion || $esLogistica;
$puedeVerClientes = $puedeAdministrarClientes || $esLogistica;

$clientes = ($esAdministracion || $esLogistica)
    ? obtenerClientes()
    : ($esVendedor ? obtenerClientesPorUsuario($idUsuario) : []);

$vendedores = $puedeElegirVendedorCliente ? obtenerVendedores() : [];
$mostrarDashboardClientes = $esAdministracion || $esLogistica;
$resumenDashboardClientes = $mostrarDashboardClientes ? obtenerResumenDashboardClientes() : [];
$dashboardClientesPorVendedor = $mostrarDashboardClientes ? obtenerDashboardClientesPorVendedor() : [];
$clientesSinActividadPorVendedor = $mostrarDashboardClientes ? obtenerClientesSinActividadPorVendedor() : [];
$tabActivoClientes = ($_GET['tab'] ?? '') === 'dashboard' && $mostrarDashboardClientes ? 'dashboard' : 'clientes';
$vistaDashboardClientes = $tabActivoClientes === 'dashboard';
$tituloClientes = $vistaDashboardClientes ? 'Dashboard Clientes' : ($puedeDarAltaClientes ? 'Registro de Clientes' : 'Tabla Clientes');

if (isset($_SESSION['mensaje_registro_clientes_correcto'])) {
    $mensaje_registro_clientes_correcto = $_SESSION['mensaje_registro_clientes_correcto'];
    unset($_SESSION['mensaje_registro_clientes_correcto']);
} else {
    $mensaje_registro_clientes_correcto = null;
}
if (isset($_SESSION['mensaje_registro_cliente_existe'])) {
    $mensaje_registro_cliente_existe = $_SESSION['mensaje_registro_cliente_existe'];
    unset($_SESSION['mensaje_registro_cliente_existe']);
} else {
    $mensaje_registro_cliente_existe = null;
}
if (isset($_SESSION['mensaje_actualizar_cliente_correcto'])) {
    $mensaje_actualizar_cliente_correcto = $_SESSION['mensaje_actualizar_cliente_correcto'];
    unset($_SESSION['mensaje_actualizar_cliente_correcto']);
} else {
    $mensaje_actualizar_cliente_correcto = null;
}
if (isset($_SESSION['mensaje_registro_cliente_eliminado'])) {
    $mensaje_registro_cliente_eliminado = $_SESSION['mensaje_registro_cliente_eliminado'];
    unset($_SESSION['mensaje_registro_cliente_eliminado']);
} else {
    $mensaje_registro_cliente_eliminado = null;
}

?>
<!doctype html>
<html lang="es">
<!--begin::Head-->

<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Clientes - Registrar</title>
    <style>
        .acciones-cliente {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .credencial-cliente-preview {
            width: min(100%, 420px);
            margin: 0 auto;
            border: 1px solid #d7dde8;
            border-radius: 8px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.14);
            overflow: hidden;
            position: relative;
            color: #172033;
        }

        .credencial-cliente-tarjeta {
            aspect-ratio: 85.6 / 54;
            background: linear-gradient(135deg, #ffffff 0%, #f7f9fc 58%, #eef4fb 100%);
            position: relative;
        }

        .credencial-cliente-tarjeta::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 12px;
            background: linear-gradient(90deg, #c41230 0%, #c41230 40%, #174a94 40%, #174a94 100%);
        }

        .credencial-cliente-inner {
            height: 100%;
            padding: 20px 20px 14px;
            display: grid;
            grid-template-columns: 1fr 150px;
            gap: 16px;
            align-items: start;
        }

        .credencial-cliente-pie {
            padding: 8px 20px;
            font-size: 0.72rem;
            line-height: 1.35;
            text-align: center;
            color: #42526a;
            background: #f1f4f9;
            border-top: 1px solid #d7dde8;
        }

        .credencial-cliente-pie-icono {
            width: 12px;
            height: 12px;
            vertical-align: -1px;
            margin: 0 2px;
        }

        .credencial-cliente-logo {
            width: 150px;
            height: 80px;
            object-fit: contain;
            display: block;
            margin-bottom: 6px;
        }

        .credencial-cliente-titulo {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #c41230;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .credencial-cliente-nombre {
            font-size: clamp(1rem, 3vw, 1.45rem);
            font-weight: 800;
            line-height: 1.05;
            margin-bottom: 6px;
            word-break: break-word;
        }

        .credencial-cliente-dato {
            font-size: 0.9rem;
            margin-bottom: 0;
            color: #42526a;
        }

        .credencial-cliente-dato strong {
            color: #172033;
        }

        .credencial-cliente-qr {
            justify-self: end;
            align-self: center;
            width: 150px;
            height: 150px;
            padding: 8px;
            background: #ffffff;
            border: 1px solid #e0e6ef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .credencial-cliente-qr img,
        .credencial-cliente-qr canvas {
            width: 130px !important;
            height: 130px !important;
        }

        .credencial-cliente-texto-qr {
            font-size: 0.78rem;
            color: #64748b;
            word-break: break-word;
        }

        .dashboard-clientes-metric {
            border: 1px solid #dde4ee;
            border-left: 4px solid #174a94;
            border-radius: 8px;
            background: #ffffff;
            padding: 14px 16px;
            height: 100%;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }

        .dashboard-clientes-metric.success {
            border-left-color: #198754;
        }

        .dashboard-clientes-metric.warning {
            border-left-color: #ffc107;
        }

        .dashboard-clientes-metric.danger {
            border-left-color: #dc3545;
        }

        .dashboard-clientes-metric .label {
            color: #64748b;
            font-size: 0.82rem;
        }

        .dashboard-clientes-metric .value {
            color: #172033;
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.1;
        }
    </style>
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <?php if ($mensaje_registro_clientes_correcto): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_clientes_correcto; ?>",
                icon: "success"
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_registro_cliente_existe): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_cliente_existe; ?>",
                icon: "error"
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_actualizar_cliente_correcto): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_actualizar_cliente_correcto; ?>",
                icon: "success"
            });
        </script>
    <?php endif; ?>
    <?php if ($mensaje_registro_cliente_eliminado): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_cliente_eliminado; ?>",
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
                            <h3 class="mb-0"><?= $tituloClientes ?></h3>
                        </div>
                        <div class="col-sm-6">
                        </div>
                    </div>
                    <!--end::Row-->
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content Header-->
            <?php if (!$vistaDashboardClientes && $puedeDarAltaClientes): ?>
            <!--begin::App Content-->
            <div class="app-content">
                <!--begin::Container-->
                <div class="container-fluid">
                    <!--begin::Row-->
                    <div class="row justify-content-center align-items-center">
                        <div class="card card-danger card-outline mb-4 col-sm-8">
                            <!--begin::Header-->
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="card-title mb-0"><b>Capturar cliente</b></div>
                                <button class="btn btn-outline-primary btn-sm ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAltaCliente" aria-expanded="false" aria-controls="collapseAltaCliente">
                                    <i class="bi bi-chevron-down"></i> Abrir formulario
                                </button>
                            </div>
                            <!--end::Header-->
                            <!--begin::Form-->
                            <div class="collapse" id="collapseAltaCliente">
                                <form class="needs-validation" novalidate="" action="../controller/controller_registrar_cliente.php" method="post">
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
                                            <div class="col-md-5">
                                                <label for="" class="form-label"><b>Correo</b></label>
                                                <input type="email" class="form-control" id="correo" value="" name="correo" required>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" value="1" id="sin_correo" name="sin_correo">
                                                    <label class="form-check-label" for="sin_correo">
                                                        Sin correo electronico
                                                    </label>
                                                </div>
                                            </div>
                                            <!--begin::Col-->
                                            <div class="col-md-3">
                                                <label for="" class="form-label"><b>Telefono</b></label>
                                                <input type="tel" class="form-control" id="" value="" name="telefono" pattern="[0-9]{10}" required>
                                            </div>
                                            <!--end::Col-->
                                            <!--begin::Col-->
                                            <div class="col-md-3">
                                                <label for="" class="form-label"><b>Fecha Nacimiento</b></label>
                                                <input type="date" class="form-control" id="" value="" name="fecha_nacimiento" required>
                                            </div>
                                            <!--end::Col-->
                                            <?php if ($puedeElegirVendedorCliente) { ?>
                                                <!--begin::Col-->
                                                <div class="col-md-3">
                                                    <label for="" class="form-label"><b>Vendedor</b></label>
                                                    <select name="id_vendedor" id="vendedor" class="form-control" required>
                                                        <option value="">Seleccione un vendedor</option>
                                                        <?php foreach ($vendedores as $vendedor): ?>
                                                            <option value="<?php echo $vendedor['id_usuario']; ?>">
                                                                <?php echo $vendedor['usuario']; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            <?php } ?>
                                            <!--end::Col-->
                                        </div>
                                        <!--begin::Footer-->
                                        <div class="card-footer mt-3 d-flex flex-column align-items-stretch">
                                            <button class="btn btn-primary m-2" type="submit">GUARDAR</button>
                                            <a href="<?php echo ($_SESSION['rol'] == 'VENDEDOR') ? '../vendedor/menu_vendedor.php' : '../clientes/registrar_cliente.php'; ?>" class="btn btn-secondary m-2">
                                                CANCELAR
                                            </a>
                                        </div>
                                    </div>
                                    <!--end::Footer-->
                                </form>
                            </div>
                            <!--end::Form-->
                        </div>
                    </div>
                    <!-- /.row (main row) -->
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content-->
            <?php endif; ?>
            <!--begin::App Content-->
            <div class="app-content">
                <!--begin::Container-->
                <div class="container-fluid">
                    <!--begin::Row-->
                    <?php if ($puedeVerClientes) { ?>
                        <div class="row justify-content-center align-items-center">
                            <div class="card card-danger card-outline mb-4 col-sm-12">
                                <!--begin::Header-->
                                <div class="card-header">
                                    <div class="card-title col-sm-6">
                                        <b><?= $vistaDashboardClientes ? 'Dashboard Clientes' : ($esVendedor ? 'Mis clientes' : 'Tabla Clientes') ?></b>
                                    </div>
                                    <?php if ($rol === 'ADMINISTRADOR' || $puedeElegirVendedorCliente || $esVendedor) { ?>
                                        <div class="card-tools d-flex flex-wrap gap-2 align-items-center">
                                            <?php if ($rol === 'ADMINISTRADOR'): ?>
                                                <a href="exportar_excel.php" class="btn btn-success btn-sm">
                                                    Exportar a Excel
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($puedeElegirVendedorCliente): ?>
                                                <form action="reporte_clientes_vendedor.php" method="get" class="d-flex flex-wrap gap-2 align-items-center">
                                                    <select name="id_vendedor" class="form-select form-select-sm" required>
                                                        <option value="">Reporte por vendedor</option>
                                                        <?php foreach ($vendedores as $vendedor): ?>
                                                            <option value="<?php echo (int) $vendedor['id_usuario']; ?>">
                                                                <?php echo htmlspecialchars($vendedor['usuario'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-outline-success btn-sm">
                                                        Generar reporte
                                                    </button>
                                                </form>
                                            <?php elseif ($esVendedor): ?>
                                                <a href="reporte_clientes_vendedor.php" class="btn btn-outline-success btn-sm">
                                                    Descargar mis clientes
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php } ?>
                                </div>
                                <!--end::Header-->
                                <div class="card-body p-1">
                                    <div class="tab-content">
                                        <?php if (!$vistaDashboardClientes): ?>
                                        <div class="tab-pane fade show active" id="panel-clientes" role="tabpanel" aria-labelledby="tab-clientes">
                                    <div class="table-responsive">
                                        <table id="tablaClientes" class="table table-striped table-bordered" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th style="width: 20px">#</th>
                                                    <th>Nombre</th>
                                                    <th>Apellido P</th>
                                                    <th>Apellido M</th>
                                                    <th>Correo</th>
                                                    <th>Telefono</th>
                                                    <th>Fecha Nacimiento</th>
                                                    <th>Fecha Registro</th>
                                                    <th>Vendedor</th>
                                                    <th>Estatus</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $cont_clientes = 0;
                                                foreach ($clientes as $cliente) {
                                                    $cont_clientes = $cont_clientes + 1;
                                                ?>
                                                    <tr>
                                                        <td><?php echo $cont_clientes; ?></td>
                                                        <td><?php echo $cliente['nombres']; ?></td>
                                                        <td><?php echo $cliente['apellido_p']; ?></td>
                                                        <td><?php echo $cliente['apellido_m']; ?></td>
                                                        <td>
                                                            <?php
                                                            if (str_starts_with((string) $cliente['correo'], 'sin-correo-')) {
                                                                echo 'Sin correo';
                                                            } else {
                                                                echo $cliente['correo'];
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php echo $cliente['telefono']; ?></td>
                                                        <td><?php echo $cliente['fecha_nacimiento']; ?></td>
                                                        <td><?php echo $cliente['fecha_registro']; ?></td>
                                                        <td><?php echo $cliente['usuario']; ?></td>
                                                        <td>
                                                            <?php
                                                            if ($cliente['estatus'] == 1) {
                                                                echo "Activo";
                                                            } else {
                                                                echo "Inactivo";
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $nombreCompletoCliente = trim($cliente['nombres'] . ' ' . $cliente['apellido_p'] . ' ' . $cliente['apellido_m']);
                                                            ?>
                                                            <div class="acciones-cliente">
                                                                <?php if ($puedeAdministrarClientes): ?>
                                                                    <?php if ($esAdministracion): ?>
                                                                    <a href="../controller/controller_eliminar_cliente.php?id_cliente=<?php echo $cliente['id_cliente']; ?>" class="btn btn-outline-danger" title="Desactivar cliente"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-dash-fill" viewBox="0 0 16 16">
                                                                        <path fill-rule="evenodd" d="M11 7.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5" />
                                                                        <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
                                                                    </svg></a>
                                                                    <?php endif; ?>
                                                                    <a href="../clientes/editar_cliente.php?id_cliente=<?php echo $cliente['id_cliente']; ?>" class="btn btn-outline-warning" title="Editar cliente"><svg fill="currentColor" width="16" height="16" viewBox="0 0 640 640" xmlns="http://www.w3.org/2000/svg">
                                                                        <path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 
                                                                    48h274.9c-2.4-6.8-3.4-14-2.6-21.3l6.8-60.9 1.2-11.1 7.9-7.9 77.3-77.3c-24.5-27.7-60-45.5-99.9-45.5zm45.3 145.3l-6.8 61c-1.1 10.2 7.5 18.8 17.6 17.6l60.9-6.8 137.9-137.9-71.7-71.7-137.9 137.8zM633 268.9L595.1 231c-9.3-9.3-24.5-9.3-33.8 0l-37.8 37.8-4.1 4.1 71.8 71.7 41.8-41.8c9.3-9.4 9.3-24.5 0-33.9z" />
                                                                    </svg></a>
                                                                <?php endif; ?>
                                                                <button type="button"
                                                                    class="btn btn-outline-primary btn-credencial-cliente"
                                                                    title="Credencial QR"
                                                                    data-id="<?php echo (int) $cliente['id_cliente']; ?>"
                                                                    data-nombre="<?php echo htmlspecialchars($nombreCompletoCliente, ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-telefono="<?php echo htmlspecialchars((string) $cliente['telefono'], ENT_QUOTES, 'UTF-8'); ?>">
                                                                    <i class="bi bi-qr-code"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($mostrarDashboardClientes && $vistaDashboardClientes): ?>
                                            <div class="tab-pane fade show active" id="panel-dashboard-clientes" role="tabpanel" aria-labelledby="tab-dashboard-clientes">
                                                <div class="p-3">
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-xl-3 col-md-4 col-6">
                                                            <div class="dashboard-clientes-metric">
                                                                <div class="label">Clientes registrados</div>
                                                                <div class="value"><?= number_format((float) ($resumenDashboardClientes['total_clientes'] ?? 0), 0) ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-xl-3 col-md-4 col-6">
                                                            <div class="dashboard-clientes-metric success">
                                                                <div class="label">Clientes activos</div>
                                                                <div class="value"><?= number_format((float) ($resumenDashboardClientes['clientes_activos'] ?? 0), 0) ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-xl-3 col-md-4 col-6">
                                                            <div class="dashboard-clientes-metric warning">
                                                                <div class="label">Nuevos este mes</div>
                                                                <div class="value"><?= number_format((float) ($resumenDashboardClientes['clientes_mes'] ?? 0), 0) ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-xl-3 col-md-4 col-6">
                                                            <div class="dashboard-clientes-metric danger">
                                                                <div class="label">Inactivos</div>
                                                                <div class="value"><?= number_format((float) ($resumenDashboardClientes['clientes_inactivos'] ?? 0), 0) ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-xl-3 col-md-4 col-6">
                                                            <div class="dashboard-clientes-metric success">
                                                                <div class="label">Clientes con concreto</div>
                                                                <div class="value"><?= number_format((float) ($resumenDashboardClientes['clientes_con_remision'] ?? 0), 0) ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-xl-3 col-md-4 col-6">
                                                            <div class="dashboard-clientes-metric success">
                                                                <div class="label">Clientes con puntos</div>
                                                                <div class="value"><?= number_format((float) ($resumenDashboardClientes['clientes_con_puntos'] ?? 0), 0) ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-xl-3 col-md-4 col-6">
                                                            <div class="dashboard-clientes-metric">
                                                                <div class="label">Metros acumulados</div>
                                                                <div class="value"><?= number_format((float) ($resumenDashboardClientes['total_metros'] ?? 0), 2) ?> m&sup3;</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-xl-3 col-md-4 col-6">
                                                            <div class="dashboard-clientes-metric">
                                                                <div class="label">Puntos generados</div>
                                                                <div class="value"><?= number_format((float) ($resumenDashboardClientes['total_puntos'] ?? 0), 2) ?></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row g-3">
                                                        <div class="col-xl-8">
                                                            <h6 class="fw-bold mb-2">Desempeno por vendedor</h6>
                                                            <div class="table-responsive">
                                                                <table id="tablaDashboardVendedores" class="table table-sm table-striped table-bordered align-middle" style="width:100%">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Vendedor</th>
                                                                            <th>Clientes</th>
                                                                            <th>Nuevos mes</th>
                                                                            <th>Con concreto</th>
                                                                            <th>Con puntos</th>
                                                                            <th>Conversion</th>
                                                                            <th>Remisiones</th>
                                                                            <th>Metros</th>
                                                                            <th>Puntos</th>
                                                                            <th>Ultima remision</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($dashboardClientesPorVendedor as $fila): ?>
                                                                            <?php
                                                                            $totalClientesVendedor = (int) ($fila['total_clientes'] ?? 0);
                                                                            $clientesConPuntos = (int) ($fila['clientes_con_puntos'] ?? 0);
                                                                            $conversion = $totalClientesVendedor > 0 ? ($clientesConPuntos / $totalClientesVendedor) * 100 : 0;
                                                                            ?>
                                                                            <tr>
                                                                                <td><?= htmlspecialchars($fila['vendedor'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                                <td><?= number_format((float) $fila['total_clientes'], 0) ?></td>
                                                                                <td><?= number_format((float) $fila['clientes_mes'], 0) ?></td>
                                                                                <td><?= number_format((float) $fila['clientes_con_remision'], 0) ?></td>
                                                                                <td><?= number_format((float) $fila['clientes_con_puntos'], 0) ?></td>
                                                                                <td><?= number_format($conversion, 1) ?>%</td>
                                                                                <td><?= number_format((float) $fila['remisiones_validas'], 0) ?></td>
                                                                                <td><?= number_format((float) $fila['total_metros'], 2) ?></td>
                                                                                <td><?= number_format((float) $fila['total_puntos'], 2) ?></td>
                                                                                <td><?= htmlspecialchars($fila['ultima_remision'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>

                                                        <div class="col-xl-4">
                                                            <h6 class="fw-bold mb-2">Clientes activos sin remisiones</h6>
                                                            <div class="table-responsive">
                                                                <table id="tablaClientesSinActividad" class="table table-sm table-striped table-bordered align-middle" style="width:100%">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Vendedor</th>
                                                                            <th>Sin actividad</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($clientesSinActividadPorVendedor as $fila): ?>
                                                                            <tr>
                                                                                <td><?= htmlspecialchars($fila['vendedor'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                                <td><?= number_format((float) $fila['clientes_sin_actividad'], 0) ?></td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div><!--End Card Body-->
                            </div><!--End Card-->
                        </div><!-- /.row (main row) -->
                    <?php } ?>
                </div><!--end::Container-->
            </div><!--end::App Content-->
        </main><!--end::App Main-->
        <!--begin::Footer-->
        <?php include("../app/layout/footer.php"); ?>
        <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <div class="modal fade" id="modalCredencialCliente" tabindex="-1" aria-labelledby="modalCredencialClienteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCredencialClienteLabel">Credencial del cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="credencial-cliente-preview" id="credencialCliente">
                        <div class="credencial-cliente-tarjeta">
                            <div class="credencial-cliente-inner">
                                <div>
                                    <img class="credencial-cliente-logo" src="../app/img/marca/logo_concretos_americas.png" alt="Concretos Americas">
                                    <div class="credencial-cliente-titulo">Aliado en Obra</div>
                                    <div class="credencial-cliente-nombre" id="credencialClienteNombre">Cliente</div>
                                    <div class="credencial-cliente-dato">Telefono: <strong id="credencialClienteTelefono"></strong></div>
                                    <div class="credencial-cliente-dato">ID: <strong id="credencialClienteId"></strong></div>
                                </div>
                                <div class="credencial-cliente-qr" id="credencialClienteQr"></div>
                            </div>
                        </div>
                        <div class="credencial-cliente-pie">Centro de Canje: <?php echo htmlspecialchars($configuracionCentroCanje['direccion']); ?><br><img class="credencial-cliente-pie-icono" src="../app/img/iconos/telefono.svg" alt="Telefono"><img class="credencial-cliente-pie-icono" src="../app/img/iconos/whatsapp.svg" alt="WhatsApp"> <?php echo htmlspecialchars($configuracionCentroCanje['telefono']); ?> &middot; <?php echo htmlspecialchars($configuracionCentroCanje['nombre_centro']); ?></div>
                    </div>
                    <div class="mt-3">
                        <div class="small text-muted mb-1">Contenido del QR</div>
                        <div class="credencial-cliente-texto-qr" id="credencialClienteTextoQr"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-outline-primary" id="btnDescargarQrCliente">
                        <i class="bi bi-download"></i> Descargar QR
                    </button>
                    <button type="button" class="btn btn-success" id="btnEnviarWhatsappCliente">
                        <i class="bi bi-whatsapp"></i> Enviar por WhatsApp
                    </button>
                    <button type="button" class="btn btn-primary" id="btnImprimirCredencialCliente">
                        <i class="bi bi-printer"></i> Imprimir credencial
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php include("../app/layout/footer_links.php"); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
        const configuracionCentroCanje = <?php echo json_encode([
            'nombreCentro' => $configuracionCentroCanje['nombre_centro'],
            'direccion' => $configuracionCentroCanje['direccion'],
            'telefono' => $configuracionCentroCanje['telefono'],
        ], JSON_UNESCAPED_UNICODE); ?>;

        $(document).ready(function() {
            if ($('#tablaClientes').length) {
                $('#tablaClientes').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                    }
                });
            }

            if ($('#tablaDashboardVendedores').length) {
                $('#tablaDashboardVendedores').DataTable({
                    pageLength: 25,
                    order: [[7, 'desc']],
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                    }
                });
            }

            if ($('#tablaClientesSinActividad').length) {
                $('#tablaClientesSinActividad').DataTable({
                    paging: false,
                    searching: false,
                    info: false,
                    order: [[1, 'desc']],
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                    }
                });
            }

            document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', function() {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                });
            });
        });

        const correoInput = document.getElementById('correo');
        const sinCorreoInput = document.getElementById('sin_correo');

        if (correoInput && sinCorreoInput) {
            sinCorreoInput.addEventListener('change', function() {
                if (this.checked) {
                    correoInput.value = '';
                    correoInput.disabled = true;
                    correoInput.required = false;
                    correoInput.placeholder = 'Sin correo electronico';
                } else {
                    correoInput.disabled = false;
                    correoInput.required = true;
                    correoInput.placeholder = '';
                    correoInput.focus();
                }
            });
        }

        const modalCredencialElemento = document.getElementById('modalCredencialCliente');
        const modalCredencialCliente = modalCredencialElemento ? new bootstrap.Modal(modalCredencialElemento) : null;
        const qrClienteContenedor = document.getElementById('credencialClienteQr');
        const nombreCredencial = document.getElementById('credencialClienteNombre');
        const telefonoCredencial = document.getElementById('credencialClienteTelefono');
        const idCredencial = document.getElementById('credencialClienteId');
        const textoQrCredencial = document.getElementById('credencialClienteTextoQr');
        const logoCredencial = new URL('../app/img/marca/logo_concretos_americas.png', window.location.href).href;
        const iconoTelefonoCredencial = new URL('../app/img/iconos/telefono.svg', window.location.href).href;
        const iconoWhatsappCredencial = new URL('../app/img/iconos/whatsapp.svg', window.location.href).href;
        let datosCredencialCliente = null;

        function limpiarQrCliente() {
            if (qrClienteContenedor) {
                qrClienteContenedor.innerHTML = '';
            }
        }

        function escaparHtml(valor) {
            return String(valor || '').replace(/[&<>"']/g, function(caracter) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[caracter];
            });
        }

        function obtenerQrClienteComoImagen() {
            const canvas = qrClienteContenedor ? qrClienteContenedor.querySelector('canvas') : null;
            if (canvas) {
                return canvas.toDataURL('image/png');
            }

            const imagen = qrClienteContenedor ? qrClienteContenedor.querySelector('img') : null;
            return imagen ? imagen.src : '';
        }

        function crearQrCliente(textoQr) {
            limpiarQrCliente();

            if (typeof QRCode === 'undefined') {
                qrClienteContenedor.innerHTML = '<span class="small text-danger text-center">No se pudo cargar el generador QR.</span>';
                return;
            }

            new QRCode(qrClienteContenedor, {
                text: textoQr,
                width: 130,
                height: 130,
                colorDark: '#101827',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        }

        document.addEventListener('click', function(evento) {
            const boton = evento.target.closest('.btn-credencial-cliente');
            if (!boton) {
                return;
            }

            const idCliente = boton.dataset.id || '';
            const nombre = boton.dataset.nombre || 'Cliente';
            const telefono = boton.dataset.telefono || '';
            const textoQr = `CLIENTE_ID: ${idCliente} CLIENTE: ${nombre} TEL: ${telefono}`;

            datosCredencialCliente = {
                idCliente,
                nombre,
                telefono,
                textoQr
            };

            nombreCredencial.textContent = nombre;
            telefonoCredencial.textContent = telefono;
            idCredencial.textContent = idCliente;
            textoQrCredencial.textContent = textoQr;
            crearQrCliente(textoQr);

            if (modalCredencialCliente) {
                modalCredencialCliente.show();
            }
        });

        document.getElementById('btnDescargarQrCliente').addEventListener('click', function() {
            if (!datosCredencialCliente) {
                return;
            }

            const imagenQr = obtenerQrClienteComoImagen();
            if (!imagenQr) {
                Swal.fire('', 'No se pudo descargar el QR en este momento.', 'error');
                return;
            }

            const enlace = document.createElement('a');
            const telefono = datosCredencialCliente.telefono || datosCredencialCliente.idCliente || 'cliente';
            enlace.href = imagenQr;
            enlace.download = `qr_cliente_${telefono}.png`;
            enlace.click();
        });

        // ===== Envio de la credencial por WhatsApp =====
        function mensajeWhatsappCredencial(nombre) {
            return `Hola ${nombre}, te compartimos tu credencial de Cliente Recompensas de Concretos Americas. ` +
                'Guarda esta imagen y muestra el codigo QR al operador cuando llegue tu concreto.';
        }

        const imagenLogoCredencial = new Image();
        const logoCredencialCargado = new Promise(function(resolver) {
            imagenLogoCredencial.onload = function() { resolver(true); };
            imagenLogoCredencial.onerror = function() { resolver(false); };
        });
        imagenLogoCredencial.src = logoCredencial;

        function precargarIcono(url) {
            const imagen = new Image();
            const cargado = new Promise(function(resolver) {
                imagen.onload = function() { resolver(true); };
                imagen.onerror = function() { resolver(false); };
            });
            imagen.src = url;
            return { imagen: imagen, cargado: cargado };
        }

        const iconoTelefonoPrecargado = precargarIcono(iconoTelefonoCredencial);
        const iconoWhatsappPrecargado = precargarIcono(iconoWhatsappCredencial);

        function dividirTextoEnLineas(ctx, texto, anchoMax) {
            const lineas = [];
            let linea = '';
            texto.split(/\s+/).forEach(function(palabra) {
                const prueba = linea ? linea + ' ' + palabra : palabra;
                if (ctx.measureText(prueba).width > anchoMax && linea) {
                    lineas.push(linea);
                    linea = palabra;
                } else {
                    linea = prueba;
                }
            });
            if (linea) {
                lineas.push(linea);
            }
            return lineas;
        }

        // Reduce la letra hasta que el nombre completo quepa en maxLineas (nunca se corta).
        function dibujarNombreAjustado(ctx, texto, x, y, anchoMax, maxLineas) {
            let tamano = 46;
            let lineas = [];
            for (; tamano >= 26; tamano -= 4) {
                ctx.font = `800 ${tamano}px Arial, sans-serif`;
                lineas = dividirTextoEnLineas(ctx, texto, anchoMax);
                const cabeAncho = lineas.every(function(linea) {
                    return ctx.measureText(linea).width <= anchoMax;
                });
                if (lineas.length <= maxLineas && cabeAncho) {
                    break;
                }
            }
            const alturaLinea = Math.round(tamano * 1.15);
            lineas.forEach(function(linea, indice) {
                ctx.fillText(linea, x, y + indice * alturaLinea);
            });
            return lineas.length * alturaLinea;
        }

        function crearQrAltaResolucion(textoQr, tamanoObjetivo) {
            const opciones = function(tamano) {
                return {
                    text: textoQr,
                    width: tamano,
                    height: tamano,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                };
            };

            // El tamano final es multiplo entero del numero de modulos para que cada
            // cuadro salga nitido (sin bordes borrosos) y el celular lo lea mejor.
            const sonda = new QRCode(document.createElement('div'), opciones(tamanoObjetivo));
            const modulos = sonda._oQRCode.getModuleCount();
            const tamanoEntero = modulos * Math.max(1, Math.floor(tamanoObjetivo / modulos));

            const temporal = document.createElement('div');
            new QRCode(temporal, opciones(tamanoEntero));
            return temporal.querySelector('canvas');
        }

        async function crearImagenCredencial(datos) {
            const ancho = 1200;
            const altoTarjeta = 756;
            const altoPie = 96;
            const alto = altoTarjeta + altoPie;
            const lienzo = document.createElement('canvas');
            lienzo.width = ancho;
            lienzo.height = alto;
            const ctx = lienzo.getContext('2d');

            const fondo = ctx.createLinearGradient(0, 0, ancho, altoTarjeta);
            fondo.addColorStop(0, '#ffffff');
            fondo.addColorStop(0.58, '#f7f9fc');
            fondo.addColorStop(1, '#eef4fb');
            ctx.fillStyle = fondo;
            ctx.fillRect(0, 0, ancho, altoTarjeta);

            ctx.fillStyle = '#c41230';
            ctx.fillRect(0, 0, ancho * 0.4, 44);
            ctx.fillStyle = '#174a94';
            ctx.fillRect(ancho * 0.4, 0, ancho * 0.6, 44);
            ctx.strokeStyle = '#d7dde8';
            ctx.lineWidth = 4;
            ctx.strokeRect(2, 2, ancho - 4, altoTarjeta - 4);

            // El QR ocupa la mitad derecha de la tarjeta, lo mas grande posible.
            const margen = 60;
            const relleno = 44;
            const qr = crearQrAltaResolucion(datos.textoQr, 560);
            const lado = qr ? qr.width + relleno * 2 : 0;
            const cajaX = ancho - margen - lado;
            const anchoTexto = (lado ? cajaX : ancho) - margen - 30;

            if (await logoCredencialCargado && imagenLogoCredencial.naturalWidth) {
                const escala = Math.min(430 / imagenLogoCredencial.naturalWidth, 200 / imagenLogoCredencial.naturalHeight);
                ctx.drawImage(imagenLogoCredencial, margen, 62,
                    imagenLogoCredencial.naturalWidth * escala, imagenLogoCredencial.naturalHeight * escala);
            }

            ctx.textBaseline = 'alphabetic';
            ctx.fillStyle = '#c41230';
            ctx.font = '800 28px Arial, sans-serif';
            ctx.fillText('ALIADO EN OBRA', margen, 300);

            ctx.fillStyle = '#172033';
            let y = 362;
            y += dibujarNombreAjustado(ctx, String(datos.nombre || '').toUpperCase(), margen, y, anchoTexto, 3) + 14;

            const dibujarDato = function(etiqueta, valor) {
                ctx.font = '34px Arial, sans-serif';
                ctx.fillStyle = '#42526a';
                ctx.fillText(etiqueta + ' ', margen, y);
                const desplazamiento = ctx.measureText(etiqueta + ' ').width;
                ctx.font = '800 34px Arial, sans-serif';
                ctx.fillStyle = '#172033';
                ctx.fillText(valor, margen + desplazamiento, y);
                y += 50;
            };
            dibujarDato('Telefono:', datos.telefono);
            dibujarDato('ID:', datos.idCliente);

            if (qr) {
                const cajaY = 44 + (altoTarjeta - 44 - lado) / 2;
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(cajaX, cajaY, lado, lado);
                ctx.strokeStyle = '#d7dde8';
                ctx.lineWidth = 3;
                ctx.strokeRect(cajaX, cajaY, lado, lado);
                ctx.imageSmoothingEnabled = false;
                ctx.drawImage(qr, cajaX + relleno, cajaY + relleno, qr.width, qr.height);
            }

            // Pie con el centro de canje, debajo de la tarjeta.
            ctx.fillStyle = '#f1f4f9';
            ctx.fillRect(0, altoTarjeta, ancho, altoPie);
            ctx.strokeStyle = '#d7dde8';
            ctx.lineWidth = 2;
            ctx.strokeRect(2, altoTarjeta, ancho - 4, altoPie - 2);
            ctx.fillStyle = '#42526a';
            ctx.font = '600 24px Arial, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('Centro de Canje: ' + configuracionCentroCanje.direccion, ancho / 2, altoTarjeta + altoPie / 2 - 10);

            const textoContacto = configuracionCentroCanje.telefono + '  ·  ' + configuracionCentroCanje.nombreCentro;
            const anchoTextoContacto = ctx.measureText(textoContacto).width;
            const yContacto = altoTarjeta + altoPie / 2 + 22;
            const ladoIcono = 22;
            const espacioEntreIconos = 5;
            const espacioIconoTexto = 8;

            await Promise.all([iconoTelefonoPrecargado.cargado, iconoWhatsappPrecargado.cargado]);
            const hayIconos = iconoTelefonoPrecargado.imagen.naturalWidth && iconoWhatsappPrecargado.imagen.naturalWidth;
            const anchoIconos = hayIconos ? (ladoIcono * 2 + espacioEntreIconos + espacioIconoTexto) : 0;
            const inicioX = ancho / 2 - (anchoIconos + anchoTextoContacto) / 2;

            if (hayIconos) {
                ctx.imageSmoothingEnabled = true;
                ctx.drawImage(iconoTelefonoPrecargado.imagen, inicioX, yContacto - ladoIcono + 7, ladoIcono, ladoIcono);
                ctx.drawImage(iconoWhatsappPrecargado.imagen, inicioX + ladoIcono + espacioEntreIconos, yContacto - ladoIcono + 7, ladoIcono, ladoIcono);
            }

            ctx.textAlign = 'left';
            ctx.fillText(textoContacto, inicioX + anchoIconos, yContacto);

            return new Promise(function(resolver) {
                lienzo.toBlob(resolver, 'image/png');
            });
        }

        document.getElementById('btnEnviarWhatsappCliente').addEventListener('click', async function() {
            if (!datosCredencialCliente) {
                return;
            }

            const boton = this;
            const datos = datosCredencialCliente;
            const digitos = String(datos.telefono || '').replace(/\D/g, '');
            const telefonoWa = digitos.length === 10 ? '52' + digitos : digitos;
            const mensaje = mensajeWhatsappCredencial(datos.nombre);
            const urlWa = `https://wa.me/${telefonoWa}?text=${encodeURIComponent(mensaje)}`;
            const esMovil = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

            boton.disabled = true;
            // En escritorio se abre el chat antes de generar la imagen para que el navegador no bloquee la ventana.
            const ventanaWa = esMovil ? null : window.open('about:blank', '_blank');

            try {
                const blob = await crearImagenCredencial(datos);
                if (!blob) {
                    throw new Error('No se genero la imagen');
                }

                const nombreArchivo = `credencial_${digitos || datos.idCliente}.png`;
                const archivo = new File([blob], nombreArchivo, {
                    type: 'image/png'
                });

                if (esMovil && navigator.canShare && navigator.canShare({
                        files: [archivo]
                    })) {
                    try {
                        await navigator.share({
                            files: [archivo],
                            text: mensaje
                        });
                    } catch (errorCompartir) {
                        if (errorCompartir.name !== 'AbortError') {
                            throw errorCompartir;
                        }
                    }
                    return;
                }

                const enlace = document.createElement('a');
                enlace.href = URL.createObjectURL(blob);
                enlace.download = nombreArchivo;
                enlace.click();
                setTimeout(function() {
                    URL.revokeObjectURL(enlace.href);
                }, 10000);

                const ventanaChat = ventanaWa || window.open(urlWa, '_blank');
                if (ventanaWa) {
                    ventanaWa.location.href = urlWa;
                }
                Swal.fire({
                    icon: 'info',
                    title: 'Credencial descargada',
                    html: ventanaChat ?
                        'Adjunta la imagen descargada en el chat de WhatsApp que se abrio.' : `Adjunta la imagen descargada en el chat: <a href="${urlWa}" target="_blank" rel="noopener">abrir WhatsApp</a>.`
                });
            } catch (error) {
                if (ventanaWa) {
                    ventanaWa.close();
                }
                Swal.fire('', 'No se pudo preparar la credencial para WhatsApp.', 'error');
            } finally {
                boton.disabled = false;
            }
        });

        document.getElementById('btnImprimirCredencialCliente').addEventListener('click', function() {
            if (!datosCredencialCliente) {
                return;
            }

            // Para imprimir se genera el QR aparte, en alta resolucion: el que se ve
            // en pantalla es chico (~130px) y una impresora de PVC lo saca pixeleado
            // si se estira a los 27mm de la tarjeta.
            const qrImpresion = crearQrAltaResolucion(datosCredencialCliente.textoQr, 640);
            const imagenQr = qrImpresion ? qrImpresion.toDataURL('image/png') : '';
            if (!imagenQr) {
                Swal.fire('', 'No se pudo preparar la credencial para impresion.', 'error');
                return;
            }

            const ventana = window.open('', '_blank', 'width=900,height=650');
            if (!ventana) {
                Swal.fire('', 'Permite las ventanas emergentes para imprimir la credencial.', 'warning');
                return;
            }

            ventana.document.write(`<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Credencial cliente</title>
    <style>
        @page { size: 85.6mm 54mm; margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            width: 85.6mm;
            height: 54mm;
            font-family: Arial, sans-serif;
            color: #172033;
            background: #ffffff;
        }
        .credencial {
            width: 85.6mm;
            height: 54mm;
            border: 0.3mm solid #d7dde8;
            border-radius: 2mm;
            overflow: hidden;
            background: linear-gradient(135deg, #ffffff 0%, #f7f9fc 58%, #eef4fb 100%);
            position: relative;
        }
        .barra {
            height: 3.2mm;
            background: linear-gradient(90deg, #c41230 0%, #c41230 40%, #174a94 40%, #174a94 100%);
        }
        .contenido {
            height: calc(54mm - 3.2mm - 7.3mm);
            padding: 2.6mm 5.2mm 1.4mm;
            display: grid;
            grid-template-columns: 1fr 30mm;
            gap: 3mm;
            align-items: start;
        }
        .pie {
            height: 7.3mm;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            line-height: 1.25;
            font-size: 6.5pt;
            font-weight: 600;
            color: #172033;
            background: #f1f4f9;
            border-top: 0.2mm solid #d7dde8;
            padding: 0 2mm;
        }
        .pie-icono {
            width: 2.6mm;
            height: 2.6mm;
            vertical-align: -0.3mm;
            margin: 0 0.4mm;
        }
        .logo {
            width: 30mm;
            height: 14.5mm;
            object-fit: contain;
            display: block;
            margin-bottom: 1mm;
        }
        .titulo {
            font-size: 7pt;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #c41230;
            text-transform: uppercase;
            margin-bottom: 0.8mm;
        }
        .nombre {
            font-size: 11.5pt;
            font-weight: 800;
            line-height: 1.05;
            margin-bottom: 1.4mm;
            word-break: break-word;
        }
        .dato {
            font-size: 8pt;
            color: #42526a;
            margin-bottom: 0.2mm;
        }
        .dato strong { color: #172033; }
        .qr {
            width: 30mm;
            height: 30mm;
            padding: 1.5mm;
            border: 0.3mm solid #e0e6ef;
            border-radius: 1.6mm;
            background: #ffffff;
            align-self: center;
        }
        .qr img {
            width: 27mm;
            height: 27mm;
            display: block;
            image-rendering: pixelated;
        }
    </style>
</head>
<body>
    <div class="credencial">
        <div class="barra"></div>
        <div class="contenido">
            <div>
                <img class="logo" src="${logoCredencial}" alt="Concretos Americas">
                <div class="titulo">Aliado en Obra</div>
                <div class="nombre">${escaparHtml(datosCredencialCliente.nombre)}</div>
                <div class="dato">Telefono: <strong>${escaparHtml(datosCredencialCliente.telefono)}</strong></div>
                <div class="dato">ID: <strong>${escaparHtml(datosCredencialCliente.idCliente)}</strong></div>
            </div>
            <div class="qr"><img src="${imagenQr}" alt="QR cliente"></div>
        </div>
        <div class="pie"><span>Centro de Canje: ${escaparHtml(configuracionCentroCanje.direccion)}<br><img class="pie-icono" src="${iconoTelefonoCredencial}" alt="Telefono"><img class="pie-icono" src="${iconoWhatsappCredencial}" alt="WhatsApp"> ${escaparHtml(configuracionCentroCanje.telefono)} &middot; ${escaparHtml(configuracionCentroCanje.nombreCentro)}</span></div>
    </div>
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 250);
        });
    <\/script>
</body>
</html>`);
            ventana.document.close();
            ventana.focus();
        });
    </script>
</body>
<!--end::Body-->

</html>
