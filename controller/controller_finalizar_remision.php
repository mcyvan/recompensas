<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
verificarSesion();

try {

    $pdo->beginTransaction();

    $id_remision = $_POST['id_remision'];
    $id_usuario = $_SESSION['id_usuario_login'];

    // 🔍 obtener datos
    $stmt = $pdo->prepare("
        SELECT hora_inicio, estatus 
        FROM tb_remisiones 
        WHERE id_remision = ? AND id_operador = ?
        LIMIT 1
    ");
    $stmt->execute([$id_remision, $id_usuario]);

    $remision = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$remision) {
        throw new Exception("Remisión no encontrada");
    }

    if ($remision['estatus'] != 'EN PROCESO') {
        throw new Exception("La remisión ya fue finalizada");
    }

    $hora_inicio = $remision['hora_inicio'];
    $hora_fin = date('Y-m-d H:i:s');

    // ⏱️ calcular tiempo en minutos
    $inicio = new DateTime($hora_inicio);
    $fin = new DateTime($hora_fin);

    $diferencia = $inicio->diff($fin);

    $minutos = ($diferencia->h * 60) + $diferencia->i;

    // 🏆 lógica de puntos
    $puntos = 0;
    $estatus_final = 'FINALIZADO';

    if ($minutos <= 45) {
        $puntos = $minutos * 2; // 👈 puedes cambiar la lógica
    }

    // ✅ actualizar remisión
    $stmt = $pdo->prepare("
        UPDATE tb_remisiones 
        SET hora_fin = ?, minutos_colado = ?, puntos = ?, estatus = ?
        WHERE id_remision = ?
    ");

    $stmt->execute([
        $hora_fin,
        $minutos,
        $puntos,
        $estatus_final,
        $id_remision
    ]);

    $pdo->commit();

    $_SESSION['mensaje_registro_remision_correcto'] =
        "Remisión finalizada en $minutos min. Puntos: $puntos";

    header('Location: ' . $URL . '/operador/ingresar_tiempo_descarga.php');
    exit();
} catch (Exception $e) {

    $pdo->rollBack();

    $_SESSION['mensaje_registro_remision_error'] = $e->getMessage();

    header('Location: ' . $URL . '/operador/ingresar_tiempo_descarga.php');
    exit();
}
