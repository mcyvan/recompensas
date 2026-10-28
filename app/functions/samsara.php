<?php

// Comparativo de estadia en obra: lo que marco el chofer contra lo que muestra el GPS de Samsara.
// Solo lee de Samsara (historial de GPS); no escribe nada en Samsara.

const SAMSARA_API_URL = 'https://api.samsara.com';
// Diferencia maxima (en minutos) entre la estadia de Samsara y el tiempo marcado para decir que concuerda.
const SAMSARA_TOLERANCIA_MINUTOS = 10;
// Radio (en metros) de la zona de obra alrededor del punto de descarga: cubre el lugar cercano donde lavan la olla.
const SAMSARA_RADIO_ZONA_METROS = 1000;
// Minutos de lavado de la olla que se suman al tiempo marcado para saber cuanto deberia durar la estadia.
const SAMSARA_LAVADO_MINUTOS = 10;
// Maximo de remisiones que se comparan en una consulta, para no saturar la API de Samsara.
const SAMSARA_MAX_REMISIONES = 150;
// Cambia cuando cambia la forma de calcular, para no mostrar resultados guardados con la forma anterior.
const SAMSARA_VERSION_ANALISIS = 'v5';
// RPM del motor a partir de las cuales se considera que la olla esta girando para cargar (cada camion tiene las suyas, ~1,350 a ~1,850).
const SAMSARA_RPM_CARGA = 1200;
// Velocidad maxima (mph) para decir que el camion esta en el puesto de carga y no circulando por la planta.
const SAMSARA_MPH_CARGA = 3;

function samsaraRutaCache(): string
{
    $ruta = __DIR__ . '/../cache/samsara';
    if (!is_dir($ruta)) {
        @mkdir($ruta, 0775, true);
    }

    return $ruta;
}

// La llave se lee de app/config/samsara.local.php (['api_key' => '...']); no se guarda en git.
function samsaraToken(): string
{
    $archivo = __DIR__ . '/../config/samsara.local.php';
    if (!is_file($archivo)) {
        throw new RuntimeException('Falta el archivo app/config/samsara.local.php con la llave de Samsara.');
    }
    $config = require $archivo;
    $token = trim((string) ($config['api_key'] ?? ''));
    if ($token === '') {
        throw new RuntimeException('El archivo app/config/samsara.local.php no trae api_key.');
    }

    return $token;
}

function samsaraLlamar(string $ruta): array
{
    $token = samsaraToken();

    for ($intento = 1; $intento <= 3; $intento++) {
        $ch = curl_init(SAMSARA_API_URL . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
        ]);
        $cuerpo = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($cuerpo === false) {
            throw new RuntimeException('No se pudo conectar con Samsara: ' . $errorCurl);
        }
        if ($codigo === 429 && $intento < 3) {
            sleep(2 * $intento);
            continue;
        }

        $datos = json_decode((string) $cuerpo, true);
        if ($codigo === 401 || $codigo === 403) {
            throw new RuntimeException('Samsara rechazo la llave o le faltan permisos: ' . ($datos['message'] ?? $codigo));
        }
        if ($codigo < 200 || $codigo >= 300 || !is_array($datos)) {
            throw new RuntimeException('Samsara respondio con error ' . $codigo);
        }

        return $datos;
    }

    throw new RuntimeException('Samsara no respondio (demasiadas peticiones).');
}

// Nombre del camion (CR08, CR37...) => id de vehiculo en Samsara. Se guarda 1 hora para no pedirlo siempre.
function samsaraIdsPorNombre(): array
{
    $archivoCache = samsaraRutaCache() . '/vehiculos.json';
    if (is_file($archivoCache) && time() - filemtime($archivoCache) < 3600) {
        $guardado = json_decode((string) file_get_contents($archivoCache), true);
        if (is_array($guardado) && $guardado) {
            return $guardado;
        }
    }

    $ids = [];
    $cursor = null;
    do {
        $datos = samsaraLlamar('/fleet/vehicles?limit=200' . ($cursor ? '&after=' . urlencode($cursor) : ''));
        foreach ($datos['data'] ?? [] as $vehiculo) {
            $nombre = strtoupper(trim((string) ($vehiculo['name'] ?? '')));
            if ($nombre !== '' && isset($vehiculo['id'])) {
                $ids[$nombre] = (string) $vehiculo['id'];
            }
        }
        $cursor = !empty($datos['pagination']['hasNextPage']) ? ($datos['pagination']['endCursor'] ?? null) : null;
    } while ($cursor);

    @file_put_contents($archivoCache, json_encode($ids));

    return $ids;
}

