<?php
include('../app/config/config.php');
include('../app/functions/auth.php');
include('../app/functions/configuracion_canje.php');
verificarSesion();

if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    exit('No tienes permiso para acceder a esta configuracion.');
}

if (empty($_SESSION['csrf_configuracion_centro_canje'])) {
    $_SESSION['csrf_configuracion_centro_canje'] = bin2hex(random_bytes(32));
}

$configuracion = obtenerConfiguracionCentroCanje($pdo);

$mensajeCorrecto = $_SESSION['mensaje_configuracion_centro_canje_correcto'] ?? null;
$mensajeError = $_SESSION['mensaje_configuracion_centro_canje_error'] ?? null;
unset($_SESSION['mensaje_configuracion_centro_canje_correcto'], $_SESSION['mensaje_configuracion_centro_canje_error']);
?>
<!doctype html>
<html lang="es">
<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Configuracion - Centro de canje</title>
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <?php include('../app/layout/navbar.php'); ?>
            </div>
        </nav>

        <?php include('../app/layout/menu.php'); ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <h3 class="mb-0">Centro de canje</h3>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="card card-primary card-outline mb-4 col-md-6">
                            <div class="card-header">
                                <div class="card-title"><b>Datos impresos en la credencial del cliente</b></div>
                            </div>

                            <form action="../controller/controller_actualizar_centro_canje.php" method="post">
                                <div class="card-body">
                                    <?php if ($mensajeCorrecto): ?>
                                        <div class="alert alert-success"><?php echo htmlspecialchars($mensajeCorrecto); ?></div>
                                    <?php endif; ?>
                                    <?php if ($mensajeError): ?>
                                        <div class="alert alert-danger"><?php echo htmlspecialchars($mensajeError); ?></div>
                                    <?php endif; ?>

                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_configuracion_centro_canje']); ?>">

                                    <div class="mb-3">
                                        <label for="nombre_centro" class="form-label"><b>Nombre del centro de canje</b></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="nombre_centro"
                                            name="nombre_centro"
                                            maxlength="150"
                                            value="<?php echo htmlspecialchars($configuracion['nombre_centro']); ?>"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="direccion" class="form-label"><b>Direccion</b></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="direccion"
                                            name="direccion"
                                            maxlength="200"
                                            value="<?php echo htmlspecialchars($configuracion['direccion']); ?>"
                                            required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="telefono" class="form-label"><b>Telefono</b></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="telefono"
                                            name="telefono"
                                            maxlength="40"
                                            value="<?php echo htmlspecialchars($configuracion['telefono']); ?>"
                                            required>
                                    </div>

                                    <div class="form-text">
                                        Este texto aparece en el pie de la credencial del cliente: en la vista previa,
                                        la imagen que se envia por WhatsApp y la version que se manda a imprimir en PVC.
                                    </div>

                                    <?php if (!empty($configuracion['fecha_actualizacion'])): ?>
                                        <p class="text-muted mt-3 mb-0">
                                            Ultima actualizacion: <?php echo htmlspecialchars($configuracion['fecha_actualizacion']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <button class="btn btn-outline-primary" type="submit">Guardar configuracion</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include('../app/layout/footer.php'); ?>
    </div>
    <?php include('../app/layout/footer_links.php'); ?>
</body>
</html>
