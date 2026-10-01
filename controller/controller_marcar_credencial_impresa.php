<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
verificarSesion();

header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No tienes permiso para esta accion.']);
    exit;
}

$idCliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);

if (!$idCliente) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Cliente invalido.']);
    exit;
}

// Se sobrescribe en cada reimpresion: solo interesa la ultima vez que se
// imprimio, no un historial de cada impresion.
$fecha = date('Y-m-d H:i:s');

try {
    $stmt = $pdo->prepare(
        'UPDATE tb_clientes
         SET credencial_impresa_fecha = ?, credencial_impresa_por = ?
         WHERE id_cliente = ?'
    );
    $stmt->execute([
        $fecha,
        (int) ($_SESSION['id_usuario_login'] ?? 0),
        $idCliente,
    ]);

    echo json_encode(['ok' => true, 'fecha' => $fecha]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No fue posible guardar.']);
}
