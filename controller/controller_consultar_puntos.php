<?php
require_once("../app/config/config.php");
require_once('../app/functions/consultas.php');

// Indicamos al navegador que la respuesta es JSON
header('Content-Type: application/json; charset=utf-8');

$numero = preg_replace('/\D/', '', (string) ($_POST['numero'] ?? ''));
$idClienteQr = filter_var($_POST['id_cliente_qr'] ?? null, FILTER_VALIDATE_INT) ?: 0;

if ($idClienteQr <= 0 && $numero === '') {
    echo json_encode([
        'success' => false,
        'mensaje' => 'El número de teléfono o QR del cliente es obligatorio.'
    ]);
    exit;
}

$cliente = false;

if ($idClienteQr > 0) {
    $cliente = obtenerDatosClientePorId($idClienteQr);
}

if (!$cliente && $numero !== '') {
    $cliente = obtenerDatosClientePorTelefono($numero);
}

if ($cliente) {
    echo json_encode([
        'success' => true,
        'nombre' => $cliente['nombres'] . ' ' . $cliente['apellido_p'] . ' ' . $cliente['apellido_m'],
        'puntos' => $cliente['puntos'] ?? 0, // Si no tiene puntos, mostramos 0
        'mensaje' => 'Consulta exitosa'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Cliente no encontrado en el sistema.'
    ]);
}
exit;