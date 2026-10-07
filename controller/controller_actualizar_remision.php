<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/remisiones.php');
require_once('../app/functions/consultas_puntos.php');
require_once('../app/functions/conciliacion_ventas.php');
require_once('../app/functions/bitacora.php');

verificarSesion();
verificarPermisoRemisiones();
verificarPermisoAdministrarRemisiones();

$redireccionListado = $URL . '/remisiones/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redireccionListado);
    exit;
}

$idRemision = filter_var($_POST['id_remision'] ?? null, FILTER_VALIDATE_INT);
$redireccionEditar = $idRemision
    ? $URL . '/remisiones/editar.php?id=' . $idRemision
    : $redireccionListado;

$token = $_POST['csrf_token'] ?? '';
$tokenSesion = $_SESSION['csrf_remisiones'] ?? '';

if ($tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
    $_SESSION['mensaje_remision_error'] = 'La solicitud expiro. Intenta nuevamente.';
    header('Location: ' . $redireccionEditar);
    exit;
}

$folio = strtoupper(trim($_POST['folio_remision'] ?? ''));
$telefono = preg_replace('/[^0-9]/', '', $_POST['telefono'] ?? '');
$volumen = (float) ($_POST['volumen'] ?? 0);
$idOperador = filter_var($_POST['id_operador'] ?? null, FILTER_VALIDATE_INT);
$estatus = strtoupper(trim($_POST['estatus'] ?? ''));
$horaInicioTexto = trim($_POST['hora_inicio'] ?? '');
$horaFinTexto = trim($_POST['hora_fin'] ?? '');
$plantaCrm = strtoupper(trim($_POST['planta_crm'] ?? ''));
$plantaCrm = $plantaCrm !== '' ? $plantaCrm : null;
$vendedorPosteado = strtoupper(trim($_POST['vendedor_crm'] ?? ''));

