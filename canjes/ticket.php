<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/canjes.php');
require_once('../app/functions/configuracion_canje.php');

verificarSesion();
verificarPermisoCanjes();

$idCanje = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$canje = $idCanje ? obtenerCanjeParaTicket($pdo, $idCanje) : null;

if (!$canje) {
    http_response_code(404);
    exit('El canje no existe.');
}

$centro = obtenerConfiguracionCentroCanje($pdo);
$imprimirAlAbrir = ($_GET['imprimir'] ?? '') === '1';
$cancelado = $canje['estatus'] === 'CANCELADO';
$e = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
$puntos = static fn($valor) => number_format((float) $valor, 2);
$copias = ['Copia del cliente', 'Copia del Centro de Canje'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de canje <?= $e($canje['folio']) ?></title>
    <style>
        @page { size: letter; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9ecef; font-family: Arial, Helvetica, sans-serif; color: #172033; font-size: 12px; }
        .barra-pantalla { text-align: center; padding: 12px; }
        .barra-pantalla button, .barra-pantalla a {
            font-size: 14px; padding: 8px 16px; border-radius: 6px; border: 1px solid #174a94;
            background: #174a94; color: #fff; cursor: pointer; text-decoration: none; margin: 0 4px;
        }
        .barra-pantalla a { background: #fff; color: #174a94; }
        .hoja { width: 195mm; margin: 0 auto 24px; background: #fff; padding: 0; }

        .recibo { position: relative; border: 1px solid #9aa5b5; border-radius: 4px; padding: 4.5mm 6mm; margin-bottom: 3mm; page-break-inside: avoid; break-inside: avoid; }
        .encabezado { display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 2px solid #174a94; padding-bottom: 3mm; margin-bottom: 3mm; }
        .encabezado img { height: 16mm; width: auto; }
        .encabezado .titulo { text-align: center; flex: 1; }
        .titulo h1 { margin: 0; font-size: 17px; letter-spacing: .5px; color: #174a94; }
        .titulo .programa { font-size: 11px; color: #c41230; font-weight: bold; text-transform: uppercase; letter-spacing: .08em; }
        .folio-caja { text-align: right; font-size: 11px; line-height: 1.5; }
        .folio-caja b { font-size: 13px; }
        .etiqueta-copia { display: inline-block; font-size: 10px; font-weight: bold; text-transform: uppercase; border: 1px solid #172033; padding: 1px 6px; border-radius: 3px; margin-bottom: 2px; }

        .datos { display: grid; grid-template-columns: 1fr 1fr; gap: 2px 18px; margin-bottom: 3mm; }
        .datos div span { color: #5b6b82; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
        th { background: #eef2f8; text-align: left; font-size: 11px; padding: 4px 6px; border: 1px solid #c9d2df; }
        td { padding: 3px 6px; border: 1px solid #c9d2df; vertical-align: top; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        .cod { color: #5b6b82; white-space: nowrap; }

        .totales { margin-left: auto; width: 78mm; margin-bottom: 2mm; }
        .totales div { display: flex; justify-content: space-between; padding: 2px 0; }
        .totales .total { border-top: 2px solid #172033; border-bottom: 1px solid #172033; font-weight: bold; font-size: 14px; padding: 4px 0; margin: 2px 0; }

        .firmas { display: flex; gap: 18mm; margin-top: 9mm; }
        .firmas > div { flex: 1; text-align: center; border-top: 1px solid #172033; padding-top: 2px; font-size: 11px; }
        .nota { margin-top: 3mm; font-size: 10px; color: #5b6b82; text-align: center; }
        .cancelado-banda { text-align: center; font-weight: bold; font-size: 15px; color: #b00020; border: 2px solid #b00020; padding: 3px; margin-bottom: 4mm; letter-spacing: .1em; }
        .corte { text-align: center; color: #7a8699; font-size: 10px; border-top: 1px dashed #7a8699; margin: 1mm 0 4mm; padding-top: 1mm; }

        @media print {
            body { background: #fff; }
            .barra-pantalla { display: none; }
            .hoja { width: 100%; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="barra-pantalla">
        <button type="button" onclick="window.print()">Imprimir recibo</button>
        <a href="index.php?tab=listado">Volver a canjes</a>
    </div>

    <div class="hoja">
        <?php foreach ($copias as $indice => $nombreCopia): ?>
            <?php if ($indice > 0): ?>
                <div class="corte">&#9986; &nbsp; cortar por aqui &nbsp; &#9986;</div>
            <?php endif; ?>

            <div class="recibo">
                <div class="encabezado">
                    <img src="../app/img/marca/logo_concretos_americas.png" alt="Concretos Americas">
                    <div class="titulo">
                        <h1>RECIBO DE CANJE DE PUNTOS</h1>
                        <div class="programa">Descarga y Gana</div>
                        <div style="font-size:10px;color:#5b6b82">
                            Centro de Canje <?= $e($centro['nombre_centro']) ?>
                            <?php if ($centro['direccion'] !== ''): ?> &middot; <?= $e($centro['direccion']) ?><?php endif; ?>
                            <?php if ($centro['telefono'] !== ''): ?> &middot; Tel. <?= $e($centro['telefono']) ?><?php endif; ?>
                        </div>
                    </div>
                    <div class="folio-caja">
                        <div class="etiqueta-copia"><?= $e($nombreCopia) ?></div><br>
                        Folio: <b><?= $e($canje['folio']) ?></b><br>
                        Fecha: <?= $e(date('d/m/Y H:i', strtotime($canje['fecha_canje']))) ?>
                    </div>
                </div>

                <?php if ($cancelado): ?>
                    <div class="cancelado-banda">CANJE CANCELADO</div>
                <?php endif; ?>

                <div class="datos">
                    <div><span>Cliente:</span> <b><?= $e($canje['cliente']) ?></b></div>
                    <div><span>Telefono:</span> <?= $e($canje['telefono']) ?></div>
                    <div><span>Venta / remision:</span> <b><?= $e($canje['documento_folio'] ?: '-') ?></b></div>
                    <div><span>Atendio:</span> <?= $e($canje['atendio'] ?? '-') ?></div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th class="num" style="width:12mm">Cant.</th>
                            <th style="width:30mm">Codigo</th>
                            <th>Premio</th>
                            <th class="num" style="width:24mm">Puntos c/u</th>
                            <th class="num" style="width:26mm">Total puntos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($canje['detalles'] as $detalle): ?>
                            <tr>
                                <td class="num"><?= (int) $detalle['cantidad'] ?></td>
                                <td class="cod"><?= $e($detalle['codigo'] ?? '') ?></td>
                                <td><?= $e($detalle['premio']) ?></td>
                                <td class="num"><?= $puntos($detalle['puntos_unitarios']) ?></td>
                                <td class="num"><b><?= $puntos($detalle['puntos_total']) ?></b></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="totales">
                    <div class="total"><span>TOTAL DE PUNTOS</span><span><?= $puntos($canje['total_puntos']) ?></span></div>
                    <div><span>Saldo anterior</span><span><?= $puntos($canje['saldo_antes']) ?></span></div>
                    <div><span><b>Saldo actual</b></span><span><b><?= $puntos($canje['saldo_despues']) ?></b></span></div>
                </div>

                <?php if ($cancelado): ?>
                    <div class="nota" style="text-align:left;color:#172033;font-size:11px">
                        <b>Cancelado el:</b> <?= $canje['fecha_cancelacion'] ? $e(date('d/m/Y H:i', strtotime($canje['fecha_cancelacion']))) : '-' ?>
                        <?php if (!empty($canje['motivo_cancelacion'])): ?> &middot; <b>Motivo:</b> <?= $e($canje['motivo_cancelacion']) ?><?php endif; ?>
                        &middot; Los puntos y articulos fueron devueltos.
                    </div>
                <?php else: ?>
                    <div class="firmas">
                        <div>Recibi de conformidad<br><b><?= $e($canje['cliente']) ?></b></div>
                        <div>Entrego<br><b><?= $e($canje['atendio'] ?? '') ?></b></div>
                    </div>
                <?php endif; ?>

                <div class="nota">
                    <b style="font-size:12px;color:#172033">Gracias por Ser Nuestro Aliado en Obra</b><br>
                    Conserve este Recibo
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($imprimirAlAbrir): ?>
        <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
    <?php endif; ?>
</body>
</html>
