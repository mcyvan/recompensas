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
           AND (fecha_vencimiento IS NULL OR fecha_vencimiento >= ?)"
    );
    // Fecha de PHP (zona de la app), no CURRENT_DATE: el servidor de base de
    // datos puede tener otra zona horaria.
    $stmt->execute([$idCliente, date('Y-m-d')]);

    return round((float) $stmt->fetchColumn(), 2);
}

// id_cliente => saldo de puntos vigente. Para listados (p.ej. la tabla de
// clientes) donde pedir el saldo uno por uno haria una consulta por fila.
function obtenerSaldoPuntosTodosClientes(PDO $pdo): array
{
    $stmt = $pdo->prepare(
        "SELECT id_cliente, COALESCE(SUM(puntos), 0) AS saldo
         FROM tb_movimientos_puntos
         WHERE fecha_vencimiento IS NULL OR fecha_vencimiento >= ?
         GROUP BY id_cliente"
    );
    $stmt->execute([date('Y-m-d')]);

    $saldos = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $saldos[(int) $fila['id_cliente']] = round((float) $fila['saldo'], 2);
    }

    return $saldos;
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
        "SELECT id_canje, folio, documento_folio, total_puntos, saldo_despues, estatus, fecha_canje,
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

function obtenerCanjesRegistrados(PDO $pdo, int $limite = 1000): array
{
    $limite = max(50, min($limite, 5000));
    $stmt = $pdo->query(
        "SELECT
            cj.id_canje,
            cj.folio,
            cj.documento_folio,
            cj.total_puntos,
            cj.saldo_antes,
            cj.saldo_despues,
            cj.estatus,
            cj.fecha_canje,
            cj.motivo_cancelacion,
            cj.fecha_cancelacion,
            c.telefono,
            c.nombres,
            c.apellido_p,
            c.apellido_m,
            u.usuario
         FROM tb_canjes cj
         INNER JOIN tb_clientes c ON c.id_cliente = cj.id_cliente
         INNER JOIN tb_usuarios u ON u.id_usuario = cj.id_usuario
         ORDER BY cj.fecha_canje DESC, cj.id_canje DESC
         LIMIT $limite"
    );
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