try {
    if (!$idRemision) {
        throw new RuntimeException('Remision invalida.');
    }

    if (!preg_match('/^[A-Z0-9][A-Z0-9-]{2,39}$/', $folio)) {
        throw new RuntimeException('Formato de folio invalido.');
    }

    // Vacio solo se permite en una remision que todavia no tiene cliente ligado (se valida mas abajo).
    if ($telefono !== '' && strlen($telefono) !== 10) {
        throw new RuntimeException('El telefono debe tener 10 digitos.');
    }

    if ($volumen < 1 || $volumen > 8 || fmod($volumen, 0.5) != 0.0) {
        throw new RuntimeException('El volumen debe estar entre 1 y 8 m3, en incrementos de .5.');
    }

    if (!$idOperador) {
        throw new RuntimeException('Selecciona un chofer valido.');
    }

    if (!in_array($estatus, ['EN PROCESO', 'FINALIZADO', 'CANCELADO'], true)) {
        throw new RuntimeException('Estatus invalido.');
    }

    $horaInicio = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $horaInicioTexto);
    if (!$horaInicio) {
        throw new RuntimeException('Fecha de inicio invalida.');
    }

    $horaFin = null;
    if ($estatus === 'FINALIZADO') {
        $horaFin = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $horaFinTexto);
        if (!$horaFin) {
            throw new RuntimeException('Fecha de fin invalida.');
        }
        if ($horaFin < $horaInicio) {
            throw new RuntimeException('La fecha de fin no puede ser menor que la fecha de inicio.');
        }
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT id_remision, id_cliente, folio_remision, estatus, planta_crm, telefono,
                vendedor_crm, id_operador, volumen, hora_inicio, hora_fin
         FROM tb_remisiones
         WHERE id_remision = ?
         FOR UPDATE"
    );
    $stmt->execute([$idRemision]);
    $remisionActual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$remisionActual) {
        throw new RuntimeException('La remision no existe.');
    }

    if ($plantaCrm !== null && $plantaCrm !== $remisionActual['planta_crm']) {
        $plantasValidas = obtenerPlantasConciliacion($pdo);
        if (!in_array($plantaCrm, $plantasValidas, true)) {
            throw new RuntimeException('Planta invalida.');
        }
    }

    $stmt = $pdo->prepare(
        "SELECT id_remision
         FROM tb_remisiones
         WHERE folio_remision = ? AND id_remision <> ?
         LIMIT 1"
    );
    $stmt->execute([$folio, $idRemision]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Ya existe otra remision con ese folio.');
    }

    $stmt = $pdo->prepare(
        "SELECT u.id_usuario
         FROM tb_usuarios u
         INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
         INNER JOIN tb_roles r ON r.id_rol = ud.id_rol
         WHERE u.id_usuario = ? AND u.estatus = 1 AND r.rol = 'OPERADOR'
         LIMIT 1"
    );
    $stmt->execute([$idOperador]);
    if (!$stmt->fetch()) {
        throw new RuntimeException('El chofer seleccionado no existe o esta inactivo.');
    }

    if ($telefono === '' && !empty($remisionActual['id_cliente'])) {
        throw new RuntimeException('El telefono debe tener 10 digitos.');
    }

    // Una remision registrada sin cliente puede seguir sin cliente hasta que se conozca su telefono.
    $idCliente = $telefono !== '' ? (int) obtenerIdClienteTelefono($telefono) : null;
    $ligandoCliente = $idCliente !== null && empty($remisionActual['id_cliente']);

    // Mientras la remision no tenga cliente, el vendedor se asigna a mano para
    // poder filtrar y medir quien no esta dando de alta a sus clientes. Vacio =
    // no cambiar; una vez ligada, manda el vendedor del cliente.
    $vendedorCrmNuevo = $remisionActual['vendedor_crm'];
    if ($vendedorPosteado !== '' && empty($remisionActual['id_cliente'])) {
        $vendedoresValidos = array_map('strtoupper', array_values(nombresVendedoresPorClave($pdo)));
        if (!in_array($vendedorPosteado, $vendedoresValidos, true)) {
            throw new RuntimeException('Vendedor invalido.');
        }
        $vendedorCrmNuevo = $vendedorPosteado;
    }

    $minutosColado = null;
    $puntosNuevos = null;
    $horaFinSql = null;

    if ($estatus === 'FINALIZADO') {
        $minutosColado = (int) floor(($horaFin->getTimestamp() - $horaInicio->getTimestamp()) / 60);
        $puntosNuevos = ($minutosColado <= 45 && $idCliente !== null) ? obtenerPuntos($volumen) : 0.0;
        $horaFinSql = $horaFin->format('Y-m-d H:i:s');
    } elseif ($estatus === 'CANCELADO') {
        $puntosNuevos = 0.0;
    }

    $stmt = $pdo->prepare(
        "UPDATE tb_remisiones
         SET id_cliente = ?, telefono = ?, id_operador = ?, folio_remision = ?,
             volumen = ?, hora_inicio = ?, hora_fin = ?, minutos_colado = ?,
             puntos = ?, estatus = ?, planta_crm = ?, vendedor_crm = ?
         WHERE id_remision = ?"
    );
    $stmt->execute([
        $idCliente,
        $telefono,
        $idOperador,
        $folio,
        $volumen,
        $horaInicio->format('Y-m-d H:i:s'),
        $horaFinSql,
        $minutosColado,
        $puntosNuevos,
        $estatus,
        $plantaCrm,
        $vendedorCrmNuevo,
        $idRemision,
    ]);

    if ($ligandoCliente) {
        $stmt = $pdo->prepare(
            "UPDATE tb_remisiones SET id_usuario_liga = ?, fecha_liga = NOW() WHERE id_remision = ?"
        );
        $stmt->execute([(int) ($_SESSION['id_usuario_login'] ?? 0), $idRemision]);
    }

    $formatoBitacora = static fn($valor) => $valor === null ? null : number_format((float) $valor, 2, '.', '');
    registrarBitacoraCambios(
        $pdo,
        'REMISION',
        $idRemision,
        $ligandoCliente ? 'LIGAR_CLIENTE' : 'ACTUALIZAR',
        [
            'id_cliente' => $remisionActual['id_cliente'],
            'telefono' => $remisionActual['telefono'],
            'id_operador' => $remisionActual['id_operador'],
            'folio_remision' => $remisionActual['folio_remision'],
            'volumen' => $formatoBitacora($remisionActual['volumen']),
            'estatus' => $remisionActual['estatus'],
            'planta_crm' => $remisionActual['planta_crm'],
            'vendedor_crm' => $remisionActual['vendedor_crm'],
            'hora_inicio' => substr((string) $remisionActual['hora_inicio'], 0, 16),
            'hora_fin' => $remisionActual['hora_fin'] ? substr((string) $remisionActual['hora_fin'], 0, 16) : null,
        ],
        [
            'id_cliente' => $idCliente,
            'telefono' => $telefono,
            'id_operador' => $idOperador,
            'folio_remision' => $folio,
            'volumen' => $formatoBitacora($volumen),
            'estatus' => $estatus,
            'planta_crm' => $plantaCrm,
            'vendedor_crm' => $vendedorCrmNuevo,
            'hora_inicio' => $horaInicio->format('Y-m-d H:i'),
            'hora_fin' => $horaFin ? $horaFin->format('Y-m-d H:i') : null,
        ]
    );

    $puntosObjetivo = $estatus === 'FINALIZADO' ? round((float) $puntosNuevos, 2) : 0.0;

    $stmt = $pdo->prepare(
        "SELECT id_movimiento
         FROM tb_movimientos_puntos
         WHERE id_remision = ?
         FOR UPDATE"
    );
    $stmt->execute([$idRemision]);
    $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);

    $tipoMovimiento = $estatus === 'FINALIZADO' ? 'ACUMULACION' : 'AJUSTE';
    $fechaVencimiento = $puntosObjetivo > 0 ? '2026-12-20' : null;
    $observacionMovimiento = $estatus === 'FINALIZADO'
        ? 'Correccion remision ' . $folio
        : 'Remision ' . $folio . ' sin acumulacion de puntos';

    // Sin cliente no hay a quien abonar puntos; el movimiento se genera cuando se ligue uno.
    if ($idCliente !== null && $movimiento) {
        $stmt = $pdo->prepare(
            "UPDATE tb_movimientos_puntos
             SET id_cliente = ?, tipo = ?, puntos = ?, fecha_vencimiento = ?,
                 observaciones = ?
             WHERE id_movimiento = ?"
        );
        $stmt->execute([
            $idCliente,
            $tipoMovimiento,
            $puntosObjetivo,
            $fechaVencimiento,
            $observacionMovimiento,
            (int) $movimiento['id_movimiento'],
        ]);
    } elseif ($idCliente !== null && ($puntosObjetivo != 0.0 || $estatus === 'CANCELADO')) {
        $stmt = $pdo->prepare(
            "INSERT INTO tb_movimientos_puntos
                (id_cliente, id_remision, tipo, puntos, fecha_movimiento, fecha_vencimiento, observaciones)
             VALUES (?, ?, ?, ?, NOW(), ?, ?)"
        );
        $stmt->execute([
            $idCliente,
            $idRemision,
            $tipoMovimiento,
            $puntosObjetivo,
            $fechaVencimiento,
            $observacionMovimiento,
        ]);
    }

    $pdo->commit();
    $_SESSION['csrf_remisiones'] = bin2hex(random_bytes(32));
    $_SESSION['mensaje_remision_correcto'] = 'Remision ' . $folio . ' actualizada correctamente.';
    header('Location: ' . $redireccionListado);
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    $_SESSION['mensaje_remision_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'No fue posible actualizar la remision.';

    header('Location: ' . $redireccionEditar);
    exit;
}
