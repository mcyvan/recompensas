<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once("../app/functions/bitacora.php");

verificarSesion();
verificarPermisoBitacora();

$filtros = filtrosBitacoraCambios($_GET);
$registros = [];
$errorTabla = false;

try {
    $registros = obtenerBitacoraCambios($pdo, $filtros);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorTabla = true;
}

$usuariosPorId = [];
foreach ($pdo->query("SELECT id_usuario, usuario FROM tb_usuarios")->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $usuariosPorId[(int) $fila['id_usuario']] = $fila['usuario'];
}
$idsClientes = [];
foreach ($registros as $registro) {
    foreach (json_decode((string) $registro['cambios'], true) ?: [] as $campo => $valores) {
        if ($campo === 'id_cliente') {
            foreach (['antes', 'despues'] as $lado) {
                if (!empty($valores[$lado])) {
                    $idsClientes[(int) $valores[$lado]] = true;
                }
            }
        }
    }
}
$clientesPorId = [];
if ($idsClientes) {
    $marcadores = implode(',', array_fill(0, count($idsClientes), '?'));
    $stmtClientes = $pdo->prepare("SELECT id_cliente, TRIM(CONCAT(nombres, ' ', apellido_p, ' ', IFNULL(apellido_m, ''))) AS nombre FROM tb_clientes WHERE id_cliente IN ($marcadores)");
    $stmtClientes->execute(array_keys($idsClientes));
    foreach ($stmtClientes->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $clientesPorId[(int) $fila['id_cliente']] = $fila['nombre'];
    }
}
$rolesPorId = [];
foreach ($pdo->query("SELECT id_rol, rol FROM tb_roles")->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $rolesPorId[(int) $fila['id_rol']] = $fila['rol'];
}
?>
<!doctype html>
<html lang="es">

<head>
    <?php include("../app/layout/head.php"); ?>
    <title>Bitacora de cambios</title>
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
                    <h3 class="mb-0">Bitacora de cambios</h3>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card mb-4">
                        <div class="card-body">
                            <form class="row g-3 align-items-end" method="get">
                                <div class="col-md-3">
                                    <label class="form-label"><b>Fecha inicial</b></label>
                                    <input class="form-control" type="date" name="fecha_inicio" value="<?= htmlspecialchars($filtros['fecha_inicio'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><b>Fecha final</b></label>
                                    <input class="form-control" type="date" name="fecha_fin" value="<?= htmlspecialchars($filtros['fecha_fin'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><b>Tipo</b></label>
                                    <select class="form-select" name="entidad">
                                        <option value="">Todos</option>
                                        <option value="CLIENTE" <?= $filtros['entidad'] === 'CLIENTE' ? 'selected' : '' ?>>Clientes</option>
                                        <option value="USUARIO" <?= $filtros['entidad'] === 'USUARIO' ? 'selected' : '' ?>>Usuarios</option>
                                        <option value="REMISION" <?= $filtros['entidad'] === 'REMISION' ? 'selected' : '' ?>>Remisiones</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><b>Quien modifico</b></label>
                                    <input class="form-control" type="text" name="autor" placeholder="Usuario" value="<?= htmlspecialchars($filtros['autor'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if ($errorTabla): ?>
                        <div class="alert alert-danger">Falta ejecutar la migracion database/2026_09_19_bitacora_cambios.sql.</div>
                    <?php else: ?>
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <b>Cambios registrados</b>
                                <span class="badge text-bg-secondary"><?= number_format(count($registros)) ?> resultados</span>
                            </div>
                            <div class="card-body table-responsive">
                                <table id="tablaBitacora" class="table table-sm table-striped table-bordered align-middle" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Modifico</th>
                                            <th>Tipo</th>
                                            <th>Registro</th>
                                            <th>Accion</th>
                                            <th>Cambios</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registros as $registro):
                                            $cambios = json_decode((string) $registro['cambios'], true) ?: [];
                                        ?>
                                            <tr>
                                                <td data-order="<?= htmlspecialchars($registro['fecha_cambio'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= date('d/m/Y H:i', strtotime($registro['fecha_cambio'])) ?>
                                                </td>
                                                <td>
                                                    <b><?= htmlspecialchars((string) $registro['usuario_autor'], ENT_QUOTES, 'UTF-8') ?></b>
                                                    <div class="text-muted small"><?= htmlspecialchars((string) $registro['rol_autor'], ENT_QUOTES, 'UTF-8') ?></div>
                                                </td>
                                                <td><?= ['CLIENTE' => 'Cliente', 'USUARIO' => 'Usuario', 'REMISION' => 'Remision'][$registro['entidad']] ?? htmlspecialchars($registro['entidad'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <?= htmlspecialchars((string) ($registro['registro_nombre'] ?? ('#' . $registro['id_registro'])), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td><?= htmlspecialchars((string) $registro['accion'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <?php foreach ($cambios as $campo => $valores): ?>
                                                        <div>
                                                            <b><?= htmlspecialchars(etiquetaCampoBitacora($registro['entidad'], (string) $campo), ENT_QUOTES, 'UTF-8') ?>:</b>
                                                            <?= htmlspecialchars(valorLegibleBitacora((string) $campo, $valores['antes'] ?? null, $usuariosPorId, $rolesPorId, $clientesPorId), ENT_QUOTES, 'UTF-8') ?>
                                                            &rarr;
                                                            <?= htmlspecialchars(valorLegibleBitacora((string) $campo, $valores['despues'] ?? null, $usuariosPorId, $rolesPorId, $clientesPorId), ENT_QUOTES, 'UTF-8') ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td><?= htmlspecialchars((string) $registro['ip'], ENT_QUOTES, 'UTF-8') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php include("../app/layout/footer.php"); ?>
    </div>

    <?php include("../app/layout/footer_links.php"); ?>
    <script>
        $(function() {
            $('#tablaBitacora').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' }
            });
        });
    </script>
</body>

</html>
