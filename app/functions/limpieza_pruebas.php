<?php

function verificarPermisoLimpiezaPruebas(): void
{
    if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
        http_response_code(403);
        exit('No tienes permiso para limpiar datos de prueba.');
    }
}

function normalizarTelefonosLimpieza(string $texto): array
{
    $partes = preg_split('/[\s,;]+/', $texto, -1, PREG_SPLIT_NO_EMPTY);
    $telefonos = [];

    foreach ($partes as $parte) {
        $telefono = preg_replace('/[^0-9]/', '', $parte);
        if (strlen($telefono) === 10) {
            $telefonos[$telefono] = $telefono;
        }
    }

    return array_values($telefonos);
}

function obtenerVistaPreviaLimpiezaPruebas(PDO $pdo, array $telefonos = [], array $idsClientes = []): array
{
    $clientes = [];

    if ($telefonos) {
        $marcadores = implode(',', array_fill(0, count($telefonos), '?'));
        $stmt = $pdo->prepare(
            "SELECT id_cliente, nombres, apellido_p, apellido_m, telefono, correo, estatus
             FROM tb_clientes
             WHERE telefono IN ($marcadores)
             ORDER BY id_cliente"
        );
        $stmt->execute($telefonos);
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($idsClientes) {
        $idsClientes = array_values(array_unique(array_map('intval', $idsClientes)));
        $idsClientes = array_filter($idsClientes, fn($id) => $id > 0);
        if ($idsClientes) {
            $marcadores = implode(',', array_fill(0, count($idsClientes), '?'));
            $stmt = $pdo->prepare(
                "SELECT id_cliente, nombres, apellido_p, apellido_m, telefono, correo, estatus
                 FROM tb_clientes
                 WHERE id_cliente IN ($marcadores)
                 ORDER BY id_cliente"
            );
            $stmt->execute($idsClientes);
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    $ids = array_map('intval', array_column($clientes, 'id_cliente'));

    $resumen = [
        'clientes' => count($clientes),
        'remisiones' => 0,
        'movimientos' => 0,
        'canjes' => 0,
        'detalles_canje' => 0,
        'aplicaciones_canje' => 0,
        'stock_a_devolver' => [],
    ];

    if (!$ids) {
        return [
            'clientes' => [],
            'resumen' => $resumen,
        ];
    }

    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $consultasConteo = [
        'remisiones' => "SELECT COUNT(*) FROM tb_remisiones WHERE id_cliente IN ($marcadores)",
        'movimientos' => "SELECT COUNT(*) FROM tb_movimientos_puntos WHERE id_cliente IN ($marcadores)",
        'canjes' => "SELECT COUNT(*) FROM tb_canjes WHERE id_cliente IN ($marcadores)",
    ];

    foreach ($consultasConteo as $llave => $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $resumen[$llave] = (int) $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare("SELECT id_canje FROM tb_canjes WHERE id_cliente IN ($marcadores)");
    $stmt->execute($ids);
    $idsCanjes = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    if ($idsCanjes) {
        $marcadoresCanjes = implode(',', array_fill(0, count($idsCanjes), '?'));

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_canje_detalle WHERE id_canje IN ($marcadoresCanjes)");
        $stmt->execute($idsCanjes);
        $resumen['detalles_canje'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_canje_aplicaciones WHERE id_canje IN ($marcadoresCanjes)");
        $stmt->execute($idsCanjes);
        $resumen['aplicaciones_canje'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT d.id_premio, d.premio, SUM(d.cantidad) AS cantidad
             FROM tb_canje_detalle d
             INNER JOIN tb_canjes c ON c.id_canje = d.id_canje
             WHERE d.id_canje IN ($marcadoresCanjes)
               AND c.estatus = 'CONFIRMADO'
             GROUP BY d.id_premio, d.premio
             ORDER BY d.premio"
        );
        $stmt->execute($idsCanjes);
        $resumen['stock_a_devolver'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return [
        'clientes' => $clientes,
        'resumen' => $resumen,
    ];
}

function ejecutarLimpiezaPruebas(PDO $pdo, array $idsClientes): array
{
    $vista = obtenerVistaPreviaLimpiezaPruebas($pdo, [], $idsClientes);
    $idsClientes = array_map('intval', array_column($vista['clientes'], 'id_cliente'));

    if (!$idsClientes) {
        throw new RuntimeException('No se encontraron clientes para limpiar.');
    }

    $marcadoresClientes = implode(',', array_fill(0, count($idsClientes), '?'));

    $stmt = $pdo->prepare("SELECT id_canje FROM tb_canjes WHERE id_cliente IN ($marcadoresClientes)");
    $stmt->execute($idsClientes);
    $idsCanjes = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    if ($idsCanjes) {
        $marcadoresCanjes = implode(',', array_fill(0, count($idsCanjes), '?'));

        $stmt = $pdo->prepare(
            "SELECT d.id_premio, SUM(d.cantidad) AS cantidad
             FROM tb_canje_detalle d
             INNER JOIN tb_canjes c ON c.id_canje = d.id_canje
             WHERE d.id_canje IN ($marcadoresCanjes)
               AND c.estatus = 'CONFIRMADO'
             GROUP BY d.id_premio"
        );
        $stmt->execute($idsCanjes);
        $stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtStock = $pdo->prepare('UPDATE tb_premios SET stock = COALESCE(stock, 0) + ? WHERE id_premio = ?');
        foreach ($stock as $fila) {
            $stmtStock->execute([(int) $fila['cantidad'], (int) $fila['id_premio']]);
        }

        $stmt = $pdo->prepare("DELETE FROM tb_canje_aplicaciones WHERE id_canje IN ($marcadoresCanjes)");
        $stmt->execute($idsCanjes);

        $stmt = $pdo->prepare("DELETE FROM tb_canje_detalle WHERE id_canje IN ($marcadoresCanjes)");
        $stmt->execute($idsCanjes);

        $stmt = $pdo->prepare("DELETE FROM tb_canjes WHERE id_canje IN ($marcadoresCanjes)");
        $stmt->execute($idsCanjes);
    }

    $stmt = $pdo->prepare("DELETE FROM tb_movimientos_puntos WHERE id_cliente IN ($marcadoresClientes)");
    $stmt->execute($idsClientes);

    $stmt = $pdo->prepare("DELETE FROM tb_remisiones WHERE id_cliente IN ($marcadoresClientes)");
    $stmt->execute($idsClientes);

    $stmt = $pdo->prepare("DELETE FROM tb_clientes WHERE id_cliente IN ($marcadoresClientes)");
    $stmt->execute($idsClientes);

    return $vista['resumen'];
}