// Puntos de GPS de un camion entre dos momentos (timestamps). Cada punto: t, lat, lon, mph, geocerca.
function samsaraHistorialGps(string $idVehiculo, int $desde, int $hasta): array
{
    $puntos = [];
    $cursor = null;
    $paginas = 0;

    do {
        $ruta = '/fleet/vehicles/stats/history?types=gps&vehicleIds=' . urlencode($idVehiculo)
            . '&startTime=' . urlencode(gmdate('Y-m-d\TH:i:s\Z', $desde))
            . '&endTime=' . urlencode(gmdate('Y-m-d\TH:i:s\Z', $hasta))
            . ($cursor ? '&after=' . urlencode($cursor) : '');
        $datos = samsaraLlamar($ruta);

        foreach ($datos['data'][0]['gps'] ?? [] as $punto) {
            $puntos[] = [
                't' => strtotime((string) $punto['time']),
                'lat' => (float) $punto['latitude'],
                'lon' => (float) $punto['longitude'],
                'mph' => (float) ($punto['speedMilesPerHour'] ?? 0),
                'geocerca' => $punto['address']['name'] ?? null,
            ];
        }

        $cursor = !empty($datos['pagination']['hasNextPage']) ? ($datos['pagination']['endCursor'] ?? null) : null;
        $paginas++;
    } while ($cursor && $paginas < 40);

    usort($puntos, static fn($a, $b) => $a['t'] <=> $b['t']);

    return $puntos;
}

// Serie de una estadistica de Samsara (por ejemplo engineRpm): lista de [timestamp, valor].
function samsaraHistorialSerie(string $idVehiculo, string $tipo, int $desde, int $hasta): array
{
    $serie = [];
    $cursor = null;
    $paginas = 0;

    do {
        $ruta = '/fleet/vehicles/stats/history?types=' . urlencode($tipo) . '&vehicleIds=' . urlencode($idVehiculo)
            . '&startTime=' . urlencode(gmdate('Y-m-d\TH:i:s\Z', $desde))
            . '&endTime=' . urlencode(gmdate('Y-m-d\TH:i:s\Z', $hasta))
            . ($cursor ? '&after=' . urlencode($cursor) : '');
        $datos = samsaraLlamar($ruta);

        foreach ($datos['data'][0][$tipo] ?? [] as $muestra) {
            $serie[] = [strtotime((string) $muestra['time']), (float) $muestra['value']];
        }

        $cursor = !empty($datos['pagination']['hasNextPage']) ? ($datos['pagination']['endCursor'] ?? null) : null;
        $paginas++;
    } while ($cursor && $paginas < 40);

    usort($serie, static fn($a, $b) => $a[0] <=> $b[0]);

    return $serie;
}

function samsaraDistanciaMetros(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $radioTierra = 6371000.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

    return 2 * $radioTierra * asin(min(1.0, sqrt($a)));
}

function samsaraMediana(array $valores): float
{
    sort($valores);
    $n = count($valores);
    if ($n === 0) {
        return 0.0;
    }

    return $n % 2 ? $valores[intdiv($n, 2)] : ($valores[$n / 2 - 1] + $valores[$n / 2]) / 2;
}

