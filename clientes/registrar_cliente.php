<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
require_once('../app/functions/configuracion_canje.php');
require_once('../app/functions/canjes.php');
verificarSesion();

$configuracionCentroCanje = obtenerConfiguracionCentroCanje($pdo);

$rol = $_SESSION['rol'] ?? '';
$idUsuario = (int) ($_SESSION['id_usuario_login'] ?? 0);
$esAdministracion = in_array($rol, ['ADMINISTRADOR', 'ADMINISTRACION'], true);
$esVendedor = $rol === 'VENDEDOR';
$esLogistica = $rol === 'LOGISTICA';
// ADMIN CANJE solo consulta la lista de clientes: sin acciones (editar, desactivar, credencial).
$esAdminCanje = $rol === 'ADMIN CANJE';
$mostrarAcciones = !$esAdminCanje;
$puedeAdministrarClientes = $esAdministracion || $esVendedor;
$puedeDarAltaClientes = $puedeAdministrarClientes || $esLogistica;
$puedeElegirVendedorCliente = $esAdministracion || $esLogistica;
$puedeVerClientes = $puedeAdministrarClientes || $esLogistica || $esAdminCanje;

// Como se muestra el camion revolvedor en la credencial:
//   'chico'      marca de agua tenue abajo a la izquierda, junto al QR
//   'fondo'      marca de agua grande de fondo, pasa por detras del QR
//   'encabezado' el camion toma el lugar del logo, arriba del nombre
//   'moderno'    tarjeta a color: camion sobre cielo, logo grande, cinta roja y barra azul abajo
$estiloCamionCredencial = 'moderno';
$camionEnEncabezado = $estiloCamionCredencial === 'encabezado';
$credencialModerna = $estiloCamionCredencial === 'moderno';

