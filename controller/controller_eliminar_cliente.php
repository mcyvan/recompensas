<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
include("../app/functions/bitacora.php");
verificarSesion();

if (!in_array($_SESSION['rol'] ?? '', ['ADMINISTRADOR', 'ADMINISTRACION'], true)) {
    $_SESSION['mensaje_registro_cliente_existe'] = "No tienes permiso para desactivar clientes";
    header('Location: ../clientes/registrar_cliente.php');
    exit();
}

$id_cliente = (int) ($_GET['id_cliente'] ?? 0);

$consulta = $pdo->prepare("SELECT estatus FROM tb_clientes WHERE id_cliente = :id_cliente");
$consulta->execute([':id_cliente' => $id_cliente]);
$clienteActual = $consulta->fetch(PDO::FETCH_ASSOC);

if ($clienteActual) {
    $eliminar_cliente = $pdo->prepare("UPDATE tb_clientes SET estatus = 0 WHERE id_cliente = :id_cliente;");
    $eliminar_cliente->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $eliminar_cliente->execute();

    registrarBitacoraCambios($pdo, 'CLIENTE', $id_cliente, 'DESACTIVAR', $clienteActual, ['estatus' => 0]);
}

$_SESSION['mensaje_registro_cliente_eliminado'] = "Cliente desactivado correctamente";
header('Location: ../clientes/registrar_cliente.php');
exit();