// Detecta cuando se cargo el camion dentro de la planta: la ultima corrida de RPM altas del motor (la olla girando
// para cargar, con el camion detenido en el puesto de carga) antes de salir. Devuelve [inicio, fin] o [null, null].
// Samsara da una muestra de RPM cada ~2 minutos, asi que el tiempo es aproximado (+/- 2 min).
function samsaraDetectarCarga(array $puntos, array $rpm, int $entradaPlanta, int $salidaPlanta): array
{
    if (!$rpm) {
        return [null, null];
    }

    $altas = [];
    $g = 0;
    $total = count($puntos);
    foreach ($rpm as [$t, $valor]) {
        if ($t < $entradaPlanta || $t > $salidaPlanta + 60 || $valor < SAMSARA_RPM_CARGA) {
            continue;
        }
        // Punto GPS mas cercano en el tiempo, para saber si el camion estaba detenido dentro de la planta.
        while ($g + 1 < $total && abs($puntos[$g + 1]['t'] - $t) <= abs($puntos[$g]['t'] - $t)) {
            $g++;
        }
        if ($total && abs($puntos[$g]['t'] - $t) <= 60 && $puntos[$g]['mph'] < SAMSARA_MPH_CARGA && !empty($puntos[$g]['geocerca'])) {
            $altas[] = $t;
        }
    }
    if (!$altas) {
        return [null, null];
    }

    // Ultima corrida: muestras altas con menos de 4 minutos entre una y otra.
    $fin = count($altas) - 1;
    $ini = $fin;
    while ($ini > 0 && $altas[$ini] - $altas[$ini - 1] <= 240) {
        $ini--;
    }

    return [$altas[$ini], $altas[$fin]];
}

