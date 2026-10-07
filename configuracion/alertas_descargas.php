<?php
include('../app/config/config.php');
include('../app/functions/auth.php');
verificarSesion();

if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    exit('No tienes permiso para acceder a esta configuracion.');
}

if (empty($_SESSION['csrf_alertas_descargas'])) {
    $_SESSION['csrf_alertas_descargas'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->query(
    'SELECT habilitado, umbral_minutos, correos, fecha_actualizacion
     FROM tb_configuracion_alertas WHERE id_configuracion = 1 LIMIT 1'
);
$configuracion = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$configuracion) {
    http_response_code(500);
    exit('Falta ejecutar la migracion de alertas de descargas.');
}

$mensajeCorrecto = $_SESSION['mensaje_alertas_correcto'] ?? null;
$mensajeError = $_SESSION['mensaje_alertas_error'] ?? null;
unset($_SESSION['mensaje_alertas_correcto'], $_SESSION['mensaje_alertas_error']);
?>
<!doctype html>
<html lang="es">
<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Configuracion - Alertas de descargas</title>
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid"><?php include('../app/layout/navbar.php'); ?></div>
    </nav>
    <?php include('../app/layout/menu.php'); ?>
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid"><h3 class="mb-0">Alertas de descargas</h3></div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="card card-primary card-outline mb-4 col-md-7">
                        <div class="card-header"><div class="card-title"><b>Alertas por correo</b></div></div>
                        <form action="../controller/controller_actualizar_alertas.php" method="post">
                            <div class="card-body">
                                <?php if ($mensajeCorrecto): ?>
                                    <div class="alert alert-success"><?= htmlspecialchars($mensajeCorrecto) ?></div>
                                <?php endif; ?>
                                <?php if ($mensajeError): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($mensajeError) ?></div>
                                <?php endif; ?>
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_alertas_descargas']) ?>">

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="alertasHabilitadas"
                                        name="habilitado" value="1" <?= (int) $configuracion['habilitado'] === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="alertasHabilitadas">
                                        <b>Activar alertas de descargas prolongadas</b>
                                    </label>
                                </div>

                                <label class="form-label" for="umbralMinutos"><b>Tiempo para enviar la alerta</b></label>
                                <div class="input-group mb-3">
                                    <input class="form-control" type="number" id="umbralMinutos"
                                        name="umbral_minutos" min="30" max="1440" step="5"
                                        value="<?= (int) $configuracion['umbral_minutos'] ?>" required>
                                    <span class="input-group-text">minutos</span>
                                </div>

                                <label class="form-label" for="correos"><b>Destinatarios</b></label>
                                <textarea class="form-control" id="correos" name="correos" rows="4"
                                    placeholder="supervisor@empresa.com&#10;gerencia@empresa.com"><?= htmlspecialchars((string) $configuracion['correos']) ?></textarea>
                                <div class="form-text">
                                    Escribe un correo por linea o separados por coma. Cada remision genera una sola alerta.
                                </div>

                                <?php if (!empty($configuracion['fecha_actualizacion'])): ?>
                                    <p class="text-muted mt-3 mb-0">
                                        Ultima actualizacion: <?= htmlspecialchars((string) $configuracion['fecha_actualizacion']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer d-flex gap-2">
                                <button class="btn btn-outline-primary" type="submit" name="accion" value="guardar">Guardar</button>
                                <button class="btn btn-outline-success" type="submit" name="accion" value="probar">Guardar y enviar prueba</button>
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
