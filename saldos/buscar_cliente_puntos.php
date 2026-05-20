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
    SELECT nombres, apellido_p, telefono
    FROM tb_clientes
    WHERE telefono = ?
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
    SELECT SUM(puntos) AS total_puntos
    FROM tb_remisiones
    WHERE telefono = ?
    AND estatus = 'FINALIZADO'
");

$stmtPuntos->execute([$telefono]);

$puntos = $stmtPuntos->fetch(PDO::FETCH_ASSOC);

$stmtHistorial = $pdo->prepare("
    SELECT
        folio_remision,
        volumen,
        minutos_colado,
        puntos,
        DATE_FORMAT(hora_fin, '%d/%m/%Y') AS fecha
    FROM tb_remisiones
    WHERE telefono = ?
    AND estatus = 'FINALIZADO'
    ORDER BY hora_fin DESC
    LIMIT 10
");

$stmtHistorial->execute([$telefono]);

$historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'nombre' => $cliente['nombres'],
    'apellido_p' => $cliente['apellido_p'],
    'puntos' => $puntos['total_puntos'] ?? 0,
    'historial' => $historial,
]);
