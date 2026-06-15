<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/canjes.php');

header('Content-Type: application/json; charset=utf-8');
verificarSesion();
verificarPermisoCanjes();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
$tokenSesion = $_SESSION['csrf_canjes'] ?? '';

if ($tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'La solicitud expiro. Recarga la pagina.']);
    exit;
}

$telefono = preg_replace('/[^0-9]/', '', $_POST['telefono'] ?? '');

if (strlen($telefono) !== 10) {
    echo json_encode(['success' => false, 'message' => 'Ingresa un telefono valido de 10 digitos.']);
    exit;
}

$cliente = obtenerClienteCanjePorTelefono($pdo, $telefono);

if (!$cliente) {
    echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
    exit;
}

$saldo = obtenerSaldoCliente($pdo, (int) $cliente['id_cliente']);
$premios = obtenerPremiosDisponiblesCanje($pdo);
$historial = obtenerHistorialCanjesCliente($pdo, (int) $cliente['id_cliente']);

echo json_encode([
    'success' => true,
    'cliente' => [
        'nombre' => trim($cliente['nombres'] . ' ' . $cliente['apellido_p'] . ' ' . $cliente['apellido_m']),
        'telefono' => $cliente['telefono'],
        'saldo' => $saldo,
    ],
    'premios' => $premios,
    'historial' => $historial,
], JSON_UNESCAPED_UNICODE);
