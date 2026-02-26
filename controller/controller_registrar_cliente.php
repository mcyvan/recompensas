<?php
include('../app/config/config.php');
session_start();

// Obtener los datos del formulario
$nombre = strtoupper(trim($_POST['nombre']));
$apellido_p = strtoupper(trim($_POST['apellido_p']));
$apellido_m = strtoupper(trim($_POST['apellido_m']));
$correo = strtolower(trim($_POST['correo']));
$telefono = trim($_POST['telefono']);
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$fecha_registro = date('Y-m-d');
$usuario = strtoupper(trim($_SESSION['id_usuario_login']));


if (empty($nombre) || empty($apellido_p) || empty($apellido_m) || empty($correo) || empty($telefono) || empty($fecha_nacimiento)) {
    $_SESSION['mensaje_registro_cliente_existe'] = "Todos los campos son obligatorios y no pueden estar vacíos";
    header('Location: ' . $URL . '/clientes/registrar_cliente.php');
    exit(); // ¡Importante! Detiene la ejecución
}

// Preparar la consulta de login
$consulta_login = $pdo->prepare("SELECT * FROM tb_clientes WHERE correo = :correo OR telefono = :telefono");
$consulta_login->bindParam(':correo', $correo, PDO::PARAM_STR);
$consulta_login->bindParam(':telefono', $telefono, PDO::PARAM_STR);
$consulta_login->execute();

// Obtener el resultado
$resultado = $consulta_login->fetch(PDO::FETCH_ASSOC);
//echo password_hash($resultado['password'], PASSWORD_DEFAULT);
// exit();
if ($resultado) {
    // Si no se encuentra el usuario
    $_SESSION['mensaje_registro_cliente_existe'] = "El cliente ya existe";
    header('Location: ' . $URL . '/clientes/registrar_cliente.php');
    exit();
} else {
    // Insertar los detalles del usuario en la tabla tb_clientes
    $insertar_usuario_detalle = $pdo->prepare("INSERT INTO tb_clientes (nombres, apellido_p, apellido_m, correo, telefono, fecha_nacimiento, fecha_registro, id_usuario) 
                                                    VALUES (:nombres, :apellido_p, :apellido_m, :correo, :telefono, :fecha_nacimiento, :fecha_registro, :id_usuario)");
    $insertar_usuario_detalle->bindParam(':nombres', $nombre, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':apellido_p', $apellido_p, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':apellido_m', $apellido_m, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':correo', $correo, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':telefono', $telefono, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':fecha_nacimiento', $fecha_nacimiento, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);
    $insertar_usuario_detalle->bindParam(':id_usuario', $usuario, PDO::PARAM_STR);

    $insertar_usuario_detalle->execute();

    $_SESSION['mensaje_registro_clientes_correcto'] = "Cliente registrado correctamente";
    header('Location: ' . $URL . '/clientes/registrar_cliente.php');
    exit();
}
