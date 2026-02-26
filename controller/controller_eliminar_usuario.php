<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
verificarSesion();


$id_usuario = $_GET['id_usuario'];

//consultar usuario no repetido
$eliminar_usuario = $pdo->prepare("UPDATE tb_usuarios SET estatus = 0 WHERE id_usuario = :id_usuario;");
$eliminar_usuario->bindParam(':id_usuario', $id_usuario, PDO::PARAM_STR);
$eliminar_usuario->execute();

$_SESSION['mensaje_registro_usuario_eliminado'] = "Usuario desactivado correctamente";
header('Location: ../usuarios/registrar_usuarios.php');
exit();
