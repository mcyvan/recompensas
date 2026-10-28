<?php
include('../app/config/config.php');
include('../app/functions/auth.php');
include('../app/functions/remisiones.php');
verificarSesion();

if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    exit('No tienes permiso para acceder a esta configuracion.');
}

if (empty($_SESSION['csrf_configuracion_modulos'])) {
    $_SESSION['csrf_configuracion_modulos'] = bin2hex(random_bytes(32));
}

$habilitado = remisionesManualesDosificadorHabilitadas($pdo);
$stmt = $pdo->prepare(
    "SELECT fecha_actualizacion
     FROM tb_configuracion_modulos
     WHERE clave = 'remisiones_manuales_dosificador'
     LIMIT 1"
);
$stmt->execute();
$fechaActualizacion = $stmt->fetchColumn();

$mensajeCorrecto = $_SESSION['mensaje_configuracion_modulos_correcto'] ?? null;
$mensajeError = $_SESSION['mensaje_configuracion_modulos_error'] ?? null;
unset($_SESSION['mensaje_configuracion_modulos_correcto'], $_SESSION['mensaje_configuracion_modulos_error']);
?>
<!doctype html>
<html lang="es">
<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Configuracion - Acceso del dosificador</title>
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <div class="app-wrapper">
        <nav class="app-header navbar-expand bg-body">
            <div class="container-fluid"><?php include('../app/layout/navbar.php'); ?></div>
        </nav>
        <?php include('../app/layout/menu.php'); ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid"><h3 class="mb-0">Acceso del dosificador</h3></div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="card card-primary card-outline mb-4 col-md-7">
                            <div class="card-header">
                                <div class="card-title"><b>Remisiones manuales</b></div>
                            </div>
                            <form action="../controller/controller_actualizar_modulos.php" method="post">
                                <div class="card-body">
                                    <?php if ($mensajeCorrecto): ?>
                                        <div class="alert alert-success"><?= htmlspecialchars($mensajeCorrecto) ?></div>
                                    <?php endif; ?>
                                    <?php if ($mensajeError): ?>
                                        <div class="alert alert-danger"><?= htmlspecialchars($mensajeError) ?></div>
                                    <?php endif; ?>

                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_configuracion_modulos']) ?>">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="remisionesManualesDosificador"
                                            name="remisiones_manuales_dosificador" value="1"
                                            <?= $habilitado ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="remisionesManualesDosificador">
                                            <b>Permitir remisiones manuales al dosificador</b>
                                        </label>
                                    </div>
                                    <div class="form-text mt-2">
                                        Al deshabilitarlo, el dosificador pierde el acceso inmediatamente,
                                        incluso si intenta abrir directamente la liga.
                                    </div>
                                    <?php if ($fechaActualizacion): ?>
                                        <p class="text-muted mt-3 mb-0">
                                            Ultima actualizacion: <?= htmlspecialchars((string) $fechaActualizacion) ?>
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
