<?php

function verificarPermisoCanjes(): void
{
    $rolesPermitidos = ['ADMINISTRADOR', 'ADMINISTRACION', 'CANJE', 'ADMIN CANJE'];

    if (!in_array($_SESSION['rol'] ?? '', $rolesPermitidos, true)) {
        http_response_code(403);
        exit('No tienes permiso para acceder al modulo de canjes.');
    }
}

function puedeAdministrarCanjes(): bool
{
    return in_array($_SESSION['rol'] ?? '', ['ADMINISTRADOR', 'ADMIN CANJE'], true);
}

function verificarPermisoAdministrarCanjes(): void
{
    if (!puedeAdministrarCanjes()) {
        http_response_code(403);
        exit('No tienes permiso para cancelar canjes.');
    }
}

function obtenerSaldoCliente(PDO $pdo, int $idCliente): float
{
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(puntos), 0)
         FROM tb_movimientos_puntos
         WHERE id_cliente = ?
           AND (fecha_vencimiento IS NULL OR fecha_vencimiento >= CURRENT_DATE)"
    );
    $stmt->execute([$idCliente]);

    return round((float) $stmt->fetchColumn(), 2);
}

function obtenerClienteCanjePorTelefono(PDO $pdo, string $telefono): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id_cliente, nombres, apellido_p, apellido_m, telefono
         FROM tb_clientes
         WHERE telefono = ? AND estatus = 1
         LIMIT 1'
    );
    $stmt->execute([$telefono]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    return $cliente ?: null;
}

function obtenerPremiosDisponiblesCanje(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT p.id_premio, p.premio, p.descripcion, p.puntos_requeridos,
                p.stock, p.imagen, c.categoria
         FROM tb_premios p
         INNER JOIN tb_categorias c ON c.id_categoria = p.id_categoria
         WHERE p.activo = 1 AND p.stock > 0
         ORDER BY p.puntos_requeridos ASC, p.premio ASC"
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerHistorialCanjesCliente(PDO $pdo, int $idCliente, int $limite = 10): array
{
    $limite = max(1, min($limite, 50));
    $stmt = $pdo->prepare(
        "SELECT id_canje, folio, total_puntos, saldo_despues, estatus, fecha_canje,
                motivo_cancelacion, fecha_cancelacion
         FROM tb_canjes
         WHERE id_cliente = ?
         ORDER BY fecha_canje DESC
         LIMIT $limite"
    );
    $stmt->execute([$idCliente]);
    $canjes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$canjes) {
        return [];
    }

    $ids = array_column($canjes, 'id_canje');
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT id_canje, premio, cantidad, puntos_unitarios, puntos_total
         FROM tb_canje_detalle
         WHERE id_canje IN ($marcadores)
         ORDER BY id_canje_detalle ASC"
    );
    $stmt->execute($ids);

    $detalles = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $detalle) {
        $detalles[$detalle['id_canje']][] = $detalle;
    }

    foreach ($canjes as &$canje) {
        $canje['detalles'] = $detalles[$canje['id_canje']] ?? [];
    }
    unset($canje);

    return $canjes;
}

function generarFolioCanje(): string
{
    return 'CNJ-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
}
