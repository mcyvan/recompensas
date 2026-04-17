<?php
require_once("../app/config/config.php"); // aquí ya debes tener $pdo

$telefono = $_POST['telefono'];

$sql = "SELECT nombres,apellido_p,apellido_m FROM tb_clientes WHERE telefono = :telefono LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':telefono', $telefono);
$stmt->execute();

$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cliente) {
    echo json_encode([
        "existe" => true,
        "nombre" => $cliente['nombres'],
        "apellido_p" => $cliente['apellido_p'],
        "apellido_m" => $cliente['apellido_m']
    ]);
} else {
    echo json_encode([
        "existe" => false
    ]);
}
