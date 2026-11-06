<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");

verificarSesion();

$rol = $_SESSION['rol'] ?? '';
$esAdministracion = in_array($rol, ['ADMINISTRADOR', 'ADMINISTRACION'], true);
$esLogistica = $rol === 'LOGISTICA';
$esVendedor = in_array($rol, ['VENDEDOR', 'VENDEDORES'], true);

if (!$esAdministracion && !$esLogistica && !$esVendedor) {
    http_response_code(403);
    exit('No tienes permiso para generar este reporte.');
}

$idVendedor = $esVendedor
    ? (int) ($_SESSION['id_usuario_login'] ?? 0)
    : (filter_var($_GET['id_vendedor'] ?? null, FILTER_VALIDATE_INT) ?: 0);

if ($idVendedor <= 0) {
    http_response_code(400);
    exit('Selecciona un vendedor para generar el reporte.');
}

$stmtVendedor = $pdo->prepare(
    "SELECT u.id_usuario, u.usuario,
            TRIM(CONCAT(COALESCE(ud.nombres, ''), ' ', COALESCE(ud.apellido_p, ''), ' ', COALESCE(ud.apellido_m, ''))) AS nombre_vendedor
     FROM tb_usuarios u
     INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
     INNER JOIN tb_roles r ON r.id_rol = ud.id_rol
     WHERE u.id_usuario = ?
       AND r.rol IN ('VENDEDOR', 'VENDEDORES')
       AND u.estatus = 1
     LIMIT 1"
);
$stmtVendedor->execute([$idVendedor]);
$vendedor = $stmtVendedor->fetch(PDO::FETCH_ASSOC);

if (!$vendedor) {
    http_response_code(404);
    exit('El vendedor seleccionado no existe o no esta activo.');
}

$stmtClientes = $pdo->prepare(
    "SELECT
        c.id_cliente,
        c.nombres,
        c.apellido_p,
        c.apellido_m,
        c.correo,
        c.telefono,
        c.fecha_nacimiento,
        c.fecha_registro,
        c.estatus
     FROM tb_clientes c
     WHERE c.id_usuario = ?
       AND c.estatus IN (1, 0)
     ORDER BY c.estatus DESC, c.fecha_registro DESC, c.nombres, c.apellido_p"
);
$stmtClientes->execute([$idVendedor]);

$nombreVendedorArchivo = preg_replace('/[^A-Z0-9_-]+/i', '_', (string) ($vendedor['usuario'] ?? 'vendedor'));
$nombreArchivo = 'validacion_clientes_' . $nombreVendedorArchivo . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

$salida = fopen('php://output', 'w');
fwrite($salida, "\xEF\xBB\xBF");

fputcsv($salida, ['Reporte de validacion de clientes por vendedor']);
fputcsv($salida, ['Vendedor', $vendedor['usuario']]);
fputcsv($salida, ['Nombre vendedor', $vendedor['nombre_vendedor'] ?: $vendedor['usuario']]);
fputcsv($salida, ['Fecha reporte', date('Y-m-d H:i:s')]);
fputcsv($salida, []);

fputcsv($salida, [
    '#',
    'ID cliente',
    'Nombre',
    'Apellido paterno',
    'Apellido materno',
    'Correo',
    'Telefono registrado',
    'Fecha nacimiento',
    'Fecha registro',
    'Estatus',
    'Telefono correcto SI/NO',
    'Telefono correcto / correccion',
    'Observaciones',
]);

$contador = 0;
while ($cliente = $stmtClientes->fetch(PDO::FETCH_ASSOC)) {
    $contador++;
    $correo = (string) ($cliente['correo'] ?? '');

    fputcsv($salida, [
        $contador,
        $cliente['id_cliente'],
        $cliente['nombres'],
        $cliente['apellido_p'],
        $cliente['apellido_m'],
        str_starts_with($correo, 'sin-correo-') ? 'Sin correo' : $correo,
        $cliente['telefono'],
        $cliente['fecha_nacimiento'],
        $cliente['fecha_registro'],
        ((int) $cliente['estatus'] === 1) ? 'Activo' : 'Inactivo',
        '',
        '',
        '',
    ]);
}

fclose($salida);
exit;
