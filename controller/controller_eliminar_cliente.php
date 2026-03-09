<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
verificarSesion();


$id_cliente = $_GET['id_cliente'];

//consultar usuario no repetido
$eliminar_cliente = $pdo->prepare("UPDATE tb_clientes SET estatus = 0 WHERE id_cliente = :id_cliente;");
$eliminar_cliente->bindParam(':id_cliente', $id_cliente, PDO::PARAM_STR);
$eliminar_cliente->execute();

$_SESSION['mensaje_registro_cliente_eliminado'] = "Cliente desactivado correctamente";
header('Location: ../clientes/registrar_cliente.php');
exit();
