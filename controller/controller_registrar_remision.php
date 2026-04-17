<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
verificarSesion();

try {

    $pdo->beginTransaction();

    $telefono = $_POST['telefono'];
    $folio_remision = strtoupper($_POST['remision']);
    $volumen = floatval($_POST['volumen']);
    $id_usuario = $_SESSION['id_usuario_login'];
    $estatus = 'EN PROCESO';

    $hora_inicio = date('Y-m-d H:i:s');

    // echo $telefono . " - " . $folio_remision . " - " . $volumen . " - " . $id_usuario . " - " . $hora_inicio . " - " . $estatus;
    // exit();


    // ✅ VALIDAR TELÉFONO
    $stmt = $pdo->prepare("SELECT id_cliente FROM tb_clientes WHERE telefono = ?");
    $stmt->execute([$telefono]);

    if (!$stmt->fetch()) {
        throw new Exception("Teléfono no válido");
    }

    // ✅ VALIDAR FORMATO
    if (!preg_match('/^RE\d+$/', $folio_remision)) {
        throw new Exception("Formato de remisión inválido");
    }

    // ✅ VALIDAR DUPLICADO
    $stmt = $pdo->prepare("SELECT folio_remision FROM tb_remisiones WHERE folio_remision = ?");
    $stmt->execute([$folio_remision]);

    if ($stmt->fetch()) {
        throw new Exception("La remisión ya existe");
    }

    // ✅ VALIDAR METROS
    if ($volumen < 1 || $volumen > 8 || fmod($volumen, 0.5) != 0.0) {
        throw new Exception("Metros no válidos");
    }

    // ✅ INSERT
    $consulta = $pdo->prepare("
        INSERT INTO tb_remisiones 
        (telefono, folio_remision, volumen, id_operador, hora_inicio, estatus) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $consulta->execute([
        $telefono,
        $folio_remision,
        $volumen,
        $id_usuario,
        $hora_inicio,
        $estatus
    ]);

    $id_remision = $pdo->lastInsertId();

    $pdo->commit();

    $_SESSION['mensaje_registro_remision_correcto'] = "Remisión registrada correctamente.";

    header('Location: ' . $URL . '/operador/remision_en_proceso.php?id=' . $id_remision);
    exit();
} catch (Exception $e) {

    $pdo->rollBack();

    $_SESSION['mensaje_registro_remision_error'] = $e->getMessage();

    header('Location: ' . $URL . '/operador/ingresar_tiempo_descarga.php');
    exit();
}