// Estilos de la credencial moderna (los comparte la vista previa y la ventana de impresion).
// Todo va en cqw (porcentaje del ancho de la tarjeta) para que escale igual a 640px que a 85.6mm.
$cssCredencialModerna = <<<'CSS'
.cc3-tarjeta{position:relative;width:100%;aspect-ratio:85.6/54;overflow:hidden;border-radius:0;background:radial-gradient(circle at 75% 25%,#e3e3e6 0%,#ececef 45%,#f8f9fb 100%);container-type:inline-size;font-family:"Segoe UI",Arial,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact;color:#04214a}
.cc3-tarjeta *{box-sizing:border-box}
.cc3-foto{position:absolute;left:0;top:0;width:53.2cqw;height:47.6%;object-fit:cover;-webkit-mask-image:linear-gradient(90deg,#000 86%,transparent 100%);mask-image:linear-gradient(90deg,#000 86%,transparent 100%)}
.cc3-logo{position:absolute;left:54.5cqw;top:5%;width:30cqw;filter:drop-shadow(0 0 .25cqw #fff) drop-shadow(0 0 .25cqw #fff) drop-shadow(0 .3cqw .5cqw rgba(0,0,0,.18))}
.cc3-lema{position:absolute;right:1.8cqw;top:6.3%;width:15.2cqw;text-align:center;font-family:Montserrat,"Segoe UI",Arial,sans-serif;font-size:1.75cqw;line-height:1.15;font-weight:800;letter-spacing:.02em;text-transform:uppercase;color:#05213f}
.cc3-lema::after{content:"";display:block;width:4.9cqw;height:.3cqw;background:#df131e;margin:1.2cqw auto 0;border-radius:.2cqw}
.cc3-cinta{position:absolute;left:.5cqw;top:48%;width:38.5cqw;height:4.3cqw;display:flex;align-items:center;padding-left:4cqw;background:#dd0f1e;color:#fff;font-family:Montserrat,"Segoe UI",Arial,sans-serif;font-weight:800;font-size:3.3cqw;text-transform:uppercase;white-space:nowrap;clip-path:polygon(0 0,100% 0,93% 100%,0 100%)}
.cc3-cinta-raya{position:absolute;left:37.1cqw;top:48%;width:4.05cqw;height:4.3cqw;background:#dd0f1e;clip-path:polygon(66.7% 0,100% 0,33.3% 100%,0 100%)}
.cc3-nombre{position:absolute;left:5.05cqw;top:58%;width:62cqw;font-family:Montserrat,"Segoe UI",Arial,sans-serif;font-weight:800;font-size:4.4cqw;line-height:1.08;text-transform:uppercase;color:#04214a;word-break:break-word}
.cc3-datos{position:absolute;left:5cqw;top:76%;display:flex;align-items:center}
.cc3-dato{display:flex;align-items:center;gap:1.35cqw}
.cc3-dato small{display:block;font-weight:700;font-size:1.7cqw;color:#505a64;text-transform:uppercase;line-height:1.1}
.cc3-dato b{display:block;font-family:Montserrat,"Segoe UI",Arial,sans-serif;font-size:3.5cqw;font-weight:800;line-height:1.1;color:#0e2746}
.cc3-ico{width:4.7cqw;height:4.7cqw;border-radius:50%;background:#e00918;display:flex;align-items:center;justify-content:center;flex:none}
.cc3-ico svg{width:55%;height:55%;fill:#fff}
.cc3-sep{width:.2cqw;height:5.7cqw;background:#505a64;margin:0 4cqw 0 4.9cqw}
.cc3-qr{position:absolute;right:4.65cqw;top:48.6%;width:22.7cqw;aspect-ratio:1/1.035;background:#fff;border:.35cqw solid #e9131f;border-radius:1.35cqw;padding:1.35cqw;display:flex;align-items:center;justify-content:center}
.cc3-qr img,.cc3-qr canvas{width:100%!important;height:auto!important;aspect-ratio:1/1;display:block;image-rendering:pixelated}
.cc3-pie{position:absolute;left:0;right:0;bottom:0;height:12.2%;background:#03234b;color:#fff;overflow:hidden}
.cc3-pie::after{content:"";position:absolute;right:0;top:0;width:9.5cqw;height:100%;background:linear-gradient(135deg,transparent 42%,#c4cbd8 42%,#c4cbd8 54%,transparent 54%,transparent 60%,#8f9db3 60%,#8f9db3 72%,transparent 72%)}
.cc3-pin{position:absolute;left:5.2cqw;top:50%;transform:translateY(-50%);width:3.7cqw;height:3.7cqw;fill:#e00918}
.cc3-lugar{position:absolute;left:10.6cqw;top:50%;transform:translateY(-50%);font-size:1.9cqw;line-height:1.2;white-space:nowrap}
.cc3-lugar b{display:block;font-size:2.1cqw;font-weight:700;text-transform:uppercase}
.cc3-wa{position:absolute;right:9.7cqw;top:50%;transform:translateY(-50%);display:flex;align-items:center;gap:1.1cqw;font-weight:700;font-size:2.5cqw;white-space:nowrap;z-index:1}
.cc3-wa svg{width:3.2cqw;height:3.2cqw;fill:#e00918}
CSS;

// Trazos (viewBox 24x24) de los iconos de la credencial moderna; se usan en HTML y en el canvas.
$iconosCredencialModerna = [
    'tel' => 'M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z',
    'id' => 'M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm5 3a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zm-4 9h8c0-2-1.8-3.5-4-3.5S4 15 4 17zm10-8v2h6V9zm0 4v2h6v-2z',
    'pin' => 'M12 2a7 7 0 0 0-7 7c0 5.3 7 13 7 13s7-7.7 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z',
    'wa' => 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.15l-.3-.18-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.25-.12-1.45-.72-1.7-.8s-.4-.12-.55.12-.63.8-.78.96-.3.18-.55.06a6.7 6.7 0 0 1-3.3-2.9c-.25-.43.25-.4.72-1.33a.45.45 0 0 0-.02-.43c-.06-.12-.55-1.33-.76-1.82s-.4-.4-.55-.4h-.47a.9.9 0 0 0-.66.3 2.8 2.8 0 0 0-.87 2.1 4.9 4.9 0 0 0 1 2.6 11.2 11.2 0 0 0 4.3 3.8c1.6.7 2.2.75 3 .63a2.5 2.5 0 0 0 1.65-1.17 2 2 0 0 0 .14-1.17c-.06-.1-.22-.16-.47-.28z',
];

$clientes = ($esAdministracion || $esLogistica || $esAdminCanje)
    ? obtenerClientes()
    : ($esVendedor ? obtenerClientesPorUsuario($idUsuario) : []);

$vendedores = $puedeElegirVendedorCliente ? obtenerVendedores() : [];
$saldosPuntosClientes = $puedeVerClientes ? obtenerSaldoPuntosTodosClientes($pdo) : [];

// Credenciales impresas vs. clientes activos de cada vendedor (usuario del que
// depende el cliente), para las tarjetas de arriba de la tabla.
$credencialesPorVendedor = [];
if ($esAdministracion || $esLogistica) {
    require_once('../app/functions/remisiones.php');
    $nombresVendedores = nombresVendedoresPorClave($pdo);
    foreach ($clientes as $clienteTarjeta) {
        if ((int) $clienteTarjeta['estatus'] !== 1) {
            continue;
        }
        $usuarioVendedor = (string) ($clienteTarjeta['usuario'] ?? '');
        $claveVendedor = preg_replace('/\s+/u', '', strtoupper($usuarioVendedor));
        if (!isset($credencialesPorVendedor[$usuarioVendedor])) {
            $credencialesPorVendedor[$usuarioVendedor] = [
                'vendedor' => $nombresVendedores[$claveVendedor] ?? $usuarioVendedor,
                'total' => 0,
                'impresas' => 0,
            ];
        }
        $credencialesPorVendedor[$usuarioVendedor]['total']++;
        if (!empty($clienteTarjeta['credencial_impresa_fecha'])) {
            $credencialesPorVendedor[$usuarioVendedor]['impresas']++;
        }
    }
    uasort($credencialesPorVendedor, static fn($a, $b) => $b['total'] <=> $a['total'] ?: strcmp($a['vendedor'], $b['vendedor']));
    $totalCredencialesImpresas = array_sum(array_column($credencialesPorVendedor, 'impresas'));
    $totalCredencialesClientes = array_sum(array_column($credencialesPorVendedor, 'total'));
}
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&display=swap" rel="stylesheet"> <!--cc3-->
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

        .credencial-cliente-marca {
            position: absolute;
            height: auto;
            pointer-events: none;
            user-select: none;
        }

        .credencial-cliente-marca-chico {
            left: 1.5%;
            bottom: 4%;
            width: 58%;
        }

        .credencial-cliente-marca-fondo {
            left: -2%;
            bottom: 0;
            width: 104%;
        }

        .credencial-cliente-inner {
            position: relative;
            z-index: 1;
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

        .credencial-cliente-derecha {
            justify-self: end;
            align-self: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .credencial-cliente-logo-qr {
            width: 150px;
            height: 58px;
            object-fit: contain;
            display: block;
        }

        .credencial-cliente-qr {
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

        <?php echo $cssCredencialModerna; ?>

        .credencial-cliente-preview.cc3 {
            width: min(100%, 640px);
            border: 0;
            border-radius: 0;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.22);
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
                                    <?php if ($credencialesPorVendedor): ?>
                                        <div class="row g-3 mb-3 px-2 pt-2">
                                            <?php
                                            $tarjetasCredenciales = array_merge(
                                                [['vendedor' => 'TOTAL', 'total' => $totalCredencialesClientes, 'impresas' => $totalCredencialesImpresas, 'esTotal' => true]],
                                                array_values($credencialesPorVendedor)
                                            );
                                            foreach ($tarjetasCredenciales as $tarjeta):
                                                $pctImpresas = $tarjeta['total'] > 0 ? $tarjeta['impresas'] * 100 / $tarjeta['total'] : 0;
                                                $claseTarjeta = !empty($tarjeta['esTotal']) ? '' : ($pctImpresas >= 80 ? 'success' : ($pctImpresas >= 40 ? 'warning' : 'danger'));
                                                $colorBarra = $claseTarjeta === 'success' ? 'success' : ($claseTarjeta === 'warning' ? 'warning' : ($claseTarjeta === 'danger' ? 'danger' : 'primary'));
                                            ?>
                                                <div class="col-sm-6 col-lg-4 col-xl-3">
                                                    <div class="dashboard-clientes-metric <?= $claseTarjeta ?>">
                                                        <div class="label"><b><?= htmlspecialchars($tarjeta['vendedor'], ENT_QUOTES, 'UTF-8') ?></b></div>
                                                        <div class="value"><?= number_format($tarjeta['impresas']) ?> <span class="fs-6 fw-normal text-muted">de <?= number_format($tarjeta['total']) ?> impresas</span></div>
                                                        <div class="progress mt-2" style="height:8px"><div class="progress-bar bg-<?= $colorBarra ?>" style="width:<?= min(100, max(0, $pctImpresas)) ?>%"></div></div>
                                                        <div class="label mt-1"><?= number_format($pctImpresas, 1) ?>%</div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
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
                                                    <th>Puntos</th>
                                                    <th>Credencial</th>
                                                    <?php if ($mostrarAcciones): ?>
                                                    <th>Acciones</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $cont_clientes = 0;
                                                foreach ($clientes as $cliente) {
                                                    $cont_clientes = $cont_clientes + 1;
                                                ?>
                                                    <?php
                                                    $nombreCompletoClienteFila = trim($cliente['nombres'] . ' ' . $cliente['apellido_p'] . ' ' . $cliente['apellido_m']);
                                                    $puntosCliente = $saldosPuntosClientes[(int) $cliente['id_cliente']] ?? 0;
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
                                                        <td data-order="<?php echo $puntosCliente; ?>"><?php echo number_format($puntosCliente, 2); ?></td>
                                                        <td class="celda-credencial-impresa" data-id-cliente="<?php echo (int) $cliente['id_cliente']; ?>">
                                                            <?php if (!empty($cliente['credencial_impresa_fecha'])): ?>
                                                                <span class="badge text-bg-success" title="Ultima impresion"><?php echo htmlspecialchars($cliente['credencial_impresa_fecha']); ?></span>
                                                            <?php else: ?>
                                                                <span class="badge text-bg-secondary">No impresa</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php if ($mostrarAcciones): ?>
                                                        <td>
                                                            <?php $nombreCompletoCliente = $nombreCompletoClienteFila; ?>
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
                                                        <?php endif; ?>
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
                    <?php if ($credencialModerna): ?>
                    <div class="credencial-cliente-preview cc3" id="credencialCliente">
                        <div class="cc3-tarjeta">
                            <img class="cc3-foto" src="../app/img/marca/credencial_foto.jpg" alt="">
                            <img class="cc3-logo" src="../app/img/marca/credencial_logo.png" alt="Concretos Americas">
                            <div class="cc3-lema">Construyendo<br>grandes<br>proyectos<br>juntos</div>
                            <div class="cc3-cinta">Aliado en obra</div><span class="cc3-cinta-raya"></span>
                            <div class="cc3-nombre" id="credencialClienteNombre">Cliente</div>
                            <div class="cc3-datos">
                                <div class="cc3-dato"><span class="cc3-ico"><svg viewBox="0 0 24 24"><path d="<?php echo $iconosCredencialModerna['tel']; ?>"/></svg></span><div><small>Teléfono:</small><b id="credencialClienteTelefono"></b></div></div>
                                <span class="cc3-sep"></span>
                                <div class="cc3-dato"><span class="cc3-ico"><svg viewBox="0 0 24 24"><path d="<?php echo $iconosCredencialModerna['id']; ?>"/></svg></span><div><small>ID:</small><b id="credencialClienteId"></b></div></div>
                            </div>
                            <div class="cc3-qr" id="credencialClienteQr"></div>
                            <div class="cc3-pie">
                                <svg class="cc3-pin" viewBox="0 0 24 24"><path d="<?php echo $iconosCredencialModerna['pin']; ?>"/></svg>
                                <div class="cc3-lugar"><b>Centro de canje <?php echo htmlspecialchars($configuracionCentroCanje['nombre_centro']); ?></b><?php echo htmlspecialchars($configuracionCentroCanje['direccion']); ?></div>
                                <div class="cc3-wa"><svg viewBox="0 0 24 24"><path d="<?php echo $iconosCredencialModerna['wa']; ?>"/></svg><?php echo htmlspecialchars($configuracionCentroCanje['telefono']); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="credencial-cliente-preview" id="credencialCliente">
                        <div class="credencial-cliente-tarjeta">
                            <?php if (!$camionEnEncabezado): ?>
                                <img class="credencial-cliente-marca credencial-cliente-marca-<?php echo $estiloCamionCredencial; ?>" src="../app/img/marca/camion_revolvedor.png" alt="">
                            <?php endif; ?>
                            <div class="credencial-cliente-inner">
                                <div>
                                    <img class="credencial-cliente-logo" src="../app/img/marca/<?php echo $camionEnEncabezado ? 'camion_revolvedor_logo.png' : 'logo_concretos_americas.png'; ?>" alt="Concretos Americas">
                                    <div class="credencial-cliente-titulo">Aliado en Obra</div>
                                    <div class="credencial-cliente-nombre" id="credencialClienteNombre">Cliente</div>
                                    <div class="credencial-cliente-dato">Telefono: <strong id="credencialClienteTelefono"></strong></div>
                                    <div class="credencial-cliente-dato">ID: <strong id="credencialClienteId"></strong></div>
                                </div>
                                <div class="credencial-cliente-derecha">
                                    <?php if ($camionEnEncabezado): ?>
                                        <img class="credencial-cliente-logo-qr" src="../app/img/marca/logo_concretos_americas.png" alt="Concretos Americas">
                                    <?php endif; ?>
                                    <div class="credencial-cliente-qr" id="credencialClienteQr"></div>
                                </div>
                            </div>
                        </div>
                        <div class="credencial-cliente-pie">Centro de Canje <?php echo htmlspecialchars($configuracionCentroCanje['nombre_centro']); ?><br><?php echo htmlspecialchars($configuracionCentroCanje['direccion']); ?><br><img class="credencial-cliente-pie-icono" src="../app/img/iconos/telefono.svg" alt="Telefono"><img class="credencial-cliente-pie-icono" src="../app/img/iconos/whatsapp.svg" alt="WhatsApp"> <?php echo htmlspecialchars($configuracionCentroCanje['telefono']); ?></div>
                    </div>
                    <?php endif; ?>
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
                    <?php if ($rol === 'ADMINISTRADOR'): ?>
                    <button type="button" class="btn btn-primary" id="btnImprimirCredencialCliente">
                        <i class="bi bi-printer"></i> Imprimir credencial
                    </button>
                    <?php endif; ?>
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
                    order: [[10, 'desc']],
                    columnDefs: [
                        <?php if ($mostrarAcciones): ?>{ orderable: false, targets: [12] },<?php endif; ?>

                        // La columna se ve con comas ("1,815.00"); sin esto
                        // DataTables a veces la detecta como texto y ordena
                        // alfabeticamente en vez de numericamente.
                        { type: 'num', targets: [10] }
                    ],
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
        const estiloCamionCredencial = <?php echo json_encode($estiloCamionCredencial); ?>;
        const logoArribaDelQr = estiloCamionCredencial === 'encabezado';
        // Con el camion en el encabezado, su imagen ocupa el lugar del logo (arriba del
        // nombre) y el logo pasa a ir centrado encima del QR.
        const logoCredencial = new URL('../app/img/marca/logo_concretos_americas.png', window.location.href).href;
        const camionEncabezadoCredencial = new URL('../app/img/marca/camion_revolvedor_logo.png', window.location.href).href;
        const iconoTelefonoCredencial = new URL('../app/img/iconos/telefono.svg', window.location.href).href;
        const iconoWhatsappCredencial = new URL('../app/img/iconos/whatsapp.svg', window.location.href).href;
        const marcaAguaCamionCredencial = new URL('../app/img/marca/camion_revolvedor.png', window.location.href).href;
        let datosCredencialCliente = null;

        // ===== Credencial moderna (estilo 'moderno') =====
        const credencialModerna = <?php echo json_encode($credencialModerna); ?>;
        const cssCredencialModerna = <?php echo json_encode($cssCredencialModerna); ?>;
        const iconosCredencialModerna = <?php echo json_encode($iconosCredencialModerna); ?>;
        const fotoModernoCredencial = new URL('../app/img/marca/credencial_foto.jpg', window.location.href).href;
        const logoModernoCredencial = new URL('../app/img/marca/credencial_logo.png', window.location.href).href;

        // Letra del nombre en cqw: baja cuando el nombre es largo para que nunca se corte ni choque con los datos.
        function tamanoNombreModerno(texto) {
            const largo = String(texto || '').length;
            if (largo <= 26) return 4.6;
            if (largo <= 34) return 4.0;
            if (largo <= 44) return 3.5;
            return 3.0;
        }

        function ajustarNombreModerno(elemento, texto) {
            if (credencialModerna && elemento) {
                elemento.style.fontSize = tamanoNombreModerno(texto) + 'cqw';
            }
        }

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
                width: credencialModerna ? 240 : 130,
                height: credencialModerna ? 240 : 130,
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
            ajustarNombreModerno(nombreCredencial, nombre);
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
        const marcaAguaCamionPrecargada = precargarIcono(marcaAguaCamionCredencial);
        const camionEncabezadoPrecargado = precargarIcono(camionEncabezadoCredencial);

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

        const fotoModernoPrecargada = precargarIcono(fotoModernoCredencial);
        const logoModernoPrecargado = precargarIcono(logoModernoCredencial);

        function rectanguloRedondeado(ctx, x, y, w, h, r) {
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.arcTo(x + w, y, x + w, y + h, r);
            ctx.arcTo(x + w, y + h, x, y + h, r);
            ctx.arcTo(x, y + h, x, y, r);
            ctx.arcTo(x, y, x + w, y, r);
            ctx.closePath();
        }

        // Dibuja un icono (trazo SVG de 24x24) centrado en (cx, cy) con el lado indicado.
        function dibujarIconoModerno(ctx, trazo, cx, cy, lado, color) {
            ctx.save();
            ctx.translate(cx - lado / 2, cy - lado / 2);
            ctx.scale(lado / 24, lado / 24);
            ctx.fillStyle = color;
            ctx.fill(new Path2D(trazo));
            ctx.restore();
        }

        // Version en imagen (para WhatsApp) de la credencial moderna. Las medidas son las mismas
        // que las del CSS (u = 1cqw = 1% del ancho de la tarjeta).
        async function crearImagenCredencialModerna(datos) {
            const W = 1200;
            const H = 757;
            const u = W / 100;
            const fuente = '"Segoe UI", Arial, sans-serif';
            const fuenteTitulo = 'Montserrat, "Segoe UI", Arial, sans-serif';
            const lienzo = document.createElement('canvas');
            lienzo.width = W;
            lienzo.height = H;
            const ctx = lienzo.getContext('2d');

            try {
                await Promise.all([document.fonts.load('800 40px Montserrat'), document.fonts.load('700 20px Montserrat')]);
            } catch (errorFuente) {
                // sin la fuente se usa la de respaldo
            }
            await Promise.all([fotoModernoPrecargada.cargado, logoModernoPrecargado.cargado]);

            // Tarjeta a sangre (sin esquinas redondeadas, para que no asome fondo blanco en las
            // orillas de la imagen) y fondo gris con degradado
            ctx.save();
            rectanguloRedondeado(ctx, 0, 0, W, H, 0);
            ctx.clip();
            const radio = 0.75 * Math.hypot(W, H);
            const fondo = ctx.createRadialGradient(W * 0.75, H * 0.25, 0, W * 0.75, H * 0.25, radio);
            fondo.addColorStop(0, '#e3e3e6');
            fondo.addColorStop(0.45, '#ececef');
            fondo.addColorStop(1, '#f8f9fb');
            ctx.fillStyle = fondo;
            ctx.fillRect(0, 0, W, H);

            // Foto del camion (object-fit: cover) con el borde derecho difuminado
            const foto = fotoModernoPrecargada.imagen;
            if (foto.naturalWidth) {
                const fw = 53.2 * u;
                const fh = H * 0.476;
                const capa = document.createElement('canvas');
                capa.width = Math.round(fw);
                capa.height = Math.round(fh);
                const cctx = capa.getContext('2d');
                const escala = Math.max(fw / foto.naturalWidth, fh / foto.naturalHeight);
                const dw = foto.naturalWidth * escala;
                const dh = foto.naturalHeight * escala;
                cctx.drawImage(foto, (fw - dw) / 2, (fh - dh) / 2, dw, dh);
                cctx.globalCompositeOperation = 'destination-in';
                const mascara = cctx.createLinearGradient(0, 0, fw, 0);
                mascara.addColorStop(0, '#000');
                mascara.addColorStop(0.86, '#000');
                mascara.addColorStop(1, 'rgba(0,0,0,0)');
                cctx.fillStyle = mascara;
                cctx.fillRect(0, 0, fw, fh);
                ctx.drawImage(capa, 0, 0, fw, fh);
            }

            // Logo con borde blanco
            const logo = logoModernoPrecargado.imagen;
            if (logo.naturalWidth) {
                const anchoLogo = 30 * u;
                ctx.save();
                ctx.filter = 'drop-shadow(0 0 3px #fff) drop-shadow(0 0 3px #fff)';
                ctx.drawImage(logo, 54.5 * u, H * 0.05, anchoLogo, anchoLogo * logo.naturalHeight / logo.naturalWidth);
                ctx.restore();
            }

            // Lema
            const tamanoLema = 1.75 * u;
            ctx.font = `800 ${tamanoLema}px ${fuenteTitulo}`;
            ctx.fillStyle = '#05213f';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            if ('letterSpacing' in ctx) {
                ctx.letterSpacing = (tamanoLema * 0.02) + 'px';
            }
            const lemaX = W - 1.8 * u - 7.6 * u;
            const lemaY = H * 0.063;
            ['CONSTRUYENDO', 'GRANDES', 'PROYECTOS', 'JUNTOS'].forEach(function(linea, i) {
                ctx.fillText(linea, lemaX, lemaY + i * tamanoLema * 1.15);
            });
            if ('letterSpacing' in ctx) {
                ctx.letterSpacing = '0px';
            }
            ctx.fillStyle = '#df131e';
            rectanguloRedondeado(ctx, lemaX - 2.45 * u, lemaY + 4 * tamanoLema * 1.15 + 1.2 * u, 4.9 * u, 0.3 * u, 0.2 * u);
            ctx.fill();

            // Cinta roja con su rayita
            const cintaX = 0.5 * u;
            const cintaY = H * 0.48;
            const cintaW = 38.5 * u;
            const cintaH = 4.3 * u;
            ctx.fillStyle = '#dd0f1e';
            ctx.beginPath();
            ctx.moveTo(cintaX, cintaY);
            ctx.lineTo(cintaX + cintaW, cintaY);
            ctx.lineTo(cintaX + cintaW * 0.93, cintaY + cintaH);
            ctx.lineTo(cintaX, cintaY + cintaH);
            ctx.closePath();
            ctx.fill();
            ctx.beginPath();
            ctx.moveTo(37.1 * u + 4.05 * u * 0.667, cintaY);
            ctx.lineTo(37.1 * u + 4.05 * u, cintaY);
            ctx.lineTo(37.1 * u + 4.05 * u * 0.333, cintaY + cintaH);
            ctx.lineTo(37.1 * u, cintaY + cintaH);
            ctx.closePath();
            ctx.fill();
            ctx.font = `800 ${3.3 * u}px ${fuenteTitulo}`;
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.fillText('ALIADO EN OBRA', cintaX + 4 * u, cintaY + cintaH / 2 + 1);

            // Nombre
            const tamanoNombre = tamanoNombreModerno(datos.nombre) * u;
            ctx.font = `800 ${tamanoNombre}px ${fuenteTitulo}`;
            ctx.textBaseline = 'top';
            ctx.fillStyle = '#04214a';
            dividirTextoEnLineas(ctx, String(datos.nombre || '').toUpperCase(), 62 * u).forEach(function(linea, i) {
                ctx.fillText(linea, 5.05 * u, H * 0.58 + i * tamanoNombre * 1.08);
            });

            // Telefono e ID
            const altoFila = 5.72 * u;
            const centroY = H * 0.76 + altoFila / 2;
            let cursorX = 5 * u;
            [
                { etiqueta: 'TELÉFONO:', valor: String(datos.telefono || ''), icono: iconosCredencialModerna.tel },
                { etiqueta: 'ID:', valor: String(datos.idCliente || ''), icono: iconosCredencialModerna.id }
            ].forEach(function(dato, indice) {
                if (indice > 0) {
                    ctx.fillStyle = '#505a64';
                    ctx.fillRect(cursorX, centroY - 2.85 * u, 0.2 * u, 5.7 * u);
                    cursorX += 0.2 * u + 4 * u;
                }
                ctx.fillStyle = '#e00918';
                ctx.beginPath();
                ctx.arc(cursorX + 2.35 * u, centroY, 2.35 * u, 0, Math.PI * 2);
                ctx.fill();
                dibujarIconoModerno(ctx, dato.icono, cursorX + 2.35 * u, centroY, 4.7 * u * 0.55, '#ffffff');
                const textoX = cursorX + 4.7 * u + 1.35 * u;
                ctx.textBaseline = 'top';
                ctx.font = `700 ${1.7 * u}px ${fuente}`;
                ctx.fillStyle = '#505a64';
                ctx.fillText(dato.etiqueta, textoX, centroY - altoFila / 2);
                const anchoEtiqueta = ctx.measureText(dato.etiqueta).width;
                ctx.font = `800 ${3.5 * u}px ${fuenteTitulo}`;
                ctx.fillStyle = '#0e2746';
                ctx.fillText(dato.valor, textoX, centroY - altoFila / 2 + 1.87 * u);
                cursorX = textoX + Math.max(anchoEtiqueta, ctx.measureText(dato.valor).width) + 4.9 * u;
            });

            // QR
            const cajaW = 22.7 * u;
            const cajaH = cajaW * 1.035;
            const cajaX = W - 4.65 * u - cajaW;
            const cajaY = H * 0.486;
            const borde = 0.35 * u;
            ctx.fillStyle = '#ffffff';
            rectanguloRedondeado(ctx, cajaX, cajaY, cajaW, cajaH, 1.35 * u);
            ctx.fill();
            ctx.strokeStyle = '#e9131f';
            ctx.lineWidth = borde;
            rectanguloRedondeado(ctx, cajaX + borde / 2, cajaY + borde / 2, cajaW - borde, cajaH - borde, 1.35 * u);
            ctx.stroke();
            const qr = crearQrAltaResolucion(datos.textoQr, 560);
            if (qr) {
                const relleno = borde + 1.35 * u;
                const ladoQr = cajaW - relleno * 2;
                ctx.imageSmoothingEnabled = false;
                ctx.drawImage(qr, cajaX + relleno, cajaY + (cajaH - ladoQr) / 2, ladoQr, ladoQr);
                ctx.imageSmoothingEnabled = true;
            }

            // Barra azul inferior con rayas
            const pieH = H * 0.122;
            const pieY = H - pieH;
            ctx.fillStyle = '#03234b';
            ctx.fillRect(0, pieY, W, pieH);
            ctx.save();
            const rayasX = W - 9.5 * u;
            ctx.beginPath();
            ctx.rect(rayasX, pieY, 9.5 * u, pieH);
            ctx.clip();
            const largo = (9.5 * u + pieH) * Math.SQRT1_2;
            const cxRayas = rayasX + 4.75 * u;
            const cyRayas = pieY + pieH / 2;
            const rayas = ctx.createLinearGradient(
                cxRayas - Math.SQRT1_2 * largo / 2, cyRayas - Math.SQRT1_2 * largo / 2,
                cxRayas + Math.SQRT1_2 * largo / 2, cyRayas + Math.SQRT1_2 * largo / 2);
            const sinColor = 'rgba(0,0,0,0)';
            [[0, sinColor], [0.42, sinColor], [0.42, '#c4cbd8'], [0.54, '#c4cbd8'], [0.54, sinColor], [0.6, sinColor], [0.6, '#8f9db3'], [0.72, '#8f9db3'], [0.72, sinColor], [1, sinColor]]
            .forEach(function(parada) {
                rayas.addColorStop(parada[0], parada[1]);
            });
            ctx.fillStyle = rayas;
            ctx.fillRect(rayasX, pieY, 9.5 * u, pieH);
            ctx.restore();

            const centroPie = pieY + pieH / 2;
            dibujarIconoModerno(ctx, iconosCredencialModerna.pin, 5.2 * u + 1.85 * u, centroPie, 3.7 * u, '#e00918');
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            const topLugar = centroPie - 2.4 * u;
            ctx.font = `700 ${2.1 * u}px ${fuente}`;
            ctx.fillText(('Centro de canje ' + configuracionCentroCanje.nombreCentro).toUpperCase(), 10.6 * u, topLugar + 0.15 * u);
            ctx.font = `400 ${1.9 * u}px ${fuente}`;
            ctx.fillText(configuracionCentroCanje.direccion, 10.6 * u, topLugar + 2.52 * u + 0.1 * u);

            const derechaX = W - 9.7 * u;
            ctx.font = `700 ${2.5 * u}px ${fuente}`;
            ctx.textAlign = 'right';
            ctx.textBaseline = 'middle';
            ctx.fillText(configuracionCentroCanje.telefono, derechaX, centroPie + 1);
            const anchoTel = ctx.measureText(configuracionCentroCanje.telefono).width;
            dibujarIconoModerno(ctx, iconosCredencialModerna.wa, derechaX - anchoTel - 1.1 * u - 1.6 * u, centroPie, 3.2 * u, '#e00918');
            ctx.restore();

            return new Promise(function(resolver) {
                lienzo.toBlob(resolver, 'image/png');
            });
        }

        // Pagina de impresion (85.6 x 54 mm) de la credencial moderna.
        function htmlImpresionModerna(datos, imagenQr) {
            return `<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Credencial cliente</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
<style>
@page { size: 85.6mm 54mm; margin: 0; }
html, body { margin: 0; padding: 0; background: #fff; overflow: hidden; }
body { width: 85.6mm; height: 54mm; }
${cssCredencialModerna}
.cc3-tarjeta { width: 85.6mm; height: 54mm; border-radius: 0; }
</style>
</head>
<body>
<div class="cc3-tarjeta">
    <img class="cc3-foto" src="${fotoModernoCredencial}" alt="">
    <img class="cc3-logo" src="${logoModernoCredencial}" alt="Concretos Americas">
    <div class="cc3-lema">Construyendo<br>grandes<br>proyectos<br>juntos</div>
    <div class="cc3-cinta">Aliado en obra</div><span class="cc3-cinta-raya"></span>
    <div class="cc3-nombre" style="font-size:${tamanoNombreModerno(datos.nombre)}cqw">${escaparHtml(datos.nombre)}</div>
    <div class="cc3-datos">
        <div class="cc3-dato"><span class="cc3-ico"><svg viewBox="0 0 24 24"><path d="${iconosCredencialModerna.tel}"/></svg></span><div><small>Teléfono:</small><b>${escaparHtml(datos.telefono)}</b></div></div>
        <span class="cc3-sep"></span>
        <div class="cc3-dato"><span class="cc3-ico"><svg viewBox="0 0 24 24"><path d="${iconosCredencialModerna.id}"/></svg></span><div><small>ID:</small><b>${escaparHtml(datos.idCliente)}</b></div></div>
    </div>
    <div class="cc3-qr"><img src="${imagenQr}" alt="QR cliente"></div>
    <div class="cc3-pie">
        <svg class="cc3-pin" viewBox="0 0 24 24"><path d="${iconosCredencialModerna.pin}"/></svg>
        <div class="cc3-lugar"><b>Centro de canje ${escaparHtml(configuracionCentroCanje.nombreCentro)}</b>${escaparHtml(configuracionCentroCanje.direccion)}</div>
        <div class="cc3-wa"><svg viewBox="0 0 24 24"><path d="${iconosCredencialModerna.wa}"/></svg>${escaparHtml(configuracionCentroCanje.telefono)}</div>
    </div>
</div>
<script>
    window.addEventListener('load', function() {
        const fuentes = (document.fonts && document.fonts.ready) ? document.fonts.ready : Promise.resolve();
        fuentes.then(function() {
            setTimeout(function() { window.print(); }, 250);
        });
    });
<\/script>
</body>
</html>`;
        }
        async function crearImagenCredencial(datos) {
            if (credencialModerna) {
                return crearImagenCredencialModerna(datos);
            }
            const ancho = 1200;
            const altoTarjeta = 756;
            const altoPie = 130;
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

            // Marca de agua de camion revolvedor (estilos 'chico' y 'fondo'). Se dibuja antes
            // que el texto y que la caja blanca del QR, asi que el QR queda siempre encima.
            if (estiloCamionCredencial !== 'encabezado') {
                await marcaAguaCamionPrecargada.cargado;
                if (marcaAguaCamionPrecargada.imagen.naturalWidth) {
                    const grande = estiloCamionCredencial === 'fondo';
                    const anchoMarca = grande ? 1260 : 484;
                    const altoMarca = anchoMarca * marcaAguaCamionPrecargada.imagen.naturalHeight / marcaAguaCamionPrecargada.imagen.naturalWidth;
                    ctx.drawImage(marcaAguaCamionPrecargada.imagen, grande ? -30 : 10,
                        altoTarjeta - altoMarca - (grande ? 0 : 20), anchoMarca, altoMarca);
                }
            }

            ctx.fillStyle = '#c41230';
            ctx.fillRect(0, 0, ancho * 0.4, 44);
            ctx.fillStyle = '#174a94';
            ctx.fillRect(ancho * 0.4, 0, ancho * 0.6, 44);
            ctx.strokeStyle = '#d7dde8';
            ctx.lineWidth = 4;
            ctx.strokeRect(2, 2, ancho - 4, altoTarjeta - 4);

            // El QR ocupa la mitad derecha de la tarjeta, lo mas grande posible.
            // Con el camion en el encabezado, el logo va centrado encima del QR y este baja
            // y se hace un poco mas chico para dejarle lugar.
            const altoLogoQr = 120;
            const espacioLogoQr = 18;
            const margen = 60;
            const relleno = logoArribaDelQr ? 40 : 44;
            const qr = crearQrAltaResolucion(datos.textoQr, logoArribaDelQr ? 420 : 560);
            const lado = qr ? qr.width + relleno * 2 : 0;
            const cajaX = ancho - margen - lado;
            const anchoTexto = (lado ? cajaX : ancho) - margen - 30;

            await Promise.all([logoCredencialCargado, camionEncabezadoPrecargado.cargado]);
            const imagenEncabezado = logoArribaDelQr ? camionEncabezadoPrecargado.imagen : imagenLogoCredencial;
            if (imagenEncabezado.naturalWidth) {
                const escala = Math.min(430 / imagenEncabezado.naturalWidth, 200 / imagenEncabezado.naturalHeight);
                ctx.drawImage(imagenEncabezado, margen, 62,
                    imagenEncabezado.naturalWidth * escala, imagenEncabezado.naturalHeight * escala);
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
                const alturaBloque = (logoArribaDelQr ? altoLogoQr + espacioLogoQr : 0) + lado;
                const bloqueY = 44 + (altoTarjeta - 44 - alturaBloque) / 2;
                const cajaY = bloqueY + (logoArribaDelQr ? altoLogoQr + espacioLogoQr : 0);
                if (logoArribaDelQr && imagenLogoCredencial.naturalWidth) {
                    const escalaLogo = Math.min(lado * 0.8 / imagenLogoCredencial.naturalWidth, altoLogoQr / imagenLogoCredencial.naturalHeight);
                    const anchoLogo = imagenLogoCredencial.naturalWidth * escalaLogo;
                    const altoLogo = imagenLogoCredencial.naturalHeight * escalaLogo;
                    ctx.drawImage(imagenLogoCredencial, cajaX + (lado - anchoLogo) / 2, bloqueY + (altoLogoQr - altoLogo) / 2, anchoLogo, altoLogo);
                }
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
            ctx.font = '700 24px Arial, sans-serif';
            ctx.fillStyle = '#172033';
            ctx.textAlign = 'center';
            ctx.fillText('Centro de Canje ' + configuracionCentroCanje.nombreCentro, ancho / 2, altoTarjeta + 34);

            ctx.font = '600 23px Arial, sans-serif';
            ctx.fillStyle = '#42526a';
            ctx.fillText(configuracionCentroCanje.direccion, ancho / 2, altoTarjeta + 68);

            const anchoTelefono = ctx.measureText(configuracionCentroCanje.telefono).width;
            const yTelefono = altoTarjeta + 102;
            const ladoIcono = 24;
            const espacioEntreIconos = 5;
            const espacioIconoTexto = 8;

            await Promise.all([iconoTelefonoPrecargado.cargado, iconoWhatsappPrecargado.cargado]);
            const hayIconos = iconoTelefonoPrecargado.imagen.naturalWidth && iconoWhatsappPrecargado.imagen.naturalWidth;
            const anchoIconos = hayIconos ? (ladoIcono * 2 + espacioEntreIconos + espacioIconoTexto) : 0;
            const inicioX = ancho / 2 - (anchoIconos + anchoTelefono) / 2;

            if (hayIconos) {
                ctx.imageSmoothingEnabled = true;
                ctx.drawImage(iconoTelefonoPrecargado.imagen, inicioX, yTelefono - ladoIcono + 7, ladoIcono, ladoIcono);
                ctx.drawImage(iconoWhatsappPrecargado.imagen, inicioX + ladoIcono + espacioEntreIconos, yTelefono - ladoIcono + 7, ladoIcono, ladoIcono);
            }

            ctx.fillStyle = '#172033';
            ctx.textAlign = 'left';
            ctx.fillText(configuracionCentroCanje.telefono, inicioX + anchoIconos, yTelefono);

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

        // Abre la ventana de impresion de la credencial de un cliente y
        // regresa una promesa que se resuelve cuando el usuario termina
        // (imprime o cancela) para poder encadenar varias en la cola de
        // impresion por lotes. Nunca se queda colgada: si el navegador no
        // avisa que el dialogo de impresion se cerro, hay un plazo maximo.
        function imprimirCredencialCliente(datos) {
            // Para imprimir se genera el QR aparte, en alta resolucion: el que se ve
            // en pantalla es chico (~130px) y una impresora de PVC lo saca pixeleado
            // si se estira a los 27mm de la tarjeta.
            const qrImpresion = crearQrAltaResolucion(datos.textoQr, 640);
            const imagenQr = qrImpresion ? qrImpresion.toDataURL('image/png') : '';
            if (!imagenQr) {
                Swal.fire('', 'No se pudo preparar la credencial para impresion.', 'error');
                return Promise.resolve(false);
            }

            const ventana = window.open('', '_blank', 'width=900,height=650');
            if (!ventana) {
                Swal.fire('', 'Permite las ventanas emergentes para imprimir la credencial.', 'warning');
                return Promise.resolve(false);
            }

            ventana.document.write(credencialModerna ? htmlImpresionModerna(datos, imagenQr) : `<!doctype html>
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
        .marca-agua {
            position: absolute;
            height: auto;
        }
        .marca-agua.chico {
            left: 1.5mm;
            bottom: 11.5mm;
            width: 47.5mm;
        }
        .marca-agua.fondo {
            left: 3.5mm;
            bottom: 10.5mm;
            width: 78mm;
        }
        .contenido {
            position: relative;
            height: calc(54mm - 3.2mm - 10.5mm);
            padding: 2.2mm 5.2mm 1mm;
            display: grid;
            grid-template-columns: 1fr 30mm;
            gap: 3mm;
            align-items: start;
        }
        .pie {
            height: 10.5mm;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            line-height: 1.22;
            font-size: 6pt;
            font-weight: 500;
            color: #172033;
            background: #f1f4f9;
            border-top: 0.2mm solid #d7dde8;
            padding: 0 2mm;
        }
        .pie strong {
            font-size: 6.6pt;
            font-weight: 800;
        }
        .pie-icono {
            width: 2.6mm;
            height: 2.6mm;
            vertical-align: -0.3mm;
            margin: 0 0.4mm;
        }
        .logo {
            width: 30mm;
            height: 12.5mm;
            object-fit: contain;
            display: block;
            margin-bottom: 0.6mm;
        }
        .titulo {
            font-size: 7pt;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #c41230;
            text-transform: uppercase;
            margin-bottom: 0.5mm;
        }
        .nombre {
            font-size: 11.5pt;
            font-weight: 800;
            line-height: 1.05;
            margin-bottom: 1mm;
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
        .logo.camion { object-position: left center; }
        .derecha {
            align-self: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .logo-qr {
            width: 30mm;
            height: 8.4mm;
            object-fit: contain;
            display: block;
            margin-bottom: 0.6mm;
        }
        .derecha.con-logo .qr {
            width: 27mm;
            height: 27mm;
            padding: 1.2mm;
        }
        .derecha.con-logo .qr img {
            width: 24.6mm;
            height: 24.6mm;
        }
    </style>
</head>
<body>
    <div class="credencial">
        ${estiloCamionCredencial === 'encabezado' ? '' : `<img class="marca-agua ${estiloCamionCredencial}" src="${marcaAguaCamionCredencial}" alt="">`}
        <div class="barra"></div>
        <div class="contenido">
            <div>
                <img class="logo ${logoArribaDelQr ? 'camion' : ''}" src="${logoArribaDelQr ? camionEncabezadoCredencial : logoCredencial}" alt="Concretos Americas">
                <div class="titulo">Aliado en Obra</div>
                <div class="nombre">${escaparHtml(datos.nombre)}</div>
                <div class="dato">Telefono: <strong>${escaparHtml(datos.telefono)}</strong></div>
                <div class="dato">ID: <strong>${escaparHtml(datos.idCliente)}</strong></div>
            </div>
            <div class="derecha ${logoArribaDelQr ? 'con-logo' : ''}">
                ${logoArribaDelQr ? `<img class="logo-qr" src="${logoCredencial}" alt="Concretos Americas">` : ''}
                <div class="qr"><img src="${imagenQr}" alt="QR cliente"></div>
            </div>
        </div>
        <div class="pie"><span><strong>Centro de Canje ${escaparHtml(configuracionCentroCanje.nombreCentro)}</strong><br>${escaparHtml(configuracionCentroCanje.direccion)}<br><img class="pie-icono" src="${iconoTelefonoCredencial}" alt="Telefono"><img class="pie-icono" src="${iconoWhatsappCredencial}" alt="WhatsApp"> ${escaparHtml(configuracionCentroCanje.telefono)}</span></div>
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

            return new Promise(function(resolver) {
                let resuelto = false;
                const terminar = function() {
                    if (resuelto) {
                        return;
                    }
                    resuelto = true;
                    clearInterval(intervalo);
                    clearTimeout(limiteMaximo);
                    resolver(true);
                };

                ventana.addEventListener('afterprint', terminar);
                // Respaldo por si el navegador no dispara 'afterprint' (o el
                // usuario cierra la ventana en vez de usar el dialogo).
                const intervalo = setInterval(function() {
                    if (ventana.closed) {
                        terminar();
                    }
                }, 500);
                // Respaldo final para que la cola de impresion nunca se quede
                // trabada esperando una ventana que nadie va a cerrar.
                const limiteMaximo = setTimeout(terminar, 5 * 60 * 1000);
            });
        }

        // Guarda en el servidor que esta credencial se imprimio (se
        // sobrescribe en cada reimpresion) y refresca la etiqueta en la
        // tabla si la fila esta visible.
        async function marcarCredencialImpresa(idCliente) {
            try {
                const respuesta = await fetch('../controller/controller_marcar_credencial_impresa.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id_cliente=' + encodeURIComponent(idCliente)
                });
                const datos = await respuesta.json();
                if (!datos.ok) {
                    return false;
                }

                const celda = document.querySelector('.celda-credencial-impresa[data-id-cliente="' + idCliente + '"]');
                if (celda) {
                    celda.innerHTML = '<span class="badge text-bg-success" title="Ultima impresion">' + datos.fecha + '</span>';
                }
                return true;
            } catch (error) {
                return false;
            }
        }

        document.getElementById('btnImprimirCredencialCliente')?.addEventListener('click', async function() {
            if (!datosCredencialCliente) {
                return;
            }

            const impreso = await imprimirCredencialCliente(datosCredencialCliente);
            if (impreso) {
                await marcarCredencialImpresa(datosCredencialCliente.idCliente);
            }
        });
    </script>
</body>
<!--end::Body-->

</html>
