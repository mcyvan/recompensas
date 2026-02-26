<?php
include('../app/config/config.php');
session_start();

// Obtener los datos del formulario
$rol = strtoupper($_POST['rol']);
$estatus = $_POST['estatus'];
$id_rol = $_POST['id_rol'];
$fecha_registro = date('Y-m-d');

// Preparar la consulta de login
$consulta_rol = $pdo->prepare("SELECT * FROM tb_roles WHERE rol = :rol AND id_rol != :id_rol;");
$consulta_rol->bindParam(':rol', $rol, PDO::PARAM_STR);
$consulta_rol->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
$consulta_rol->execute();

// Obtener el resultado
$resultado = $consulta_rol->fetch(PDO::FETCH_ASSOC);

if ($resultado) {
    // Si no se encuentra el rol
    $_SESSION['mensaje_registro_rol_existe'] = "El rol ya existe";
    header('Location: ' . $URL . '/usuarios/registrar_rol.php');
    exit();
} else {
    // Insertar el nuevo rol en la base de datos
    $insertar_rol = $pdo->prepare("UPDATE tb_roles SET rol = :rol, estatus = :estatus, fecha_registro = :fecha_registro WHERE id_rol = :id_rol");
    $insertar_rol->bindParam(':rol', $rol, PDO::PARAM_STR);
    $insertar_rol->bindParam(':estatus', $estatus, PDO::PARAM_STR);
    $insertar_rol->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);
    $insertar_rol->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
    $insertar_rol->execute();

    $_SESSION['mensaje_registro_rol_actualizado'] = "Rol actualizado correctamente";
    header('Location: ' . $URL . '/usuarios/registrar_rol.php');
    exit();
}
