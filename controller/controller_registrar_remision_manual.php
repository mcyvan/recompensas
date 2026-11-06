<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
include("../app/functions/consultas_puntos.php");
include("../app/functions/remisiones.php");
/** @var PDO $pdo */
/** @var string $URL */
verificarSesion();
verificarPermisoRegistrarRemisionManual($pdo);

try {
    $pdo->beginTransaction();

    $telefono = preg_replace('/\D/', '', (string) ($_POST['telefono'] ?? ''));
    $id_cliente = obtenerIdClienteTelefono($telefono);
    $folio_remision = normalizarFolioRemisionOperador((string) ($_POST['remision'] ?? ''));
    $volumen = (float) ($_POST['volumen'] ?? 0);
    $id_operador = (int) ($_POST['id_operador'] ?? 0);
    $hora_inicio_input = (string) ($_POST['hora_inicio'] ?? '');
    $hora_fin_input = (string) ($_POST['hora_fin'] ?? '');

    if (!$id_cliente) {
        throw new Exception("Telefono no valido");
    }

    if (!folioRemisionOperadorValido($folio_remision)) {
        throw new Exception("La remision debe tener el formato RE seguido de 6 digitos, por ejemplo RE123456");
    }

    $stmt = $pdo->prepare("SELECT id_remision FROM tb_remisiones WHERE folio_remision = ? LIMIT 1");
    $stmt->execute([$folio_remision]);

    if ($stmt->fetch()) {
        throw new Exception("La remision ya existe");
    }

    if ($volumen < 1 || $volumen > 8 || fmod($volumen, 0.5) != 0.0) {
        throw new Exception("Metros no validos");
    }

    $stmt = $pdo->prepare(
        "SELECT u.id_usuario
         FROM tb_usuarios u
         INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
         INNER JOIN tb_roles r ON r.id_rol = ud.id_rol
         WHERE u.id_usuario = ?
           AND r.rol = 'OPERADOR'
           AND u.estatus = 1
         LIMIT 1"
    );
    $stmt->execute([$id_operador]);

    if (!$stmt->fetch()) {
        throw new Exception("Selecciona un chofer valido");
    }

    $hora_inicio = DateTime::createFromFormat('Y-m-d\TH:i', $hora_inicio_input);
    $hora_fin = DateTime::createFromFormat('Y-m-d\TH:i', $hora_fin_input);

    if (!$hora_inicio || !$hora_fin) {
        throw new Exception("Captura hora de inicio y hora de fin validas");
    }

    $ahora = new DateTime();
    if ($hora_inicio > $ahora || $hora_fin > $ahora) {
        throw new Exception("Las horas no pueden ser futuras");
    }

    if ($hora_fin < $hora_inicio) {
        throw new Exception("La hora de fin no puede ser menor a la hora de inicio");
    }

    $minutos = (int) floor(($hora_fin->getTimestamp() - $hora_inicio->getTimestamp()) / 60);
    $puntos = $minutos <= 45 ? obtenerPuntos($volumen) : 0;
    $observacion = $puntos > 0
        ? 'Remision registrada manualmente dentro de 45 minutos'
        : 'Remision registrada manualmente despues de 45 minutos';

    $camposRemision = [
        'id_cliente', 'telefono', 'id_operador', 'folio_remision', 'volumen',
        'hora_inicio', 'hora_fin', 'minutos_colado', 'puntos', 'estatus',
    ];
    $valoresRemision = [
        $id_cliente,
        $telefono,
        $id_operador,
        $folio_remision,
        $volumen,
        $hora_inicio->format('Y-m-d H:i:s'),
        $hora_fin->format('Y-m-d H:i:s'),
        $minutos,
        $puntos,
        'FINALIZADO',
    ];

    if (isset(columnasTabla($pdo, 'tb_remisiones')['rol_captura'])) {
        $camposRemision[] = 'rol_captura';
        $valoresRemision[] = rolCapturaRemisionActual();
    }

    $placeholders = implode(', ', array_fill(0, count($camposRemision), '?'));
    $stmt = $pdo->prepare(
        'INSERT INTO tb_remisiones (' . implode(', ', $camposRemision) . ") VALUES ($placeholders)"
    );
    $stmt->execute($valoresRemision);

    $id_remision = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare(
        "INSERT INTO tb_movimientos_puntos
            (id_cliente, id_remision, tipo, puntos, fecha_vencimiento, observaciones)
         VALUES (?, ?, 'ACUMULACION', ?, ?, ?)"
    );
    $stmt->execute([
        $id_cliente,
        $id_remision,
        $puntos,
        $puntos > 0 ? '2026-12-20' : null,
        $observacion,
    ]);

    $pdo->commit();

    $_SESSION['mensaje_registro_remision_correcto'] =
        "Remision manual registrada en $minutos min. Puntos: $puntos";

    $destino = ($_SESSION['rol'] ?? '') === 'DOSIFICADOR'
        ? '/operador/registrar_remision_manual.php'
        : '/remisiones/index.php';
    header('Location: ' . $URL . $destino);
    exit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensaje_registro_remision_error'] = $e->getMessage();
    header('Location: ' . $URL . '/operador/registrar_remision_manual.php');
    exit();
}
