<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/functions/google_sheets.php';
require_once __DIR__ . '/../app/functions/conciliacion_ventas.php';

$bloqueo = $pdo->query("SELECT GET_LOCK('recompensas_sincronizar_ventas', 1)")->fetchColumn();
if ((int) $bloqueo !== 1) {
    exit("Otro proceso de sincronizacion de ventas esta activo.\n");
}

try {
    $config = cargarConfigGoogleSheets();

    try {
        $resultado = sincronizarVentasDesdeGoogleSheets($pdo, $config);
        echo "Ventas: {$resultado['filas_validas']} filas validas (de {$resultado['filas_leidas']} leidas), "
            . "{$resultado['dias_reemplazados']} dia(s) reemplazados, ultima fila {$resultado['ultima_fila']}\n";
    } catch (Throwable $e) {
        error_log('Sincronizar ventas: ' . $e->getMessage());
        registrarErrorSincronizacionVentas($pdo, $e->getMessage());
        echo "Error: {$e->getMessage()}\n";
    }
} finally {
    $pdo->query("SELECT RELEASE_LOCK('recompensas_sincronizar_ventas')");
}
