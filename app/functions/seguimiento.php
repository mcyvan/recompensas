<?php

// id_cliente => ['ultima' => fecha, 'usuario' => quien la marco, 'total' => llamadas]
function obtenerResumenLlamadasClientes(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT l.id_cliente, l.fecha_llamada AS ultima, u.usuario,
                (SELECT COUNT(*) FROM tb_clientes_llamadas t WHERE t.id_cliente = l.id_cliente) AS total
         FROM tb_clientes_llamadas l
         INNER JOIN tb_usuarios u ON u.id_usuario = l.id_usuario
         WHERE l.id_llamada = (
             SELECT MAX(m.id_llamada) FROM tb_clientes_llamadas m WHERE m.id_cliente = l.id_cliente
         )"
    );

    $resumen = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $resumen[(int) $fila['id_cliente']] = [
            'ultima' => $fila['ultima'],
            'usuario' => $fila['usuario'],
            'total' => (int) $fila['total'],
        ];
    }

    return $resumen;
}
