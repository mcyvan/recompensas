<?php
function obtenerPuntos(float $volumen): float
{
    global $pdo;

    $stmt = $pdo->query(
        "SELECT puntos_por_m3
         FROM tb_configuracion_puntos
         WHERE id_configuracion = 1
         LIMIT 1"
    );
    $puntosPorMetro = $stmt->fetchColumn();

    if ($puntosPorMetro === false) {
        throw new RuntimeException('No existe la configuracion de puntos por metro cubico.');
    }

    return round($volumen * (float) $puntosPorMetro, 2);
}

function obtenerIdClienteTelefono($telefono)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT id_cliente FROM tb_clientes WHERE telefono = ? AND estatus = 1");
    $stmt->execute([$telefono]);

    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        throw new Exception("Cliente no encontrado");
    }

    return $cliente['id_cliente'];
}
