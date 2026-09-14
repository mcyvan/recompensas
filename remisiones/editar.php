<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once("../app/functions/remisiones.php");

verificarSesion();
verificarPermisoRemisiones();
verificarPermisoAdministrarRemisiones();

$idRemision = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$idRemision) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_remisiones'])) {
    $_SESSION['csrf_remisiones'] = bin2hex(random_bytes(32));
}

$remision = obtenerRemision($pdo, $idRemision);
if (!$remision) {
    $_SESSION['mensaje_remision_error'] = 'La remision no existe.';
    header('Location: index.php');
    exit;
}

$operadores = obtenerOperadores($pdo);
$csrf = $_SESSION['csrf_remisiones'];
?>
<!doctype html>
<html lang="es">

<head>
    <?php include("../app/layout/head.php"); ?>
    <title>Editar remision</title>
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <?php include("../app/layout/navbar.php"); ?>
            </div>
        </nav>

        <?php include("../app/layout/menu.php"); ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Editar remision</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="card card-danger card-outline col-lg-8">
                            <div class="card-header">
                                <div class="card-title">
                                    <b><?= htmlspecialchars($remision['folio_remision'], ENT_QUOTES, 'UTF-8') ?></b>
                                </div>
                            </div>
                            <form action="../controller/controller_actualizar_remision.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id_remision" value="<?= (int) $remision['id_remision'] ?>">

                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label"><b>Folio</b></label>
                                            <input type="text" class="form-control" name="folio_remision" value="<?= htmlspecialchars($remision['folio_remision'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label"><b>Telefono</b></label>
                                            <input type="tel" class="form-control" name="telefono" value="<?= htmlspecialchars($remision['telefono'], ENT_QUOTES, 'UTF-8') ?>" maxlength="10" pattern="[0-9]{10}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label"><b>Volumen</b></label>
                                            <input type="number" class="form-control" name="volumen" min="1" max="8" step="0.5" value="<?= htmlspecialchars((string) $remision['volumen'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label"><b>Chofer</b></label>
                                            <select name="id_operador" class="form-control" required>
                                                <?php foreach ($operadores as $operador): ?>
                                                    <option value="<?= (int) $operador['id_usuario'] ?>" <?= (int) $operador['id_usuario'] === (int) $remision['id_operador'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($operador['usuario'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label"><b>Estatus</b></label>
                                            <select name="estatus" id="estatusRemision" class="form-control" required>
                                                <?php foreach (['EN PROCESO', 'FINALIZADO', 'CANCELADO'] as $estatus): ?>
                                                    <option value="<?= $estatus ?>" <?= $remision['estatus'] === $estatus ? 'selected' : '' ?>>
                                                        <?= $estatus ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label"><b>Puntos actuales</b></label>
                                            <input type="text" class="form-control" value="<?= number_format((float) ($remision['puntos'] ?? 0), 2) ?>" disabled>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label"><b>Fecha y hora inicio</b></label>
                                            <input type="datetime-local" class="form-control" name="hora_inicio" value="<?= prepararFechaInput($remision['hora_inicio']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><b>Fecha y hora fin</b></label>
                                            <input type="datetime-local" class="form-control" id="horaFin" name="hora_fin" value="<?= prepararFechaInput($remision['hora_fin']) ?>">
                                        </div>
                                    </div>

                                    <div class="alert alert-info mt-3 mb-0">
                                        Si la remision finalizada cambia de volumen, cliente o tiempo, el sistema ajustara los puntos automaticamente.
                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-outline-secondary">Regresar</a>
                                    <button type="submit" class="btn btn-outline-primary">Guardar cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include("../app/layout/footer.php"); ?>
    </div>

    <?php include("../app/layout/footer_links.php"); ?>
    <script>
        const estatusRemision = document.getElementById('estatusRemision');
        const horaFin = document.getElementById('horaFin');

        function actualizarHoraFin() {
            horaFin.required = estatusRemision.value === 'FINALIZADO';
            horaFin.disabled = estatusRemision.value === 'EN PROCESO';
            if (horaFin.disabled) {
                horaFin.value = '';
            }
        }

        estatusRemision.addEventListener('change', actualizarHoraFin);
        actualizarHoraFin();
    </script>
</body>

</html>
