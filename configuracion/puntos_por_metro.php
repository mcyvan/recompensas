<?php
include('../app/config/config.php');
include('../app/functions/auth.php');
verificarSesion();

if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    exit('No tienes permiso para acceder a esta configuracion.');
}

if (empty($_SESSION['csrf_configuracion_puntos'])) {
    $_SESSION['csrf_configuracion_puntos'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->query(
    "SELECT puntos_por_m3, fecha_actualizacion
     FROM tb_configuracion_puntos
     WHERE id_configuracion = 1
     LIMIT 1"
);
$configuracion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$configuracion) {
    http_response_code(500);
    exit('Falta ejecutar la migracion de configuracion de puntos.');
}

$mensajeCorrecto = $_SESSION['mensaje_configuracion_puntos_correcto'] ?? null;
$mensajeError = $_SESSION['mensaje_configuracion_puntos_error'] ?? null;
unset($_SESSION['mensaje_configuracion_puntos_correcto'], $_SESSION['mensaje_configuracion_puntos_error']);
?>
<!doctype html>
<html lang="es">
<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Configuracion - Puntos por metro cubico</title>
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
                    <h3 class="mb-0">Puntos por metro cubico</h3>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="card card-primary card-outline mb-4 col-md-6">
                            <div class="card-header">
                                <div class="card-title"><b>Configuracion de acumulacion</b></div>
                            </div>

                            <form action="../controller/controller_actualizar_puntos_metro.php" method="post">
                                <div class="card-body">
                                    <?php if ($mensajeCorrecto): ?>
                                        <div class="alert alert-success"><?php echo htmlspecialchars($mensajeCorrecto); ?></div>
                                    <?php endif; ?>
                                    <?php if ($mensajeError): ?>
                                        <div class="alert alert-danger"><?php echo htmlspecialchars($mensajeError); ?></div>
                                    <?php endif; ?>

                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_configuracion_puntos']); ?>">

                                    <label for="puntos_por_m3" class="form-label"><b>Puntos otorgados por cada m&sup3;</b></label>
                                    <div class="input-group">
                                        <input
                                            type="number"
                                            class="form-control"
                                            id="puntos_por_m3"
                                            name="puntos_por_m3"
                                            min="0.01"
                                            max="10000"
                                            step="0.01"
                                            value="<?php echo htmlspecialchars($configuracion['puntos_por_m3']); ?>"
                                            required>
                                        <span class="input-group-text">puntos / m&sup3;</span>
                                    </div>
                                    <div class="form-text">
                                        Este valor se aplicara solamente a las remisiones que se finalicen despues del cambio.
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