// Compara el tiempo marcado por el chofer (inicio/fin, timestamps) con lo que ve el GPS del camion.
//
// Zona de obra = todo lo que esta a menos de SAMSARA_RADIO_ZONA_METROS del punto de descarga (la ubicacion
// mediana del camion durante el tiempo marcado). Asi el traslado cercano para lavar la olla cuenta como parte
// de la estadia. La estadia en zona va desde que el camion entra a la zona hasta que sale de ella (o llega a
// una planta). Se espera que dure el tiempo marcado mas SAMSARA_LAVADO_MINUTOS de lavado.
//
// Tambien calcula cuanto estuvo el camion en la planta antes de salir a esta obra y los traslados.
function analizarEstadiaGps(array $puntos, int $inicioMarcado, int $finMarcado, array $rpm = []): array
{
    $marcadoMin = max(0, (int) round(($finMarcado - $inicioMarcado) / 60));
    $base = ['marcado_min' => $marcadoMin, 'estado' => 'sin_datos', 'llegada' => null, 'salida' => null,
        'samsara_min' => null, 'esperado_min' => $marcadoMin + SAMSARA_LAVADO_MINUTOS, 'diferencia_min' => null,
        'espera_antes_min' => null, 'despues_fin_min' => null,
        'planta_nombre' => null, 'planta_salida' => null, 'planta_min' => null, 'planta_truncado' => false, 'traslado_ida_min' => null,
        'regreso_planta' => null, 'traslado_vuelta_min' => null,
        'carga_inicio' => null, 'carga_fin' => null, 'carga_min' => null];

    $esPlanta = static fn(array $p): bool => !empty($p['geocerca']) && stripos($p['geocerca'], 'PLANTA') !== false;

    $indicesVentana = [];
    foreach ($puntos as $i => $p) {
        if ($p['t'] >= $inicioMarcado && $p['t'] <= $finMarcado) {
            $indicesVentana[] = $i;
        }
    }
    // Camion parado: hay puntos desde 2 horas antes hasta 1 hora despues del tiempo marcado y en todos ellos no se
    // movio. Ese camion no fue a ninguna obra en ese rato (probablemente la remision se capturo con otro camion
    // asignado). Parado casi no manda puntos, por eso tambien se revisa cuando no hay ninguno dentro del tiempo marcado.
    $camionParado = static function () use ($puntos, $inicioMarcado, $finMarcado): bool {
        $velocidadMax = 0.0;
        $recorridoM = 0.0;
        $anterior = null;
        $cuantos = 0;
        foreach ($puntos as $p) {
            if ($p['t'] < $inicioMarcado - 7200 || $p['t'] > $finMarcado + 3600) {
                continue;
            }
            $cuantos++;
            $velocidadMax = max($velocidadMax, $p['mph']);
            if ($anterior !== null) {
                $recorridoM += samsaraDistanciaMetros($anterior['lat'], $anterior['lon'], $p['lat'], $p['lon']);
            }
            $anterior = $p;
        }

        return $cuantos > 0 && $velocidadMax < 5 && $recorridoM < 500;
    };

    if (!$indicesVentana) {
        if ($camionParado()) {
            $base['estado'] = 'sin_movimiento';
        }

        return $base;
    }

    $enPlanta = 0;
    foreach ($indicesVentana as $i) {
        if ($esPlanta($puntos[$i])) {
            $enPlanta++;
        }
    }
    if ($enPlanta * 2 > count($indicesVentana)) {
        $base['estado'] = 'en_planta';

        return $base;
    }

    if ($camionParado()) {
        $base['estado'] = 'sin_movimiento';

        return $base;
    }

    $latRef = samsaraMediana(array_map(static fn($i) => $puntos[$i]['lat'], $indicesVentana));
    $lonRef = samsaraMediana(array_map(static fn($i) => $puntos[$i]['lon'], $indicesVentana));
    $dentroDeZona = static fn(array $p): bool => !$esPlanta($p)
        && samsaraDistanciaMetros($p['lat'], $p['lon'], $latRef, $lonRef) <= SAMSARA_RADIO_ZONA_METROS;

    // Entrada y salida de la zona de obra, a partir de los puntos del tiempo marcado.
    $primero = $indicesVentana[0];
    while ($primero > 0 && $dentroDeZona($puntos[$primero - 1])) {
        $primero--;
    }
    $ultimo = end($indicesVentana);
    $total = count($puntos);
    while ($ultimo < $total - 1 && $dentroDeZona($puntos[$ultimo + 1])) {
        $ultimo++;
    }

    $llegada = $puntos[$primero]['t'];
    $salida = $puntos[$ultimo]['t'];
    $samsaraMin = (int) round(($salida - $llegada) / 60);
    $esperadoMin = $marcadoMin + SAMSARA_LAVADO_MINUTOS;
    $diferencia = $samsaraMin - $esperadoMin;

    if (abs($diferencia) <= SAMSARA_TOLERANCIA_MINUTOS) {
        $estado = 'concuerda';
    } else {
        $estado = $diferencia > 0 ? 'samsara_mayor' : 'samsara_menor';
    }

    // Planta de la que salio antes de llegar a esta obra: cuanto estuvo ahi y cuanto tardo en llegar.
    $plantaNombre = null;
    $plantaMin = null;
    $plantaTruncado = false;
    $trasladoIda = null;
    $plantaSalida = null;
    $cargaInicio = null;
    $cargaFin = null;
    $j = $primero - 1;
    while ($j >= 0 && !$esPlanta($puntos[$j])) {
        $j--;
    }
    if ($j >= 0 && $llegada - $puntos[$j]['t'] <= 4 * 3600) {
        $salidaPlanta = $puntos[$j]['t'];
        $plantaSalida = $salidaPlanta;
        $entradaPlanta = $salidaPlanta;
        $k = $j;
        // Una misma estadia en planta: puntos de la geocerca separados por menos de 10 minutos.
        while ($k > 0 && $esPlanta($puntos[$k - 1]) && $puntos[$k]['t'] - $puntos[$k - 1]['t'] <= 600) {
            $k--;
            $entradaPlanta = $puntos[$k]['t'];
        }
        $plantaNombre = $puntos[$j]['geocerca'];
        $plantaMin = (int) round(($salidaPlanta - $entradaPlanta) / 60);
        $plantaTruncado = $k === 0; // los datos empiezan dentro de la planta: el tiempo real pudo ser mayor
        $trasladoIda = (int) round(($llegada - $salidaPlanta) / 60);

        [$cargaInicio, $cargaFin] = samsaraDetectarCarga($puntos, $rpm, $entradaPlanta, $salidaPlanta);
    }

    // Regreso a planta despues de salir de la obra.
    $regreso = null;
    $trasladoVuelta = null;
    $r = $ultimo + 1;
    while ($r < $total && !$esPlanta($puntos[$r])) {
        $r++;
    }
    if ($r < $total && $puntos[$r]['t'] - $salida <= 3 * 3600) {
        $regreso = $puntos[$r]['t'];
        $trasladoVuelta = (int) round(($regreso - $salida) / 60);
    }

    return [
        'marcado_min' => $marcadoMin,
        'estado' => $estado,
        'llegada' => $llegada,
        'salida' => $salida,
        'samsara_min' => $samsaraMin,
        'esperado_min' => $esperadoMin,
        'diferencia_min' => $diferencia,
        // Cuanto esperaron antes de marcar el inicio, y cuanto paso del fin marcado a que el camion salio de la zona (lavado).
        'espera_antes_min' => (int) round(($inicioMarcado - $llegada) / 60),
        'despues_fin_min' => (int) round(($salida - $finMarcado) / 60),
        'planta_nombre' => $plantaNombre,
        'planta_salida' => $plantaSalida,
        'carga_inicio' => $cargaInicio,
        'carga_fin' => $cargaFin,
        'carga_min' => $cargaInicio !== null ? (int) round(($cargaFin - $cargaInicio) / 60) : null,
        'planta_min' => $plantaMin,
        'planta_truncado' => $plantaTruncado,
        'traslado_ida_min' => $trasladoIda,
        'regreso_planta' => $regreso,
        'traslado_vuelta_min' => $trasladoVuelta,
    ];
}

