<?php

// Datos del centro de canje que se imprimen en la credencial del cliente
// (vista previa, imagen para WhatsApp y plantilla de impresion PVC).
function obtenerConfiguracionCentroCanje(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT nombre_centro, direccion, telefono, fecha_actualizacion
         FROM tb_configuracion_centro_canje
         WHERE id_configuracion = 1
         LIMIT 1"
    );
    $configuracion = $stmt->fetch(PDO::FETCH_ASSOC);

    return $configuracion ?: [
        'nombre_centro' => '',
        'direccion' => '',
        'telefono' => '',
        'fecha_actualizacion' => null,
    ];
}
