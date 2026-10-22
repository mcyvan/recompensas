<?php
require_once("../app/config/config.php");
require_once("../app/functions/remisiones.php");
header('Content-Type: application/json');

$remision = normalizarFolioRemisionOperador($_POST['remision'] ?? '');

if (!folioRemisionOperadorValido($remision)) {
    echo json_encode([
        "existe" => false,
        "formato_valido" => false,
        "remision" => $remision,
    ]);
    exit;
}

$sql = "SELECT id_remision, hora_inicio, estatus, camion_logistica FROM tb_remisiones WHERE folio_remision = :folio ORDER BY id_remision ASC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':folio', $remision);
$stmt->execute();

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

$respuesta = [
    "existe" => $resultado ? true : false,
    "formato_valido" => true,
    "remision" => $remision,
];

// Si ya esta registrada se regresa cuando, para avisarle al operador.
if ($resultado) {
    $momento = !empty($resultado['hora_inicio']) ? strtotime($resultado['hora_inicio']) : false;
    $respuesta['registrada_fecha'] = $momento ? date('d/m/Y', $momento) : '';
    $respuesta['registrada_hora'] = $momento ? date('H:i', $momento) : '';
    $respuesta['registrada_estatus'] = (string) $resultado['estatus'];
    $respuesta['registrada_camion'] = (string) ($resultado['camion_logistica'] ?? '');
}

echo json_encode($respuesta);
