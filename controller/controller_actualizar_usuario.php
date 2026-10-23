<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
include("../app/functions/bitacora.php");
verificarSesion();

$id_usuario = $_POST['id_usuario'];

// Obtener los datos del formulario
$nombres = strtoupper(trim($_POST['nombres']));
$apellido_p = strtoupper(trim($_POST['apellido_p']));
$apellido_m = strtoupper(trim($_POST['apellido_m']));
$usuario = strtoupper(trim($_POST['usuario']));
$telefono = trim($_POST['telefono']);
$id_rol = $_POST['id_rol'];
$password = trim($_POST['password']);  // Contraseña nueva
$fecha_actualizacion = date('Y-m-d H:i:s');
$estatus = $_POST['estatus'];

if (empty($nombres) || empty($apellido_p) || empty($apellido_m) || empty($usuario)) {
    $_SESSION['mensaje_registro_usuario_existe'] = "Todos los campos son obligatorios y no pueden estar vacíos";
    header('Location: ' . $URL . '/usuarios/registrar_usuarios.php');
    exit(); // ¡Importante! Detiene la ejecución
}

// Si la contraseña es proporcionada, hashearla. Si no, mantener la actual
if (!empty($password)) {
    // Hashear la nueva contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
} else {
    // Si no se proporciona una nueva contraseña, no actualizar la contraseña
    // Primero, obtener la contraseña actual desde la base de datos
    $consulta_usuario = $pdo->prepare("SELECT password FROM tb_usuarios WHERE id_usuario = :id_usuario");
    $consulta_usuario->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $consulta_usuario->execute();
    $usuario_data = $consulta_usuario->fetch(PDO::FETCH_ASSOC);

    // Si el usuario existe, mantenemos la contraseña actual
    $hashed_password = $usuario_data['password'];
}

$consulta_previa = $pdo->prepare("SELECT u.usuario, u.estatus, d.nombres, d.apellido_p, d.apellido_m, d.telefono, d.id_rol
                                  FROM tb_usuarios u
                                  LEFT JOIN tb_usuarios_detalle d ON d.id_usuario = u.id_usuario
                                  WHERE u.id_usuario = :id_usuario");
$consulta_previa->execute([':id_usuario' => $id_usuario]);
$usuarioAnterior = $consulta_previa->fetch(PDO::FETCH_ASSOC) ?: [];

// Preparar la consulta de actualización
$consulta_actualizar = $pdo->prepare("UPDATE tb_usuarios SET
                                        usuario = :usuario,                                       
                                        password = :password,
                                        estatus = :estatus,
                                        fecha_registro = :fecha_registro 
                                      WHERE id_usuario = :id_usuario");


$consulta_actualizar->bindParam(':usuario', $usuario, PDO::PARAM_STR);
$consulta_actualizar->bindParam(':password', $hashed_password, PDO::PARAM_STR);
$consulta_actualizar->bindParam(':estatus', $estatus, PDO::PARAM_INT);
$consulta_actualizar->bindParam(':fecha_registro', $fecha_actualizacion, PDO::PARAM_STR);
$consulta_actualizar->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);

// Ejecutar la consulta
$consulta_actualizar->execute();

// Preparar la consulta de actualización
$consulta_actualizar = $pdo->prepare("UPDATE tb_usuarios_detalle SET 
                                        nombres= :nombres, 
                                        apellido_p = :apellido_p,
                                        apellido_m = :apellido_m,
                                        telefono = :telefono,
                                        id_rol = :id_rol,
                                        fecha_registro = :fecha_registro 
                                      WHERE id_usuario = :id_usuario");

$consulta_actualizar->bindParam(':nombres', $nombres, PDO::PARAM_STR);
$consulta_actualizar->bindParam(':apellido_p', $apellido_p, PDO::PARAM_STR);
$consulta_actualizar->bindParam(':apellido_m', $apellido_m, PDO::PARAM_STR);
$consulta_actualizar->bindParam(':telefono', $telefono, PDO::PARAM_STR);
$consulta_actualizar->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
$consulta_actualizar->bindParam(':fecha_registro', $fecha_actualizacion, PDO::PARAM_STR);
$consulta_actualizar->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);

// Ejecutar la consulta
$consulta_actualizar->execute();

$cambiosUsuario = [
    'usuario' => $usuario,
    'estatus' => $estatus,
    'nombres' => $nombres,
    'apellido_p' => $apellido_p,
    'apellido_m' => $apellido_m,
    'telefono' => $telefono,
    'id_rol' => $id_rol,
];
$anteriorUsuario = $usuarioAnterior;
if (!empty($password)) {
    // Nunca se guarda la contrasena, solo el hecho de que fue cambiada.
    $anteriorUsuario['contrasena'] = '';
    $cambiosUsuario['contrasena'] = 'CAMBIADA';
}
registrarBitacoraCambios($pdo, 'USUARIO', (int) $id_usuario, 'ACTUALIZAR', $anteriorUsuario, $cambiosUsuario);

$_SESSION['mensaje_actualizar_usuario_correcto'] = "Usuario actualizado correctamente";
header('Location: ../usuarios/registrar_usuarios.php');
exit();
