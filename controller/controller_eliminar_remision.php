<?php

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/functions/auth.php';

verificarSesion();

$redireccion = $URL . '/remisiones/index.php?tab=remisiones';

if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    exit('Solo el administrador puede eliminar remisiones.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redireccion);
    exit;
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_remisiones']) || !hash_equals($_SESSION['csrf_remisiones'], $token)) {
    $_SESSION['mensaje_remision_error'] = 'La solicitud expiro. Intenta nuevamente.';
    header('Location: ' . $redireccion);
    exit;
}

$idRemision = filter_var($_POST['id_remision'] ?? null, FILTER_VALIDATE_INT);
$folioConfirmacion = strtoupper(trim((string) ($_POST['folio_confirmacion'] ?? '')));

if (!$idRemision || $folioConfirmacion === '') {
    $_SESSION['mensaje_remision_error'] = 'No se recibio una remision valida para eliminar.';
    header('Location: ' . $redireccion);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id_remision, folio_remision, estatus
         FROM tb_remisiones
         WHERE id_remision = ?
         FOR UPDATE'
    );
    $stmt->execute([$idRemision]);
    $remision = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$remision) {
        throw new RuntimeException('La remision ya no existe.');
    }
    if ($remision['estatus'] !== 'CANCELADO') {
        throw new RuntimeException('Solo se pueden eliminar remisiones canceladas.');
    }
    if (strtoupper(trim((string) $remision['folio_remision'])) !== $folioConfirmacion) {
        throw new RuntimeException('El folio de confirmacion no coincide.');
    }

    $tablaAlertasExiste = $pdo->query(
        "SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_alertas_remisiones'"
    )->fetchColumn();
    if ((int) $tablaAlertasExiste > 0) {
        $stmt = $pdo->prepare('DELETE FROM tb_alertas_remisiones WHERE id_remision = ?');
        $stmt->execute([$idRemision]);
    }

    $stmt = $pdo->prepare('DELETE FROM tb_movimientos_puntos WHERE id_remision = ?');
    $stmt->execute([$idRemision]);

    $stmt = $pdo->prepare("DELETE FROM tb_remisiones WHERE id_remision = ? AND estatus = 'CANCELADO'");
    $stmt->execute([$idRemision]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('La remision no pudo ser eliminada.');
    }

    $pdo->commit();
    $_SESSION['csrf_remisiones'] = bin2hex(random_bytes(32));
    $_SESSION['mensaje_remision_correcto'] = 'Remision ' . $remision['folio_remision'] . ' eliminada definitivamente.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Eliminar remision: ' . $e->getMessage());
    $_SESSION['mensaje_remision_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'No fue posible eliminar la remision.';
}

header('Location: ' . $redireccion);
exit;
