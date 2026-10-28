<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/seguimiento.php');
verificarSesion();

header('Content-Type: application/json; charset=utf-8');

if (!in_array($_SESSION['rol'] ?? '', ['ADMINISTRADOR', 'ADMINISTRACION', 'SEGUIMIENTO'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No tienes permiso para esta accion.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
$tokenSesion = $_SESSION['csrf_seguimiento'] ?? '';
if ($tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'La solicitud expiro. Recarga la pagina.']);
    exit;
}

$idCliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
if (!$idCliente) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Cliente invalido.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT 1 FROM tb_clientes WHERE id_cliente = ? AND estatus = 1');
    $stmt->execute([$idCliente]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'El cliente no existe o esta inactivo.']);
        exit;
    }

    // Hora de PHP (zona de la app), no NOW() de MySQL.
    $stmt = $pdo->prepare(
        'INSERT INTO tb_clientes_llamadas (id_cliente, id_usuario, fecha_llamada) VALUES (?, ?, ?)'
    );
    $stmt->execute([$idCliente, (int) ($_SESSION['id_usuario_login'] ?? 0), date('Y-m-d H:i:s')]);

    $resumen = obtenerResumenLlamadasClientes($pdo)[$idCliente] ?? null;
    echo json_encode(['ok' => true, 'llamada' => $resumen]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No fue posible guardar la llamada.']);
}
