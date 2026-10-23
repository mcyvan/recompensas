<?php
require_once("../app/config/config.php");

header('Content-Type: application/json; charset=utf-8');

$telefono = preg_replace('/\D/', '', (string) ($_POST['telefono'] ?? ''));
$idClienteQr = filter_var($_POST['id_cliente_qr'] ?? null, FILTER_VALIDATE_INT) ?: 0;

if ($idClienteQr > 0) {
    $sql = "SELECT id_cliente, nombres, apellido_p, apellido_m, telefono
            FROM tb_clientes
            WHERE id_cliente = :id_cliente AND estatus = 1
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_cliente', $idClienteQr, PDO::PARAM_INT);
} else {
    $sql = "SELECT id_cliente, nombres, apellido_p, apellido_m, telefono
            FROM tb_clientes
            WHERE telefono = :telefono AND estatus = 1
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':telefono', $telefono, PDO::PARAM_STR);
}

$stmt->execute();
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cliente) {
    echo json_encode([
        "existe" => true,
        "id_cliente" => $cliente['id_cliente'],
        "telefono" => $cliente['telefono'],
        "nombre" => $cliente['nombres'],
        "apellido_p" => $cliente['apellido_p'],
        "apellido_m" => $cliente['apellido_m']
    ]);
} else {
    echo json_encode([
        "existe" => false
    ]);
}