<?php
function obtenerPuntos(float $volumen): float
{
    return round($volumen * 5, 2);
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
