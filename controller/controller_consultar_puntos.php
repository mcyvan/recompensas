<?php
require_once("../app/config/config.php");
require_once('../app/functions/consultas.php');

// Indicamos al navegador que la respuesta es JSON
header('Content-Type: application/json; charset=utf-8');

$numero = $_POST['numero'] ?? '';

if (empty($numero)) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'El número de teléfono es obligatorio.'
    ]);
    exit;
}


// Supongamos que esta función busca en tu base de datos
$cliente = obtenerDatosClientePorTelefono($numero);

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
