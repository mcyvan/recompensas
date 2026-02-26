<?php
include('../app/config/config.php');
session_start();

// Obtener los datos del formulario
$nombre = strtoupper(trim($_POST['nombre']));
$apellido_p = strtoupper(trim($_POST['apellido_p']));
$apellido_m = strtoupper(trim($_POST['apellido_m']));
$telefono = trim($_POST['telefono']);
$id_rol = $_POST['id_rol'];
$usuario = strtoupper(trim($_POST['usuario']));
$fecha_registro = date('Y-m-d');
$password = trim($_POST['password']);
$passwordhash = password_hash($password, PASSWORD_DEFAULT);

if (empty($nombre) || empty($apellido_p) || empty($usuario) || empty($password)) {
    $_SESSION['mensaje_registro_usuario_existe'] = "Todos los campos son obligatorios y no pueden estar vacíos";
    header('Location: ' . $URL . '/usuarios/registrar_usuarios.php');
    exit(); // ¡Importante! Detiene la ejecución
}

// Preparar la consulta de login
$consulta_login = $pdo->prepare("SELECT * FROM tb_usuarios WHERE usuario = :usuario AND estatus = 1");
$consulta_login->bindParam(':usuario', $usuario, PDO::PARAM_STR);
$consulta_login->execute();

// Obtener el resultado
$resultado = $consulta_login->fetch(PDO::FETCH_ASSOC);
//echo password_hash($resultado['password'], PASSWORD_DEFAULT);
// exit();
if ($resultado) {
    // Si no se encuentra el usuario
    $_SESSION['mensaje_registro_usuario_existe'] = "El usuario ya existe";
    header('Location: ' . $URL . '/usuarios/registrar_usuarios.php');
    exit();
} else {
    // Insertar el nuevo usuario en la base de datos
    $insertar_usuario = $pdo->prepare("INSERT INTO tb_usuarios (usuario, password, estatus, fecha_registro) VALUES (:usuario, :password, 1, :fecha_registro)");
    $insertar_usuario->bindParam(':usuario', $usuario, PDO::PARAM_STR);
    $insertar_usuario->bindParam(':password', $passwordhash, PDO::PARAM_STR);
    $insertar_usuario->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);
    $insertar_usuario->execute();

    // Obtener el ID del usuario recién insertado
    $id_usuario = $pdo->lastInsertId();
    // Insertar los detalles del usuario en la tabla tb_usuarios_detalle
    $insertar_usuario_detalle = $pdo->prepare("INSERT INTO tb_usuarios_detalle (id_usuario, nombres, apellido_p, apellido_m, telefono, id_rol, fecha_registro) 
                                                    VALUES (:id_usuario, :nombres, :apellido_p, :apellido_m, :telefono, :id_rol, :fecha_registro)");
    $insertar_usuario_detalle->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $insertar_usuario_detalle->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
    $insertar_usuario_detalle->bindParam(':nombres', $nombre, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':apellido_p', $apellido_p, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':apellido_m', $apellido_m, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':telefono', $telefono, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);

    $insertar_usuario_detalle->execute();

    $_SESSION['mensaje_registro_usuario_correcto'] = "Usuario registrado correctamente";
    header('Location: ' . $URL . '/usuarios/registrar_usuarios.php');
    exit();
}
