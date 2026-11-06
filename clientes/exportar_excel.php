<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");

verificarSesion();

if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    exit('No tienes permiso para exportar clientes.');
}

$fechaArchivo = date('Ymd_His');
$nombreArchivo = "clientes_{$fechaArchivo}.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

$salida = fopen('php://output', 'w');
fwrite($salida, "\xEF\xBB\xBF");

fputcsv($salida, [
    '#',
    'Nombre',
    'Apellido paterno',
    'Apellido materno',
    'Correo',
    'Telefono',
    'Fecha nacimiento',
    'Fecha registro',
    'Vendedor',
    'Estatus',
]);

$stmt = $pdo->query(
    "SELECT
        c.nombres,
        c.apellido_p,
        c.apellido_m,
        c.correo,
        c.telefono,
        c.fecha_nacimiento,
        c.fecha_registro,
        c.estatus,
        u.usuario AS vendedor
     FROM tb_clientes c
     LEFT JOIN tb_usuarios u ON u.id_usuario = c.id_usuario
     WHERE c.estatus IN (1, 0)
     ORDER BY c.fecha_registro DESC, c.id_cliente DESC"
);

$contador = 0;
while ($cliente = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $contador++;
    $correo = (string) ($cliente['correo'] ?? '');

    fputcsv($salida, [
        $contador,
        $cliente['nombres'],
        $cliente['apellido_p'],
        $cliente['apellido_m'],
        str_starts_with($correo, 'sin-correo-') ? 'Sin correo' : $correo,
        $cliente['telefono'],
        $cliente['fecha_nacimiento'],
        $cliente['fecha_registro'],
        $cliente['vendedor'],
        ((int) $cliente['estatus'] === 1) ? 'Activo' : 'Inactivo',
    ]);
}

fclose($salida);
exit;
