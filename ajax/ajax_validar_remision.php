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

$sql = "SELECT id_remision FROM tb_remisiones WHERE folio_remision = :folio LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':folio', $remision);
$stmt->execute();

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "existe" => $resultado ? true : false,
    "formato_valido" => true,
    "remision" => $remision,
]);