function samsaraEtiquetasEstado(): array
{
    return [
        'concuerda' => ['Concuerda', 'success'],
        'samsara_mayor' => ['Samsara mayor', 'warning'],
        'samsara_menor' => ['Samsara menor', 'danger'],
        'en_planta' => ['Marcó en planta', 'danger'],
        'sin_movimiento' => ['Camión parado', 'dark'],
        'sin_datos' => ['Sin datos GPS', 'secondary'],
        'sin_camion' => ['Camión sin Samsara', 'secondary'],
        'error' => ['Error Samsara', 'dark'],
    ];
}

// Remisiones finalizadas del periodo con su comparativo. Las que ya se compararon se guardan en
// archivos (una finalizada no cambia), asi que repetir la consulta no vuelve a llamar a Samsara.
function obtenerComparativoEstadias(PDO $pdo, string $fechaInicio, string $fechaFin, string $camion = ''): array
{
    // Camion que el dosificador anoto en la bitacora de produccion para cada remision. Cuando cambian de camion y no
    // se actualiza el sistema de mantenimiento el mismo dia, la remision queda con el camion equivocado; la bitacora
    // trae el que de verdad salio.
    $parametros = [date('Y-m-d', strtotime($fechaInicio . ' -1 day')), date('Y-m-d', strtotime($fechaFin . ' +1 day')),
        $fechaInicio . ' 00:00:00', date('Y-m-d H:i:s', strtotime($fechaFin . ' +1 day'))];
    $filtroCamion = '';
    if ($camion !== '') {
        $numeroCamion = preg_replace('/^CR/i', '', $camion);
        $filtroCamion = ' AND (r.camion_logistica = ? OR p.camion IN (?, ?, ?))';
        array_push($parametros, $camion, $camion, $numeroCamion, ltrim($numeroCamion, '0'));
    }

    $stmt = $pdo->prepare(
        "SELECT r.id_remision, r.folio_remision, r.camion_logistica, r.hora_inicio, r.hora_fin,
                r.minutos_colado, r.volumen, u.usuario AS operador, p.camion AS camion_bitacora
         FROM tb_remisiones r
         INNER JOIN tb_usuarios u ON u.id_usuario = r.id_operador
         LEFT JOIN (
             SELECT CONVERT(folio_remision USING utf8mb4) COLLATE utf8mb4_unicode_ci AS folio, MAX(camion) AS camion
             FROM tb_produccion_planta
             WHERE fecha >= ? AND fecha <= ?
             GROUP BY folio_remision
         ) p ON p.folio = CONVERT(r.folio_remision USING utf8mb4) COLLATE utf8mb4_unicode_ci
         WHERE r.estatus = 'FINALIZADO' AND r.hora_fin IS NOT NULL AND r.hora_inicio >= ? AND r.hora_inicio < ?
               AND r.camion_logistica IS NOT NULL AND r.camion_logistica <> ''$filtroCamion
         ORDER BY r.camion_logistica, r.hora_inicio
         LIMIT " . (SAMSARA_MAX_REMISIONES + 1)
    );
    $stmt->execute($parametros);
    $remisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $recortado = count($remisiones) > SAMSARA_MAX_REMISIONES;
    $remisiones = array_slice($remisiones, 0, SAMSARA_MAX_REMISIONES);

    $carpeta = samsaraRutaCache();
    $porCamion = [];
    foreach ($remisiones as $i => $remision) {
        $remisiones[$i]['inicio_ts'] = strtotime($remision['hora_inicio']);
        $remisiones[$i]['fin_ts'] = strtotime($remision['hora_fin']);

        // Camion real: el de la bitacora si trae uno de concreto (CR..); si no, el del sistema.
        $camionSistema = strtoupper(trim($remision['camion_logistica']));
        $camionBitacora = strtoupper(trim((string) ($remision['camion_bitacora'] ?? '')));
        if ($camionBitacora !== '' && ctype_digit($camionBitacora)) {
            $camionBitacora = 'CR' . str_pad($camionBitacora, 2, '0', STR_PAD_LEFT);
        }
        $camionReal = preg_match('/^CR\d+$/', $camionBitacora) ? $camionBitacora : $camionSistema;
        $remisiones[$i]['camion_sistema'] = $camionSistema;
        $remisiones[$i]['camion_real'] = $camionReal;
        $remisiones[$i]['camion_cambiado'] = $camionReal !== $camionSistema;

        $firma = SAMSARA_VERSION_ANALISIS . '|' . $remision['hora_inicio'] . '|' . $remision['hora_fin']
            . ($camionReal !== $camionSistema ? '|' . $camionReal : '');
        $remisiones[$i]['firma_cache'] = $firma;
        $archivo = $carpeta . '/rem_' . (int) $remision['id_remision'] . '.json';
        $guardado = is_file($archivo) ? json_decode((string) file_get_contents($archivo), true) : null;
        if (is_array($guardado) && ($guardado['firma'] ?? '') === $firma) {
            $remisiones[$i]['analisis'] = $guardado['analisis'];
            continue;
        }
        $porCamion[$camionReal][] = $i;
    }

    $error = null;
    if ($porCamion) {
        try {
            $ids = samsaraIdsPorNombre();
        } catch (RuntimeException $e) {
            $ids = [];
            $error = $e->getMessage();
        }

        foreach ($porCamion as $nombreCamion => $indices) {
            if (!$ids) {
                foreach ($indices as $i) {
                    $remisiones[$i]['analisis'] = ['estado' => 'error'];
                }
                continue;
            }
            if (!isset($ids[$nombreCamion])) {
                foreach ($indices as $i) {
                    $remisiones[$i]['analisis'] = ['estado' => 'sin_camion'];
                }
                continue;
            }

            // Se piden horas antes y despues para ver el tiempo en planta y el regreso.
            $desde = min(array_map(static fn($i) => $remisiones[$i]['inicio_ts'], $indices)) - 4 * 3600;
            $hasta = max(array_map(static fn($i) => $remisiones[$i]['fin_ts'], $indices)) + 3 * 3600;
            try {
                $puntos = samsaraHistorialGps($ids[$nombreCamion], $desde, $hasta);
                try {
                    $rpm = samsaraHistorialSerie($ids[$nombreCamion], 'engineRpm', $desde, $hasta);
                } catch (RuntimeException $e) {
                    $rpm = []; // sin RPM solo falta el tiempo de carga; lo demas se calcula igual
                }
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
                foreach ($indices as $i) {
                    $remisiones[$i]['analisis'] = ['estado' => 'error'];
                }
                continue;
            }

            foreach ($indices as $i) {
                $analisis = analizarEstadiaGps($puntos, $remisiones[$i]['inicio_ts'], $remisiones[$i]['fin_ts'], $rpm);
                $remisiones[$i]['analisis'] = $analisis;
                @file_put_contents(
                    $carpeta . '/rem_' . (int) $remisiones[$i]['id_remision'] . '.json',
                    json_encode(['firma' => $remisiones[$i]['firma_cache'], 'analisis' => $analisis])
                );
            }
        }
    }

    if ($camion !== '') {
        $remisiones = array_values(array_filter($remisiones, static fn($r) => $r['camion_real'] === strtoupper($camion)));
    }

    return ['remisiones' => $remisiones, 'recortado' => $recortado, 'error' => $error];
}


