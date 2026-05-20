<?php
include('../app/config/config.php');
include('../app/functions/auth.php');

/** @var PDO $pdo */

verificarSesion();

session_start();

// ===============================
// RECIBIR DATOS
// ===============================
$id_premio          = $_POST['id_premio'];
$premio             = strtoupper(trim($_POST['premio']));
$id_categoria       = strtoupper(trim($_POST['id_categoria']));
$puntos_requeridos  = intval($_POST['puntos_requeridos']);
$stock              = intval($_POST['stock']);
$activo             = intval($_POST['activo']);
$descripcion        = strtoupper(trim($_POST['descripcion']));


// ===============================
// VALIDAR CAMPOS
// ===============================
if (
    empty($premio) ||
    empty($descripcion) ||
    empty($id_categoria) ||
    $puntos_requeridos <= 0
) {

    $_SESSION['error_registro_premio'] = "Completa todos los campos.";

    header("Location: ../premios/actualizar_premio.php?id=" . $id_premio);
    exit;
}


// ===============================
// OBTENER PREMIO ACTUAL
// ===============================
$sql = "SELECT imagen 
        FROM tb_premios 
        WHERE id_premio = :id_premio";

$query = $pdo->prepare($sql);

$query->bindParam(':id_premio', $id_premio);

$query->execute();

$premioActual = $query->fetch(PDO::FETCH_ASSOC);


// ===============================
// VALIDAR SI EXISTE
// ===============================
if (!$premioActual) {

    $_SESSION['error_registro_premio'] = "El premio no existe.";

    header("Location: ../premios/registrar_premio.php");
    exit;
}


// ===============================
// CONSERVAR IMAGEN ACTUAL
// ===============================
$rutaBD = $premioActual['imagen'];


// ===============================
// SI SUBIO NUEVA IMAGEN
// ===============================
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {

    // ===============================
    // CONFIGURACION
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
    $maxSize = 5 * 1024 * 1024;

    if ($tamano > $maxSize) {

        $_SESSION['error_registro_premio'] = "La imagen excede 5MB.";

        header("Location: ../premios/actualizar_premio.php?id=" . $id_premio);
        exit;
    }


    // ===============================
    // VALIDAR EXTENSION
    // ===============================
    $extension = strtolower(pathinfo($nombreReal, PATHINFO_EXTENSION));

    $extPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $extPermitidas)) {

        $_SESSION['error_registro_premio'] = "Formato no permitido.";

        header("Location: ../premios/actualizar_premio.php?id=" . $id_premio);
        exit;
    }


    // ===============================
    // GENERAR NOMBRE UNICO
    // ===============================
    $nombreImagen = uniqid('premio_') . ".jpg";

    $rutaCompleta = $directorio . $nombreImagen;


    // ===============================
    // CREAR IMAGEN
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

            header("Location: ../premios/actualizar_premio.php?id=" . $id_premio);
            exit;
    }


    // ===============================
    // VALIDAR IMAGEN
    // ===============================
    if (!$source) {

        $_SESSION['error_registro_premio'] = "La imagen no es válida.";

        header("Location: ../premios/actualizar_premio.php?id=" . $id_premio);
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

    $nuevoAlto = intval(($height / $width) * $nuevoAncho);

    $nuevaImagen = imagecreatetruecolor($nuevoAncho, $nuevoAlto);


    // ===============================
    // FONDO BLANCO
    // ===============================
    $blanco = imagecolorallocate($nuevaImagen, 255, 255, 255);

    imagefill($nuevaImagen, 0, 0, $blanco);


    // ===============================
    // REDIMENSIONAR
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
    // GUARDAR JPG
    // ===============================
    imagejpeg($nuevaImagen, $rutaCompleta, 75);


    // ===============================
    // LIBERAR MEMORIA
    // ===============================
    imagedestroy($source);
    imagedestroy($nuevaImagen);


    // ===============================
    // ELIMINAR IMAGEN ANTERIOR
    // ===============================
    if (
        !empty($premioActual['imagen']) &&
        file_exists($premioActual['imagen'])
    ) {

        unlink($premioActual['imagen']);
    }


    // ===============================
    // NUEVA RUTA BD
    // ===============================
    $rutaBD = "../premios/img/" . $nombreImagen;
}


// ===============================
// UPDATE
// ===============================
$sql = "UPDATE tb_premios 
        SET
            premio = :premio,
            id_categoria = :id_categoria,
            puntos_requeridos = :puntos_requeridos,
            stock = :stock,
            activo = :activo,
            descripcion = :descripcion,
            imagen = :imagen
        WHERE id_premio = :id_premio";

$query = $pdo->prepare($sql);

$query->bindParam(':premio', $premio);
$query->bindParam(':id_categoria', $id_categoria);
$query->bindParam(':puntos_requeridos', $puntos_requeridos);
$query->bindParam(':stock', $stock);
$query->bindParam(':activo', $activo);
$query->bindParam(':descripcion', $descripcion);
$query->bindParam(':imagen', $rutaBD);
$query->bindParam(':id_premio', $id_premio);

$resultado = $query->execute();


// ===============================
// RESULTADO
// ===============================
if ($resultado) {

    $_SESSION['mensaje_premio_actualizado'] = "Premio actualizado correctamente.";
} else {

    $_SESSION['error_registro_premio'] = "Error al actualizar el premio.";
}


// ===============================
// REDIRECT
// ===============================
header("Location: ../premios/registrar_premio.php?id=" . $id_premio);
exit;
