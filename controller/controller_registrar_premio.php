<?php

include("../app/config/config.php");
include("../app/functions/auth.php");

/** @var PDO $pdo */

verificarSesion();

date_default_timezone_set('America/Chihuahua');


// ===============================
// VALIDAR DATOS
// ===============================
$premio             = strtoupper(trim($_POST['premio'] ?? ''));
$descripcion        = strtoupper(trim($_POST['descripcion'] ?? ''));
$puntos_requeridos  = strtoupper(intval($_POST['puntos_requeridos'] ?? 0));
$stock              = strtoupper(intval($_POST['stock'] ?? 0));
$id_categoria          = strtoupper(trim($_POST['id_categoria'] ?? ''));
$activo             = strtoupper(intval($_POST['activo'] ?? 1));


// ===============================
// VALIDAR CAMPOS VACIOS
// ===============================
if (
    empty($premio) ||
    empty($descripcion) ||
    empty($id_categoria) ||
    $puntos_requeridos <= 0
) {

    $_SESSION['error_registro_premio'] = "Completa todos los campos.";

    header("Location: ../premios/registrar_premio.php");
    exit;
}


// ===============================
// VALIDAR IMAGEN
// ===============================
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] != 0) {

    $_SESSION['error_registro_premio'] = "Debes seleccionar una imagen.";

    header("Location: ../premios/registrar_premio.php");
    exit;
}


// ===============================
// CONFIGURACION IMAGEN
// ===============================
$directorio = "../premios/img/";

if (!file_exists($directorio)) {
    mkdir($directorio, 0777, true);
}

$imagen      = $_FILES['imagen'];
$tmp         = $imagen['tmp_name'];
$tamano      = $imagen['size'];
$nombreReal  = $imagen['name'];


// ===============================
// VALIDAR TAMAÑO
// ===============================
$maxSize = 5 * 1024 * 1024; // 5MB

if ($tamano > $maxSize) {

    $_SESSION['error_registro_premio'] = "La imagen excede 5MB.";

    header("Location: ../premios/registrar_premio.php");
    exit;
}


// ===============================
// VALIDAR EXTENSION
// ===============================
$extension = strtolower(pathinfo($nombreReal, PATHINFO_EXTENSION));

$extPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($extension, $extPermitidas)) {

    $_SESSION['error_registro_premio'] = "Formato no permitido.";

    header("Location: ../premios/registrar_premio.php");
    exit;
}


// ===============================
// GENERAR NOMBRE UNICO
// ===============================
$nombreImagen = uniqid($premio . '_') . ".jpg";

$rutaCompleta = $directorio . $nombreImagen;


// ===============================
// CREAR IMAGEN SEGUN FORMATO
// ===============================
switch ($extension) {

    case 'jpg':
    case 'jpeg':
        $source = imagecreatefromjpeg($tmp);
        break;

    case 'png':
        $source = imagecreatefrompng($tmp);
        break;

    case 'webp':
        $source = imagecreatefromwebp($tmp);
        break;

    default:

        $_SESSION['error_registro_premio'] = "Formato inválido.";

        header("Location: ../premios/registrar_premio.php");
        exit;
}


// ===============================
// OBTENER DIMENSIONES
// ===============================
$width  = imagesx($source);
$height = imagesy($source);


// ===============================
// REDIMENSIONAR
// ===============================
$nuevoAncho = 1000;

$nuevoAlto = ($height / $width) * $nuevoAncho;

$nuevaImagen = imagecreatetruecolor($nuevoAncho, $nuevoAlto);


// ===============================
// FONDO BLANCO PNG
// ===============================
$blanco = imagecolorallocate($nuevaImagen, 255, 255, 255);

imagefill($nuevaImagen, 0, 0, $blanco);


// ===============================
// COPIAR Y REDIMENSIONAR
// ===============================
imagecopyresampled(
    $nuevaImagen,
    $source,
    0,
    0,
    0,
    0,
    $nuevoAncho,
    $nuevoAlto,
    $width,
    $height
);


// ===============================
// GUARDAR JPG COMPRIMIDO
// ===============================
imagejpeg($nuevaImagen, $rutaCompleta, 75);


// ===============================
// LIBERAR MEMORIA
// ===============================
imagedestroy($source);
imagedestroy($nuevaImagen);


// ===============================
// RUTA PARA BD
// ===============================
$rutaBD = "../premios/img/" . $nombreImagen;


// ===============================
// INSERTAR EN BD
// ===============================
try {

    $stmt = $pdo->prepare("
        INSERT INTO tb_premios
        (
            premio,
            descripcion,
            puntos_requeridos,
            stock,
            id_categoria,
            imagen,
            activo,
            fecha_registro
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, NOW()
        )
    ");

    $stmt->execute([
        $premio,
        $descripcion,
        $puntos_requeridos,
        $stock,
        $id_categoria,
        $rutaBD,
        $activo
    ]);


    $_SESSION['mensaje_registro_premio_correcto'] =
        "Premio registrado correctamente.";
} catch (Exception $e) {

    error_log($e->getMessage());

    $_SESSION['error_registro_premio'] =
        "Error al registrar premio.";
}


// ===============================
// REDIRECCION
// ===============================
header("Location: ../premios/registrar_premio.php");
exit;
