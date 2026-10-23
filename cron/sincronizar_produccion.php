<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/functions/produccion_planta.php';

$bloqueo = $pdo->query("SELECT GET_LOCK('recompensas_sincronizar_produccion', 1)")->fetchColumn();
if ((int) $bloqueo !== 1) {
    exit("Otro proceso de sincronizacion esta activo.\n");
}

try {
    $config = cargarConfigGoogleSheets();

    foreach (PRODUCCION_PLANTAS_VALIDAS as $planta) {
        try {
            $resultado = sincronizarProduccionPlanta($pdo, $config, $planta);
            echo "{$resultado['planta']}: {$resultado['filas_con_folio']} filas nuevas (de {$resultado['filas_leidas']} leidas), ultima fila {$resultado['ultima_fila']}\n";
        } catch (Throwable $e) {
            error_log("Sincronizar produccion $planta: " . $e->getMessage());
            registrarErrorSincronizacionProduccion($pdo, $planta, $e->getMessage());
            echo "Error en $planta: {$e->getMessage()}\n";
        }
    }
} finally {
    $pdo->query("SELECT RELEASE_LOCK('recompensas_sincronizar_produccion')");
}
