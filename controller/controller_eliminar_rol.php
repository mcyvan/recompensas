<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
verificarSesion();


$id_rol = $_GET['id_rol'];

//consultar rol no repetido
$eliminar_rol = $pdo->prepare("UPDATE tb_roles SET estatus = 0 WHERE id_rol = :id_rol;");
$eliminar_rol->bindParam(':id_rol', $id_rol, PDO::PARAM_STR);
$eliminar_rol->execute();

$_SESSION['mensaje_registro_rol_eliminado'] = "Rol desactivado correctamente";
header('Location: ../usuarios/registrar_rol.php');
exit();