// Lee el comparativo ya guardado de una remision, sin llamar a Samsara. null si todavia no se calcula
// (o si cambiaron sus horas desde que se calculo).
function samsaraAnalisisGuardado(int $idRemision, string $horaInicio, string $horaFin): ?array
{
    static $carpeta = null;
    $carpeta ??= samsaraRutaCache();

    $archivo = $carpeta . '/rem_' . $idRemision . '.json';
    if (!is_file($archivo)) {
        return null;
    }
    $guardado = json_decode((string) file_get_contents($archivo), true);
    $prefijo = SAMSARA_VERSION_ANALISIS . '|' . $horaInicio . '|' . $horaFin;
    $firma = (string) ($guardado['firma'] ?? '');
    if (!is_array($guardado) || ($firma !== $prefijo && strpos($firma, $prefijo . '|') !== 0)) {
        return null;
    }

    return is_array($guardado['analisis'] ?? null) ? $guardado['analisis'] : null;
}

// Calcula (y guarda) el comparativo de GPS de las remisiones finalizadas de un rango de fechas, dia por dia y
// empezando por el mas reciente (es lo que se ve primero en las tablas). Se detiene al pasar $segundos para no
// exceder el tiempo maximo del servidor; si faltan dias devuelve 'siguiente_dia' (el proximo por calcular, hacia
// atras) para volver a llamarla con ese dia como $hasta.
function calcularEstadiasPendientes(PDO $pdo, string $desde, string $hasta, int $segundos = 25): array
{
    $carpeta = samsaraRutaCache();
    if (!is_dir($carpeta) || !is_writable($carpeta)) {
        throw new RuntimeException('El servidor no deja guardar los tiempos de GPS: la carpeta app/cache/samsara no existe o no tiene permiso de escritura.');
    }

    $inicio = microtime(true);
    $remisiones = 0;
    $ultimoDia = null;
    $error = null;
    $dia = $hasta;

    while ($dia >= $desde) {
        if (microtime(true) - $inicio > $segundos) {
            return ['completo' => false, 'remisiones' => $remisiones, 'ultimo_dia' => $ultimoDia, 'siguiente_dia' => $dia, 'error' => $error];
        }
        $resultado = obtenerComparativoEstadias($pdo, $dia, $dia);
        $remisiones += count($resultado['remisiones']);
        if ($resultado['error']) {
            // Si Samsara falla (llave, permisos, limite de peticiones) no tiene caso seguir con los demas dias.
            return ['completo' => false, 'remisiones' => $remisiones, 'ultimo_dia' => $ultimoDia, 'siguiente_dia' => $dia, 'error' => $resultado['error']];
        }
        $ultimoDia = $dia;
        $dia = date('Y-m-d', strtotime($dia . ' -1 day'));
    }

    return ['completo' => true, 'remisiones' => $remisiones, 'ultimo_dia' => $ultimoDia, 'siguiente_dia' => null, 'error' => null];
}
