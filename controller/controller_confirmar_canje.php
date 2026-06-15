<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/canjes.php');

verificarSesion();
verificarPermisoCanjes();

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

$telefono = preg_replace('/[^0-9]/', '', $_POST['telefono'] ?? '');
$itemsRecibidos = json_decode($_POST['items'] ?? '', true);

if (strlen($telefono) !== 10 || !is_array($itemsRecibidos) || !$itemsRecibidos) {
    $_SESSION['mensaje_canje_error'] = 'Los datos del canje no son validos.';
    header('Location: ' . $redireccion);
    exit;
}

$cantidades = [];
foreach ($itemsRecibidos as $item) {
    $idPremio = filter_var($item['id_premio'] ?? null, FILTER_VALIDATE_INT);
    $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);

    if (!$idPremio || !$cantidad || $cantidad < 1 || $cantidad > 100) {
        $_SESSION['mensaje_canje_error'] = 'Hay un premio o cantidad no valida.';
        header('Location: ' . $redireccion);
        exit;
    }

    $cantidades[$idPremio] = ($cantidades[$idPremio] ?? 0) + $cantidad;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id_cliente, nombres, apellido_p
         FROM tb_clientes
         WHERE telefono = ? AND estatus = 1
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->execute([$telefono]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        throw new RuntimeException('El cliente ya no esta disponible.');
    }

    $idCliente = (int) $cliente['id_cliente'];
    $saldoAntes = obtenerSaldoCliente($pdo, $idCliente);
    $premios = [];
    $totalPuntos = 0.0;

    $stmtPremio = $pdo->prepare(
        'SELECT id_premio, premio, puntos_requeridos, stock
         FROM tb_premios
         WHERE id_premio = ? AND activo = 1
         FOR UPDATE'
    );

    foreach ($cantidades as $idPremio => $cantidad) {
        $stmtPremio->execute([$idPremio]);
        $premio = $stmtPremio->fetch(PDO::FETCH_ASSOC);

        if (!$premio) {
            throw new RuntimeException('Uno de los premios ya no esta disponible.');
        }
        if ((int) $premio['stock'] < $cantidad) {
            throw new RuntimeException('No hay existencia suficiente de ' . $premio['premio'] . '.');
        }

        $premio['cantidad'] = $cantidad;
        $premio['subtotal'] = round((float) $premio['puntos_requeridos'] * $cantidad, 2);
        $totalPuntos += $premio['subtotal'];
        $premios[] = $premio;
    }

    $totalPuntos = round($totalPuntos, 2);
    if ($totalPuntos <= 0 || $totalPuntos > $saldoAntes) {
        throw new RuntimeException('El cliente no tiene puntos suficientes para este canje.');
    }

    $folio = generarFolioCanje();
    $saldoDespues = round($saldoAntes - $totalPuntos, 2);
    $stmt = $pdo->prepare(
        "INSERT INTO tb_canjes
            (folio, id_cliente, total_puntos, saldo_antes, saldo_despues, estatus, id_usuario, fecha_canje)
         VALUES (?, ?, ?, ?, ?, 'CONFIRMADO', ?, NOW())"
    );
    $stmt->execute([
        $folio,
        $idCliente,
        $totalPuntos,
        $saldoAntes,
        $saldoDespues,
        (int) ($_SESSION['id_usuario_login'] ?? 0),
    ]);
    $idCanje = (int) $pdo->lastInsertId();

    $stmtDetalle = $pdo->prepare(
        'INSERT INTO tb_canje_detalle
            (id_canje, id_premio, premio, cantidad, puntos_unitarios, puntos_total)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmtStock = $pdo->prepare(
        'UPDATE tb_premios SET stock = stock - ? WHERE id_premio = ? AND stock >= ?'
    );

    foreach ($premios as $premio) {
        $stmtDetalle->execute([
            $idCanje,
            $premio['id_premio'],
            $premio['premio'],
            $premio['cantidad'],
            $premio['puntos_requeridos'],
            $premio['subtotal'],
        ]);
        $stmtStock->execute([$premio['cantidad'], $premio['id_premio'], $premio['cantidad']]);

        if ($stmtStock->rowCount() !== 1) {
            throw new RuntimeException('Cambio la existencia de uno de los premios. Intenta nuevamente.');
        }
    }

    $stmt = $pdo->prepare(
        "SELECT m.id_movimiento, m.puntos, m.fecha_vencimiento,
                COALESCE((
                    SELECT SUM(a.puntos_aplicados)
                    FROM tb_canje_aplicaciones a
                    WHERE a.id_movimiento_acumulacion = m.id_movimiento
                ), 0) AS puntos_consumidos
         FROM tb_movimientos_puntos m
         WHERE m.id_cliente = ?
           AND m.tipo IN ('ACUMULACION', 'AJUSTE')
           AND m.puntos > 0
           AND (m.fecha_vencimiento IS NULL OR m.fecha_vencimiento >= CURRENT_DATE)
         ORDER BY COALESCE(m.fecha_vencimiento, '9999-12-31'), m.id_movimiento
         FOR UPDATE"
    );
    $stmt->execute([$idCliente]);
    $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $restante = $totalPuntos;
    $stmtMovimiento = $pdo->prepare(
        "INSERT INTO tb_movimientos_puntos
            (id_cliente, id_remision, tipo, puntos, fecha_movimiento, fecha_vencimiento, observaciones)
         VALUES (?, NULL, 'CANJE', ?, NOW(), ?, ?)"
    );
    $stmtAplicacion = $pdo->prepare(
        'INSERT INTO tb_canje_aplicaciones
            (id_canje, id_movimiento_acumulacion, id_movimiento_canje, puntos_aplicados, fecha_vencimiento)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($lotes as $lote) {
        if ($restante <= 0) break;
        $disponible = round((float) $lote['puntos'] - (float) $lote['puntos_consumidos'], 2);
        $aplicar = min($disponible, $restante);
        if ($aplicar <= 0) continue;

        $stmtMovimiento->execute([
            $idCliente,
            -$aplicar,
            $lote['fecha_vencimiento'],
            'Canje ' . $folio,
        ]);
        $idMovimientoCanje = (int) $pdo->lastInsertId();
        $stmtAplicacion->execute([
            $idCanje,
            $lote['id_movimiento'],
            $idMovimientoCanje,
            $aplicar,
            $lote['fecha_vencimiento'],
        ]);
        $restante = round($restante - $aplicar, 2);
    }

    if ($restante > 0) {
        throw new RuntimeException('No fue posible aplicar los puntos disponibles por fecha de vencimiento.');
    }

    $pdo->commit();
    $_SESSION['csrf_canjes'] = bin2hex(random_bytes(32));
    $_SESSION['mensaje_canje_correcto'] = 'Canje registrado correctamente.';
    $_SESSION['canje_realizado'] = ['folio' => $folio, 'saldo_despues' => $saldoDespues];
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    $_SESSION['mensaje_canje_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'No fue posible registrar el canje.';
}

header('Location: ' . $redireccion);
exit;
