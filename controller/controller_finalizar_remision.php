<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
include("../app/functions/consultas_puntos.php");
/** @var PDO $pdo */
/** @var string $URL */
verificarSesion();

try {

    $pdo->beginTransaction();

    $id_remision = $_POST['id_remision'];
    $id_usuario = $_SESSION['id_usuario_login'];

    // 🔍 obtener datos
    $stmt = $pdo->prepare("
        SELECT hora_inicio, volumen, estatus
FROM tb_remisiones
WHERE id_remision = ?
  AND id_operador = ?
  AND estatus = 'EN PROCESO'
FOR UPDATE
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
    $volumen = (float) $remision['volumen'];

    if ($minutos <= 45) {
        $puntos = obtenerPuntos($volumen);
    }

    // ✅ actualizar remisión
    $stmt = $pdo->prepare("
        UPDATE tb_remisiones
SET hora_fin = ?, minutos_colado = ?, puntos = ?, estatus = 'FINALIZADO'
WHERE id_remision = ?
  AND id_operador = ?
  AND estatus = 'EN PROCESO'
    ");

    $stmt->execute([
        $hora_fin,
        $minutos,
        $puntos,
        $id_remision,
        $id_usuario
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
