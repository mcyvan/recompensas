<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/consultas.php');
require_once('../app/functions/canjes.php');
require_once('../app/functions/seguimiento.php');
verificarSesion();

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['ADMINISTRADOR', 'ADMINISTRACION', 'SEGUIMIENTO'], true)) {
    http_response_code(403);
    exit('No tienes permiso para acceder al seguimiento de clientes.');
}

if (empty($_SESSION['csrf_seguimiento'])) {
    $_SESSION['csrf_seguimiento'] = bin2hex(random_bytes(32));
}

try {
    $llamadas = obtenerResumenLlamadasClientes($pdo);
    $falloLlamadas = false;
} catch (PDOException $e) {
    error_log($e->getMessage());
    $llamadas = [];
    $falloLlamadas = true;
}

$clientes = array_values(array_filter(
    obtenerClientes(),
    static fn($cliente) => (int) $cliente['estatus'] === 1
));
$saldos = obtenerSaldoPuntosTodosClientes($pdo);

$filas = [];
foreach ($clientes as $cliente) {
    $puntos = $saldos[(int) $cliente['id_cliente']] ?? 0.0;
    $nombre = trim($cliente['nombres'] . ' ' . $cliente['apellido_p'] . ' ' . $cliente['apellido_m']);
    $digitos = preg_replace('/\D/', '', (string) $cliente['telefono']);
    $impresa = !empty($cliente['credencial_impresa_fecha']);

    $filas[] = [
        'id' => (int) $cliente['id_cliente'],
        'llamada' => $llamadas[(int) $cliente['id_cliente']] ?? null,
        'nombre' => $nombre,
        'telefono' => $digitos,
        'correo' => str_starts_with((string) $cliente['correo'], 'sin-correo-') ? '' : (string) $cliente['correo'],
        'vendedor' => (string) $cliente['usuario'],
        'puntos' => $puntos,
        'impresa' => $impresa,
        'fecha_impresa' => $impresa ? date('d/m/Y', strtotime($cliente['credencial_impresa_fecha'])) : '',
        'tel' => '+' . (strlen($digitos) === 10 ? '52' . $digitos : $digitos),
    ];
}

$totalClientes = count($filas);
$totalImpresas = count(array_filter($filas, static fn($f) => $f['impresa']));
$totalConPuntos = count(array_filter($filas, static fn($f) => $f['puntos'] > 0));
$totalLlamados = count(array_filter($filas, static fn($f) => $f['llamada'] !== null));

