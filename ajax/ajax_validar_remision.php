<?php
require_once("../app/config/config.php");
header('Content-Type: application/json');
$remision = $_POST['remision'];

$sql = "SELECT id_remision FROM tb_remisiones WHERE folio_remision = :folio LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':folio', $remision);
$stmt->execute();

$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "existe" => $resultado ? true : false
]);
