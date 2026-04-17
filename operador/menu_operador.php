<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
verificarSesion();


/*======================
|| VERIFICAR SI HAY UNA REMISIÓN EN PROCESO
=======================*/

$id_usuario = $_SESSION['id_usuario_login'];

$stmt = $pdo->prepare("
    SELECT id_remision 
    FROM tb_remisiones 
    WHERE id_operador = ? 
    AND estatus = 'EN PROCESO'
    LIMIT 1
");

$stmt->execute([$id_usuario]);

$remision_activa = $stmt->fetch(PDO::FETCH_ASSOC);

if ($remision_activa) {
    header("Location: remision_en_proceso.php?id=" . $remision_activa['id_remision']);
    exit();
}


$_SESSION['clientes'] = obtenerTotalClientes($_SESSION['usuario']);
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
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Recompensas - Operador</title>
    <style>
        /* Clase para botones cuadrados medianos */
        .btn-square-md {
            width: 100px;
            /* ajusta el tamaño según lo que necesites */
            height: 100px;
            padding: 0;
            /* evita que el texto expanda el botón */
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            /* opcional, quítalo si quieres esquinas rectas */
            font-size: 16px;
        }

        /* Variante más pequeña */
        .btn-square-sm {
            width: 60px;
            height: 60px;
            padding: 0;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 14px;
        }

        /* Variante más grande */
        .btn-square-lg {
            width: 150px;
            height: 150px;
            padding: 0;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 18px;
        }
    </style>
</head>

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
    <div class="wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <!--begin::Container-->
            <div class="container-fluid">
                <?php include("../app/layout/navbar.php"); ?>
            </div>
            <!--end::Container-->
        </nav>
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2 justify-content-center">
                        <div class="col-sm-4">
                            <div class="card bg-light m-3 ">
                                <div class="card-body text-center">
                                    <div class="row">
                                        <h5 class="card-title">MENU OPERADOR</h5>
                                    </div>
                                    <div class="d-grid gap-2 col-6 mx-auto justify-content-center">
                                        <div class="row justify-content-center text-center">
                                            <a href="ingresar_tiempo_descarga.php" class="btn btn-outline-primary btn-square-lg mt-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" fill="currentColor" class="bi bi-stopwatch-fill" viewBox="0 0 16 16">
                                                    <path d="M6.5 0a.5.5 0 0 0 0 1H7v1.07A7.001 7.001 0 0 0 8 16a7 7 0 0 0 5.29-11.584l.013-.012.354-.354.353.354a.5.5 0 1 0 .707-.707l-1.414-1.415a.5.5 0 1 0-.707.707l.354.354-.354.354-.012.012A6.97 6.97 0 0 0 9 2.071V1h.5a.5.5 0 0 0 0-1zm2 5.6V9a.5.5 0 0 1-.5.5H4.5a.5.5 0 0 1 0-1h3V5.6a.5.5 0 1 1 1 0" />
                                                </svg>
                                            </a>
                                        </div>
                                        <!-- <div class="row justify-content-center text-center">
                                            <a href="#" class="btn btn-warning btn-square-lg mt-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                                                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                                                </svg>
                                            </a>
                                        </div> -->
                                    </div>
                                </div>
                            </div><!-- /.col -->
                        </div><!-- /.row -->
                        <!-- /.content-header -->
                    </div>
                    <!-- /.content-wrapper -->
                    <?php include('../app/layout/footer.php'); ?>
                </div>
                <!-- ./wrapper -->
                <?php include('../app/layout/footer_links.php'); ?>
            </div>

        </div>


</body>

</html>