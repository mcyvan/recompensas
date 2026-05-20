<?php
include('../app/config/config.php');
include('../app/functions/auth.php');
/** @var PDO $pdo */
verificarSesion();


// Obtener los datos del formulario
$categoria = strtoupper($_POST['categoria']);
$descripcion = strtoupper($_POST['descripcion']);
$fecha_registro = date('Y-m-d');

// Preparar la consulta de login
$consulta_categoria = $pdo->prepare("SELECT * FROM tb_categorias WHERE categoria = :categoria AND estatus = 1");
$consulta_categoria->bindParam(':categoria', $categoria, PDO::PARAM_STR);
$consulta_categoria->execute();

// Obtener el resultado
$resultado = $consulta_categoria->fetch(PDO::FETCH_ASSOC);
//echo password_hash($resultado['password'], PASSWORD_DEFAULT);
// exit();
if ($resultado) {
    // Si no se encuentra la categoría
    $_SESSION['mensaje_registro_categoria_existe'] = "La categoría ya existe";
    header('Location: ' . $URL . '/categorias/registrar_categoria.php');
    exit();
} else {
    // Insertar la nueva categoría en la base de datos
    $insertar_categoria = $pdo->prepare("INSERT INTO tb_categorias (categoria, fecha_registro, estatus, descripcion) VALUES (:categoria, :fecha_registro, 1, :descripcion)");
    $insertar_categoria->bindParam(':categoria', $categoria, PDO::PARAM_STR);
    $insertar_categoria->bindParam(':fecha_registro', $fecha_registro, PDO::PARAM_STR);
    $insertar_categoria->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
    $insertar_categoria->execute();

    $_SESSION['mensaje_registro_categoria_correcto'] = "Categoría registrada correctamente";
    header('Location: ' . $URL . '/categorias/registrar_categoria.php');
    exit();
}
