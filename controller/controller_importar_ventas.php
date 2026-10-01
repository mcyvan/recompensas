<?php

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/functions/auth.php';
require_once __DIR__ . '/../app/functions/conciliacion_ventas.php';

verificarSesion();
verificarPermisoConciliacionVentas();

$redireccion = $URL . '/conciliacion/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redireccion);
    exit;
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_conciliacion_ventas']) || !hash_equals($_SESSION['csrf_conciliacion_ventas'], $token)) {
    $_SESSION['mensaje_conciliacion_error'] = 'La sesion del formulario vencio. Intenta nuevamente.';
    header('Location: ' . $redireccion);
    exit;
}

try {
    $archivos = $_FILES['archivos_ventas'] ?? null;
    if (!$archivos || !is_array($archivos['name'] ?? null)) {
        throw new RuntimeException('Selecciona al menos un archivo XLSX.');
    }

    $ventas = [];
    $nombres = [];
    $huellas = [];

    foreach ($archivos['name'] as $indice => $nombreOriginal) {
        $error = (int) ($archivos['error'][$indice] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo recibir el archivo ' . basename((string) $nombreOriginal) . '.');
        }

        $nombre = basename((string) $nombreOriginal);
        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $tamano = (int) ($archivos['size'][$indice] ?? 0);
        $temporal = (string) ($archivos['tmp_name'][$indice] ?? '');

        if ($extension !== 'xlsx') {
            throw new RuntimeException("$nombre no es un archivo XLSX.");
        }
        if ($tamano <= 0 || $tamano > 20 * 1024 * 1024) {
            throw new RuntimeException("$nombre esta vacio o supera el limite de 20 MB.");
        }
        if (!is_uploaded_file($temporal)) {
            throw new RuntimeException("No se pudo validar la carga de $nombre.");
        }

        foreach (leerVentasXlsx($temporal) as $venta) {
            $huella = hash('sha256', implode('|', [
                $venta['fecha'], $venta['remision'], $venta['pedido'], $venta['factura'],
                $venta['planta'], $venta['vendedor'], $venta['cliente'], $venta['articulo'],
                (string) $venta['cantidad'], (string) $venta['precio_unitario'],
            ]));
            if (isset($huellas[$huella])) {
                continue;
            }
            $huellas[$huella] = true;
            $ventas[] = $venta;
        }
        $nombres[] = $nombre;
    }

    if (!$ventas || !$nombres) {
        throw new RuntimeException('No se encontraron ventas validas en los archivos seleccionados.');
    }

    $fechas = array_values(array_unique(array_column($ventas, 'fecha')));
    sort($fechas);
    $fechaInicio = $fechas[0];
    $fechaFin = $fechas[count($fechas) - 1];

    $pdo->beginTransaction();

    $insertarCarga = $pdo->prepare(
        'INSERT INTO tb_cargas_ventas
            (archivos, fecha_inicio, fecha_fin, dias_reemplazados, filas_importadas, id_usuario, fecha_carga)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    );
    $insertarCarga->execute([
        implode(', ', $nombres),
        $fechaInicio,
        $fechaFin,
        count($fechas),
        count($ventas),
        (int) ($_SESSION['id_usuario_login'] ?? 0) ?: null,
    ]);
    $idCarga = (int) $pdo->lastInsertId();

    foreach (array_chunk($fechas, 100) as $grupoFechas) {
        $marcadores = implode(',', array_fill(0, count($grupoFechas), '?'));
        $eliminar = $pdo->prepare("DELETE FROM tb_ventas_empresa WHERE fecha IN ($marcadores)");
        $eliminar->execute($grupoFechas);
    }

    $insertar = $pdo->prepare(
        'INSERT INTO tb_ventas_empresa
            (id_carga, fecha, remision, pedido, factura, planta, vendedor, cliente,
             articulo, cantidad, precio_unitario, es_concreto, fecha_importacion)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );

    foreach ($ventas as $venta) {
        $insertar->execute([
            $idCarga,
            $venta['fecha'],
            $venta['remision'],
            $venta['pedido'] ?: null,
            $venta['factura'] ?: null,
            $venta['planta'] ?: null,
            $venta['vendedor'] ?: null,
            $venta['cliente'] ?: null,
            $venta['articulo'],
            $venta['cantidad'],
            $venta['precio_unitario'],
            $venta['es_concreto'],
        ]);
    }

    $pdo->commit();
    $_SESSION['mensaje_conciliacion_correcto'] = sprintf(
        'Carga completada: %d filas de %d archivo(s). Se sustituyeron %d dia(s), del %s al %s.',
        count($ventas),
        count($nombres),
        count($fechas),
        date('d/m/Y', strtotime($fechaInicio)),
        date('d/m/Y', strtotime($fechaFin))
    );
    $_SESSION['csrf_conciliacion_ventas'] = bin2hex(random_bytes(32));
    header(
        'Location: ' . $URL . '/remisiones/index.php?tab=conciliacion'
        . '&conc_fecha_inicio=' . urlencode($fechaInicio)
        . '&conc_fecha_fin=' . urlencode($fechaFin)
    );
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Importacion de ventas: ' . $e->getMessage());
    $_SESSION['mensaje_conciliacion_error'] = $e->getMessage();
    header('Location: ' . $redireccion);
    exit;
}
