<?php

require_once("../app/config/config.php");
require_once("../app/functions/rate_limit.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido',
    ]);
    exit;
}

// Trampa para bots (campo oculto en el formulario)
if (!empty($_POST['website'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Cliente no encontrado',
    ]);
    exit;
}

if (!rateLimitPermitir('saldos_consulta', 15, 60)) {
    rateLimitRechazarJson();
}

$telefono = preg_replace('/[^0-9]/', '', $_POST['telefono'] ?? '');

if (strlen($telefono) !== 10) {
    echo json_encode([
        'success' => false,
        'message' => 'Ingresa un teléfono válido de 10 dígitos',
    ]);
    exit;
}

$stmtCliente = $pdo->prepare("
    SELECT id_cliente, nombres, apellido_p, telefono 
    FROM tb_clientes
    WHERE telefono = ? AND estatus = 1
    LIMIT 1
");

$stmtCliente->execute([$telefono]);

$cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo json_encode([
        'success' => false,
        'message' => 'Cliente no encontrado',
    ]);
    exit;
}

$stmtPuntos = $pdo->prepare("
    SELECT
        COALESCE(SUM(puntos), 0) AS total_puntos,
        MIN(
            CASE
                WHEN tipo = 'ACUMULACION'
                 AND puntos > 0
                 AND fecha_vencimiento >= CURRENT_DATE
                THEN fecha_vencimiento
                ELSE NULL
            END
        ) AS fecha_vencimiento
    FROM tb_movimientos_puntos
    WHERE id_cliente = ?
      AND (
          fecha_vencimiento IS NULL
          OR fecha_vencimiento >= CURRENT_DATE
      )
");

$stmtPuntos->execute([$cliente['id_cliente']]);

$puntos = $stmtPuntos->fetch(PDO::FETCH_ASSOC);
$fechaVencimiento = $puntos['fecha_vencimiento'] ?? null;
$fechaVencimientoTexto = null;

if ($fechaVencimiento) {
    $fechaVencimientoTexto = (new DateTimeImmutable($fechaVencimiento))
        ->format('d/m/Y');
}

// $stmtHistorial = $pdo->prepare("
//     SELECT
//         folio_remision,
//         volumen,
//         minutos_colado,
//         puntos,
//         DATE_FORMAT(hora_fin, '%d/%m/%Y') AS fecha
//     FROM tb_remisiones
//     WHERE telefono = ?
//     AND estatus = 'FINALIZADO'
//     ORDER BY hora_fin DESC
//     LIMIT 10
// ");

// $stmtHistorial->execute([$telefono]);

// $historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'nombre' => $cliente['nombres'],
    'apellido_p' => $cliente['apellido_p'],
    'puntos' => $puntos['total_puntos'] ?? 0,
    'fecha_vencimiento' => $fechaVencimiento,
    'fecha_vencimiento_texto' => $fechaVencimientoTexto,
    // 'historial' => $historial,
]);
