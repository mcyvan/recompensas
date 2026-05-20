<?php

include('../app/config/config.php');
include('../app/functions/auth.php');

/** @var PDO $pdo */

verificarSesion();


// ===============================
// VALIDAR ID
// ===============================
if (!isset($_GET['id']) || empty($_GET['id'])) {

    $_SESSION['error_registro_premio'] = "ID de premio inválido.";

    header("Location: ../premios/registrar_premio.php");
    exit;
}

$id_premio = $_GET['id'];


// ===============================
// OBTENER PREMIO
// ===============================
$sql = "SELECT imagen 
        FROM tb_premios 
        WHERE id_premio = :id_premio";

$query = $pdo->prepare($sql);

$query->bindParam(':id_premio', $id_premio);

$query->execute();

$premio = $query->fetch(PDO::FETCH_ASSOC);


// ===============================
// VALIDAR EXISTENCIA
// ===============================
if (!$premio) {

    $_SESSION['error_registro_premio'] = "El premio no existe.";

    header("Location: ../premios/registrar_premio.php");
    exit;
}


// ===============================
// ELIMINAR IMAGEN
// ===============================
if (
    !empty($premio['imagen']) &&
    file_exists($premio['imagen'])
) {

    unlink($premio['imagen']);
}


// ===============================
// ELIMINAR PREMIO
// ===============================
$sql = "DELETE FROM tb_premios 
        WHERE id_premio = :id_premio";

$query = $pdo->prepare($sql);

$query->bindParam(':id_premio', $id_premio);

$resultado = $query->execute();


// ===============================
// RESULTADO
// ===============================
if ($resultado) {

    $_SESSION['mensaje'] = "Premio eliminado correctamente.";
} else {

    $_SESSION['error_registro_premio'] = "Error al eliminar el premio.";
}


// ===============================
// REDIRECT
// ===============================
header("Location: ../premios/registrar_premio.php");
exit;
