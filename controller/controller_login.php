<?php
include('../app/config/config.php');
session_start();

// Obtener los datos del formulario
$usuario = $_POST['usuario'];
$password = $_POST['password'];

// Preparar la consulta de login
$consulta_login = $pdo->prepare("SELECT * FROM tb_usuarios WHERE usuario = :usuario AND estatus = 1");
$consulta_login->bindParam(':usuario', $usuario, PDO::PARAM_STR);
$consulta_login->execute();

// Obtener el resultado
$resultado = $consulta_login->fetch(PDO::FETCH_ASSOC);
//echo password_hash($resultado['password'], PASSWORD_DEFAULT);
// exit();


if (!$resultado) {
    // Si no se encuentra el usuario
    header('Location: ' . $URL . '/login');
    exit();
} else {
    // Verificar si la contraseña ingresada coincide con el hash almacenado
    if (password_verify($password, $resultado['password'])) {
        // if ($password == "123*") {

        // Preparar la consulta de login
        $consulta_login = $pdo->prepare("SELECT 
                                            tb_usuarios_detalle.nombres,
                                            tb_usuarios_detalle.apellido_p,
                                            tb_usuarios_detalle.apellido_m,                                            
                                            tb_usuarios_detalle.telefono,                                            
                                            tb_usuarios_detalle.fecha_registro,
                                            tb_usuarios.estatus,
                                            tb_usuarios.usuario,
                                            tb_usuarios.id_usuario,
                                            tb_usuarios.password,
                                            tb_roles.rol
                                            FROM tb_usuarios 
                                            INNER JOIN tb_usuarios_detalle ON tb_usuarios_detalle.id_usuario=tb_usuarios.id_usuario
                                            INNER JOIN tb_roles ON tb_roles.id_rol=tb_usuarios_detalle.id_rol
                                            WHERE tb_usuarios.usuario = :usuario AND tb_usuarios.estatus = 1");
        $consulta_login->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $consulta_login->execute();

        // Obtener el resultado
        $resultado = $consulta_login->fetch(PDO::FETCH_ASSOC);

        // Si la contraseña es correcta, asignamos los datos a las variables de sesión
        $nombre = $resultado['nombres'] . " " . $resultado['apellido_p'] . " " . $resultado['apellido_m'];

        $telefono = $resultado['telefono'];
        $id_usuario = $resultado['id_usuario'];
        $rol = $resultado['rol'];

        // Asignamos los datos a la sesión
        $_SESSION['rol'] = $rol;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['id_usuario_login'] = $id_usuario;
        $_SESSION['usuario'] = $usuario;

        // Redirigir dependiendo del usuario
        if ($_SESSION['rol'] == "ADMINISTRADOR") {
            header('Location: ../administracion/inicio.php');
        } else if ($_SESSION['rol'] == "VENDEDOR") {
            header('Location: ../vendedor/menu_vendedor.php');
        } else if ($_SESSION['rol'] == "ADMINISTRACION") {
            header('Location: ../administracion/inicio.php');
        } else if ($_SESSION['rol'] == "OPERADOR") {
            header('Location: ../operador/menu_operador.php');
        }
        exit(); // Asegurarse de que no siga ejecutando código después de la redirección
    } else {
        // Si la contraseña no es correcta
        header('Location: ' . $URL . '/login');
        exit();
    }
}
