<?php
// Calcula y guarda el tiempo en obra (GPS de Samsara) de las remisiones finalizadas de los ultimos dias, para que
// la columna "Tiempo en obra (GPS)" del listado de remisiones ya este llena sin presionar el boton.
//
// Uso (cron de cPanel, por ejemplo todos los dias a las 11:30 pm):
//   php /ruta/a/recompensas/cron/estadias_gps.php        -> hoy y los 2 dias anteriores
//   php /ruta/a/recompensas/cron/estadias_gps.php 7      -> hoy y los 7 dias anteriores

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo se puede ejecutar por linea de comandos.');
}

chdir(__DIR__ . '/..');
require_once 'app/config/config.php';
require_once 'app/functions/samsara.php';

set_time_limit(1800);
$diasAtras = isset($argv[1]) ? max(0, min(60, (int) $argv[1])) : 2;
$desde = date('Y-m-d', strtotime("-{$diasAtras} days"));
$hasta = date('Y-m-d');

$resultado = calcularEstadiasPendientes($pdo, $desde, $hasta, 1500);

echo date('Y-m-d H:i:s') . " Estadias GPS {$desde} a {$hasta}: {$resultado['remisiones']} remisiones revisadas"
    . ($resultado['completo'] ? '' : ", incompleto (siguiente dia: {$resultado['siguiente_dia']})")
    . ($resultado['error'] ? ", error: {$resultado['error']}" : '') . "\n";

exit($resultado['error'] ? 1 : 0);
