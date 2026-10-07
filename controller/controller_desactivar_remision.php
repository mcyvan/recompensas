<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/remisiones.php');

verificarSesion();
verificarPermisoRemisiones();
verificarPermisoAdministrarRemisiones();

$redireccion = $URL . '/remisiones/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redireccion);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
$tokenSesion = $_SESSION['csrf_remisiones'] ?? '';

if ($tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
    $_SESSION['mensaje_remision_error'] = 'La solicitud expiro. Intenta nuevamente.';
    header('Location: ' . $redireccion);
    exit;
}

$idRemision = filter_var($_POST['id_remision'] ?? null, FILTER_VALIDATE_INT);
$motivo = trim($_POST['motivo'] ?? '');

if (!$idRemision || mb_strlen($motivo) < 10 || mb_strlen($motivo) > 500) {
    $_SESSION['mensaje_remision_error'] = 'Ingresa un motivo valido de entre 10 y 500 caracteres.';
    header('Location: ' . $redireccion);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT id_remision, id_cliente, folio_remision, estatus
         FROM tb_remisiones
         WHERE id_remision = ?
         FOR UPDATE"
    );
    $stmt->execute([$idRemision]);
    $remision = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$remision) {
        throw new RuntimeException('La remision no existe.');
    }

    if ($remision['estatus'] === 'CANCELADO') {
        throw new RuntimeException('La remision ya esta cancelada.');
    }

    $stmt = $pdo->prepare(
        "SELECT id_movimiento
         FROM tb_movimientos_puntos
         WHERE id_remision = ?
         FOR UPDATE"
    );
    $stmt->execute([$idRemision]);
    $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($movimiento) {
        $stmt = $pdo->prepare(
            "UPDATE tb_movimientos_puntos
             SET tipo = 'AJUSTE', puntos = 0, fecha_vencimiento = NULL,
                 observaciones = ?
             WHERE id_movimiento = ?"
        );
        $stmt->execute([
            'Cancelacion remision ' . $remision['folio_remision'] . ': ' . $motivo,
            (int) $movimiento['id_movimiento'],
        ]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tb_movimientos_puntos
                (id_cliente, id_remision, tipo, puntos, fecha_movimiento, fecha_vencimiento, observaciones)
             VALUES (?, ?, 'AJUSTE', 0, NOW(), NULL, ?)"
        );
        $stmt->execute([
            (int) $remision['id_cliente'],
            $idRemision,
            'Cancelacion remision ' . $remision['folio_remision'] . ': ' . $motivo,
        ]);
    }

    $stmt = $pdo->prepare(
        "UPDATE tb_remisiones
         SET estatus = 'CANCELADO', puntos = 0
         WHERE id_remision = ?"
    );
    $stmt->execute([$idRemision]);

    $pdo->commit();
    $_SESSION['csrf_remisiones'] = bin2hex(random_bytes(32));
    $_SESSION['mensaje_remision_correcto'] = 'Remision ' . $remision['folio_remision'] . ' desactivada correctamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    $_SESSION['mensaje_remision_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'No fue posible desactivar la remision.';
}

header('Location: ' . $redireccion);
exit;