function etiquetaLlamada(?array $llamada): string
{
    if (!$llamada) {
        return '<span class="badge text-bg-secondary">Sin llamar</span>';
    }

    return '<span class="badge text-bg-primary">Llamado</span> <span class="small text-muted">'
        . date('d/m/Y H:i', strtotime($llamada['ultima'])) . ' &middot; '
        . htmlspecialchars($llamada['usuario'], ENT_QUOTES, 'UTF-8')
        . ($llamada['total'] > 1 ? ' (' . $llamada['total'] . ' veces)' : '')
        . '</span>';
}
?>
<!doctype html>
<html lang="es">
<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Seguimiento de clientes</title>
    <style>
        .seg-metric { border: 1px solid #dfe5ec; border-left: 5px solid #174a94; border-radius: 10px; background: #fff; padding: 12px 16px; }
        .seg-metric small { display: block; color: #64748b; }
        .seg-metric strong { font-size: 1.5rem; color: #172033; }
        .seg-telefono { font-weight: 600; white-space: nowrap; }
    </style>
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
                    <h3 class="mb-0">Seguimiento de clientes</h3>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <?php if ($falloLlamadas): ?>
                        <div class="alert alert-warning">Falta ejecutar la migracion database/2026_10_08_llamadas_clientes.sql; todavia no se pueden registrar llamadas.</div>
                    <?php endif; ?>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3"><div class="seg-metric"><small>Clientes activos</small><strong><?= number_format($totalClientes) ?></strong></div></div>
                        <div class="col-6 col-md-3"><div class="seg-metric"><small>Con puntos disponibles</small><strong><?= number_format($totalConPuntos) ?></strong></div></div>
                        <div class="col-6 col-md-3"><div class="seg-metric"><small>Tarjeta ya impresa</small><strong><?= number_format($totalImpresas) ?></strong> <span class="text-muted">de <?= number_format($totalClientes) ?></span></div></div>
                        <div class="col-6 col-md-3"><div class="seg-metric"><small>Ya llamados</small><strong id="totalLlamados"><?= number_format($totalLlamados) ?></strong> <span class="text-muted">de <?= number_format($totalClientes) ?></span></div></div>
                    </div>

                    <div class="card card-primary card-outline mb-4">
                        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                            <div class="card-title"><b>Clientes</b></div>
                            <div class="d-flex flex-wrap gap-2">
                                <select id="filtroTarjeta" class="form-select form-select-sm" style="width:auto">
                                    <option value="">Tarjeta: todas</option>
                                    <option value="Impresa">Solo con tarjeta impresa</option>
                                    <option value="Pendiente">Solo con tarjeta pendiente</option>
                                </select>
                                <select id="filtroLlamada" class="form-select form-select-sm" style="width:auto">
                                    <option value="">Llamadas: todos</option>
                                    <option value="Sin llamar">Solo sin llamar</option>
                                    <option value="Llamado">Solo ya llamados</option>
                                </select>
                                <div class="form-check form-switch align-self-center mb-0">
                                    <input class="form-check-input" type="checkbox" id="soloConPuntos">
                                    <label class="form-check-label" for="soloConPuntos">Solo con puntos</label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table id="tablaSeguimiento" class="table table-striped table-bordered align-middle" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Telefono</th>
                                            <th>Correo</th>
                                            <th>Vendedor</th>
                                            <th>Puntos</th>
                                            <th>Tarjeta</th>
                                            <th>Llamada</th>
                                            <th>Contactar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filas as $fila): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="seg-telefono"><?= htmlspecialchars($fila['telefono'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($fila['correo'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($fila['vendedor'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td data-order="<?= $fila['puntos'] ?>"><?= number_format($fila['puntos'], 2) ?></td>
                                                <td>
                                                    <?php if ($fila['impresa']): ?>
                                                        <span class="badge text-bg-success">Impresa</span>
                                                        <span class="small text-muted"><?= $fila['fecha_impresa'] ?></span>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-secondary">Pendiente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="celda-llamada"><?= etiquetaLlamada($fila['llamada']) ?></td>
                                                <td class="text-nowrap">
                                                    <a href="tel:<?= htmlspecialchars($fila['tel'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-primary btn-llamar" data-id="<?= $fila['id'] ?>" title="Llamar y registrar la llamada">
                                                        <i class="bi bi-telephone-fill"></i> Llamar
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include('../app/layout/footer.php'); ?>
    </div>
    <?php include('../app/layout/footer_links.php'); ?>
    <script>
        $(document).ready(function() {
            const tabla = $('#tablaSeguimiento').DataTable({
                order: [[4, 'desc']],
                pageLength: 25,
                columnDefs: [
                    { type: 'num', targets: [4] },
                    { orderable: false, targets: [7] }
                ],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
            });

            $('#filtroTarjeta').on('change', function() {
                tabla.column(5).search(this.value).draw();
            });
            $('#filtroLlamada').on('change', function() {
                tabla.column(6).search(this.value).draw();
            });

            const csrfToken = <?= json_encode($_SESSION['csrf_seguimiento']) ?>;
            const escapar = valor => String(valor ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
            const dos = n => String(n).padStart(2, '0');

            function etiquetaLlamada(llamada) {
                const f = new Date(llamada.ultima.replace(' ', 'T'));
                return '<span class="badge text-bg-primary">Llamado</span> <span class="small text-muted">'
                    + dos(f.getDate()) + '/' + dos(f.getMonth() + 1) + '/' + f.getFullYear() + ' ' + dos(f.getHours()) + ':' + dos(f.getMinutes())
                    + ' &middot; ' + escapar(llamada.usuario)
                    + (llamada.total > 1 ? ' (' + llamada.total + ' veces)' : '') + '</span>';
            }

            // Al dar clic en Llamar se registra la llamada (quien y cuando). El
            // enlace tel: sigue su curso: en celular marca; en computadora
            // depende de que haya una aplicacion de llamadas instalada.
            document.querySelector('#tablaSeguimiento').addEventListener('click', function(evento) {
                const boton = evento.target.closest('.btn-llamar');
                if (!boton) {
                    return;
                }

                const celda = tabla.cell($(boton).closest('tr').find('.celda-llamada'));
                const eraNuevo = celda.data().indexOf('Sin llamar') !== -1;

                fetch('../controller/controller_registrar_llamada.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id_cliente=' + encodeURIComponent(boton.dataset.id) + '&csrf_token=' + encodeURIComponent(csrfToken),
                    keepalive: true
                })
                    .then(respuesta => respuesta.json())
                    .then(datos => {
                        if (!datos.ok) {
                            throw new Error(datos.error || 'No se pudo registrar la llamada.');
                        }
                        celda.data(etiquetaLlamada(datos.llamada)).draw(false);
                        if (eraNuevo) {
                            const total = document.getElementById('totalLlamados');
                            total.textContent = (parseInt(total.textContent.replace(/,/g, ''), 10) + 1).toLocaleString('es-MX');
                        }
                    })
                    .catch(error => alert(error.message));
            });

            $.fn.dataTable.ext.search.push(function(settings, datos) {
                if (settings.nTable.id !== 'tablaSeguimiento' || !$('#soloConPuntos').is(':checked')) {
                    return true;
                }
                return parseFloat(String(datos[4]).replace(/,/g, '')) > 0;
            });
            $('#soloConPuntos').on('change', function() { tabla.draw(); });
        });
    </script>
</body>
</html>
