<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once("../app/functions/limpieza_pruebas.php");

verificarSesion();
verificarPermisoLimpiezaPruebas();

if (empty($_SESSION['csrf_limpieza_pruebas'])) {
    $_SESSION['csrf_limpieza_pruebas'] = bin2hex(random_bytes(32));
}

$mensajeCorrecto = $_SESSION['mensaje_limpieza_correcto'] ?? null;
$mensajeError = $_SESSION['mensaje_limpieza_error'] ?? null;
unset($_SESSION['mensaje_limpieza_correcto'], $_SESSION['mensaje_limpieza_error']);

$telefonosTexto = $_POST['telefonos'] ?? '';
$vista = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['accion'] ?? '') === 'vista_previa') {
    $telefonos = normalizarTelefonosLimpieza($telefonosTexto);
    $vista = obtenerVistaPreviaLimpiezaPruebas($pdo, $telefonos);
}
?>
<!doctype html>
<html lang="es">

<head>
    <?php include("../app/layout/head.php"); ?>
    <title>Limpieza de datos de prueba</title>
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <?php if ($mensajeCorrecto): ?>
        <script>
            Swal.fire({
                icon: 'success',
                text: <?= json_encode($mensajeCorrecto, JSON_UNESCAPED_UNICODE) ?>
            });
        </script>
    <?php endif; ?>
    <?php if ($mensajeError): ?>
        <script>
            Swal.fire({
                icon: 'error',
                text: <?= json_encode($mensajeError, JSON_UNESCAPED_UNICODE) ?>
            });
        </script>
    <?php endif; ?>

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
                    <h3 class="mb-0">Limpieza de datos de prueba</h3>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <div class="card card-danger card-outline">
                                <div class="card-header">
                                    <div class="card-title"><b>Seleccionar clientes ficticios</b></div>
                                </div>
                                <form method="POST" action="limpieza_pruebas.php">
                                    <input type="hidden" name="accion" value="vista_previa">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_limpieza_pruebas'], ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="card-body">
                                        <label class="form-label"><b>Telefonos</b></label>
                                        <textarea name="telefonos" class="form-control" rows="8" placeholder="Pega uno o varios telefonos, separados por espacios, comas o saltos de linea"><?= htmlspecialchars($telefonosTexto, ENT_QUOTES, 'UTF-8') ?></textarea>
                                        <div class="form-text">
                                            Solo se tomaran telefonos de 10 digitos. Primero revisa la vista previa antes de eliminar.
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Ver vista previa</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card card-warning card-outline">
                                <div class="card-header">
                                    <div class="card-title"><b>Vista previa</b></div>
                                </div>
                                <div class="card-body">
                                    <?php if (!$vista): ?>
                                        <p class="text-muted mb-0">Ingresa telefonos para ver los datos relacionados.</p>
                                    <?php elseif (!$vista['clientes']): ?>
                                        <div class="alert alert-warning mb-0">No se encontraron clientes con esos telefonos.</div>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            Esta accion eliminara clientes, remisiones, movimientos de puntos, canjes y aplicaciones relacionadas. Si hay canjes confirmados, se devolvera stock antes de borrar.
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <?php foreach ([
                                                'clientes' => 'Clientes',
                                                'remisiones' => 'Remisiones',
                                                'movimientos' => 'Movimientos',
                                                'canjes' => 'Canjes',
                                                'detalles_canje' => 'Detalles',
                                                'aplicaciones_canje' => 'Aplicaciones',
                                            ] as $llave => $label): ?>
                                                <div class="col-md-4 col-6">
                                                    <div class="border rounded p-2">
                                                        <div class="text-muted small"><?= $label ?></div>
                                                        <div class="fs-4 fw-bold"><?= number_format((int) $vista['resumen'][$llave]) ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <h6 class="fw-bold">Clientes encontrados</h6>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-striped table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Cliente</th>
                                                        <th>Telefono</th>
                                                        <th>Correo</th>
                                                        <th>Estatus</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($vista['clientes'] as $cliente): ?>
                                                        <tr>
                                                            <td><?= (int) $cliente['id_cliente'] ?></td>
                                                            <td><?= htmlspecialchars(trim($cliente['nombres'] . ' ' . $cliente['apellido_p'] . ' ' . $cliente['apellido_m']), ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td><?= htmlspecialchars($cliente['telefono'], ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td><?= htmlspecialchars($cliente['correo'], ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td><?= (int) $cliente['estatus'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <?php if ($vista['resumen']['stock_a_devolver']): ?>
                                            <h6 class="fw-bold">Stock a devolver</h6>
                                            <div class="table-responsive mb-3">
                                                <table class="table table-sm table-striped table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Premio</th>
                                                            <th>Cantidad</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($vista['resumen']['stock_a_devolver'] as $stock): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($stock['premio'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= number_format((int) $stock['cantidad']) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>

                                        <form method="POST" action="../controller/controller_limpieza_pruebas.php" id="formLimpiezaPruebas">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_limpieza_pruebas'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?php foreach ($vista['clientes'] as $cliente): ?>
                                                <input type="hidden" name="ids_clientes[]" value="<?= (int) $cliente['id_cliente'] ?>">
                                            <?php endforeach; ?>
                                            <label class="form-label"><b>Confirmacion</b></label>
                                            <input type="text" name="confirmacion" class="form-control mb-2" placeholder="Escribe ELIMINAR PRUEBAS">
                                            <button type="submit" class="btn btn-danger">Eliminar datos de prueba</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include("../app/layout/footer.php"); ?>
    </div>

    <?php include("../app/layout/footer_links.php"); ?>
    <script>
        const formLimpieza = document.getElementById('formLimpiezaPruebas');
        if (formLimpieza) {
            formLimpieza.addEventListener('submit', function(evento) {
                evento.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Eliminar datos de prueba',
                    text: 'Esta accion no se puede deshacer.',
                    showCancelButton: true,
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545'
                }).then(function(resultado) {
                    if (resultado.isConfirmed) {
                        formLimpieza.submit();
                    }
                });
            });
        }
    </script>
</body>

</html>
