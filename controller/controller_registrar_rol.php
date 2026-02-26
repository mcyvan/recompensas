<?php
include('../app/config/config.php');
session_start();

// Obtener los datos del formulario
$rol = strtoupper($_POST['rol']);
$fecha_registro = date('Y-m-d');

// Preparar la consulta de login
$consulta_rol = $pdo->prepare("SELECT * FROM tb_roles WHERE rol = :rol AND estatus = 1");
$consulta_rol->bindParam(':rol', $rol, PDO::PARAM_STR);
$consulta_rol->execute();

// Obtener el resultado
$resultado = $consulta_rol->fetch(PDO::FETCH_ASSOC);
//echo password_hash($resultado['password'], PASSWORD_DEFAULT);
// exit();
if ($resultado) {
    // Si no se encuentra el rol
    $_SESSION['mensaje_registro_rol_existe'] = "El rol ya existe";
    header('Location: ' . $URL . '/usuarios/registrar_rol.php');
    exit();
} else {
    // Insertar el nuevo rol en la base de datos
    $insertar_rol = $pdo->prepare("INSERT INTO tb_roles (rol, fecha_registro, estatus) VALUES (:rol, :fecha_registro, 1)");
    $insertar_rol->bindParam(':rol', $rol, PDO::PARAM_STR);
    $insertar_rol->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);
    $insertar_rol->execute();

    $_SESSION['mensaje_registro_rol_correcto'] = "Rol registrado correctamente";
    header('Location: ' . $URL . '/usuarios/registrar_rol.php');
    exit();
}
