<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/canjes.php');

verificarSesion();
verificarPermisoAdministrarCanjes();

$redireccion = $URL . '/canjes/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redireccion);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
$tokenSesion = $_SESSION['csrf_canjes'] ?? '';

if ($tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
    $_SESSION['mensaje_canje_error'] = 'La solicitud expiro. Intenta nuevamente.';
    header('Location: ' . $redireccion);
    exit;
}

$idCanje = filter_var($_POST['id_canje'] ?? null, FILTER_VALIDATE_INT);
$motivo = trim($_POST['motivo'] ?? '');

if (!$idCanje || mb_strlen($motivo) < 10 || mb_strlen($motivo) > 500) {
    $_SESSION['mensaje_canje_error'] = 'Ingresa un motivo valido de entre 10 y 500 caracteres.';
    header('Location: ' . $redireccion);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT id_canje, folio, id_cliente, estatus
         FROM tb_canjes
         WHERE id_canje = ?
         FOR UPDATE"
    );
    $stmt->execute([$idCanje]);
    $canje = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$canje) {
        throw new RuntimeException('El canje no existe.');
    }
    if ($canje['estatus'] !== 'CONFIRMADO') {
        throw new RuntimeException('El canje ya fue cancelado.');
    }

    $stmt = $pdo->prepare(
        'SELECT id_premio, cantidad
         FROM tb_canje_detalle
         WHERE id_canje = ?'
    );
    $stmt->execute([$idCanje]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$detalles) {
        throw new RuntimeException('El canje no tiene articulos para devolver.');
    }

    $stmtStock = $pdo->prepare('UPDATE tb_premios SET stock = stock + ? WHERE id_premio = ?');
    foreach ($detalles as $detalle) {
        $stmtStock->execute([(int) $detalle['cantidad'], (int) $detalle['id_premio']]);
        if ($stmtStock->rowCount() !== 1) {
            throw new RuntimeException('No fue posible devolver uno de los articulos al inventario.');
        }
    }

    $stmt = $pdo->prepare(
        'SELECT puntos_aplicados, fecha_vencimiento
         FROM tb_canje_aplicaciones
         WHERE id_canje = ?
         ORDER BY id_aplicacion
         FOR UPDATE'
    );
    $stmt->execute([$idCanje]);
    $aplicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$aplicaciones) {
        throw new RuntimeException('No se encontraron los movimientos de puntos del canje.');
    }

    $stmtMovimiento = $pdo->prepare(
        "INSERT INTO tb_movimientos_puntos
            (id_cliente, id_remision, tipo, puntos, fecha_movimiento, fecha_vencimiento, observaciones)
         VALUES (?, NULL, 'AJUSTE', ?, NOW(), ?, ?)"
    );
    foreach ($aplicaciones as $aplicacion) {
        $stmtMovimiento->execute([
            (int) $canje['id_cliente'],
            (float) $aplicacion['puntos_aplicados'],
            $aplicacion['fecha_vencimiento'],
            'Cancelacion canje ' . $canje['folio'],
        ]);
    }

    $stmt = $pdo->prepare(
        "UPDATE tb_canjes
         SET estatus = 'CANCELADO', motivo_cancelacion = ?,
             id_usuario_cancelacion = ?, fecha_cancelacion = NOW()
         WHERE id_canje = ? AND estatus = 'CONFIRMADO'"
    );
    $stmt->execute([
        $motivo,
        (int) ($_SESSION['id_usuario_login'] ?? 0),
        $idCanje,
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('El canje cambio de estado antes de completar la cancelacion.');
    }

    $pdo->commit();
    $_SESSION['csrf_canjes'] = bin2hex(random_bytes(32));
    $_SESSION['mensaje_canje_correcto'] = 'Canje ' . $canje['folio'] . ' cancelado. Los puntos y articulos fueron devueltos.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    $_SESSION['mensaje_canje_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'No fue posible cancelar el canje.';
}

header('Location: ' . $redireccion);
exit;
