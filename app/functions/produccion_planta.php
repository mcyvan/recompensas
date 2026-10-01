<?php

require_once __DIR__ . '/google_sheets.php';

const PRODUCCION_PLANTAS_VALIDAS = ['NORTE', 'OESTE', 'SUR'];

// ~300 filas cubren mas de una semana de captura (unas 20-30 remisiones reales
// por dia mas huecos de formato), suficiente para alcanzar correcciones tardias
// sin tener que releer la hoja completa en cada corrida.
const PRODUCCION_SHEET_COLCHON_FILAS = 300;

function normalizarEncabezadoProduccion(string $texto): string
{
    return preg_replace('/\s+/u', ' ', trim(strtoupper($texto))) ?? '';
}

// Devuelve el indice de la primera columna cuyo encabezado coincide (exacto o
// "contiene") con alguno de los candidatos, en el orden dado.
function localizarColumnaProduccion(array $encabezados, array $candidatos): ?int
{
    $normalizados = array_map('normalizarEncabezadoProduccion', $encabezados);

    foreach ($candidatos as $candidato) {
        $indice = array_search($candidato, $normalizados, true);
        if ($indice !== false) {
            return $indice;
        }
    }

    foreach ($candidatos as $candidato) {
        foreach ($normalizados as $indice => $encabezado) {
            if ($encabezado !== '' && str_contains($encabezado, $candidato)) {
                return $indice;
            }
        }
    }

    return null;
}

function mapaColumnasProduccion(array $encabezados): array
{
    $mapa = [
        'fecha' => localizarColumnaProduccion($encabezados, ['FECHA']),
        'camion' => localizarColumnaProduccion($encabezados, ['CAMION']),
        'operador' => localizarColumnaProduccion($encabezados, ['OPERADOR']),
        'folio_remision' => localizarColumnaProduccion($encabezados, ['REMISION']),
        'sello' => localizarColumnaProduccion($encabezados, ['SELLO']),
        'volumen' => localizarColumnaProduccion($encabezados, ['VOL.', 'VOL']),
        'resistencia' => localizarColumnaProduccion($encabezados, ['RESISTENCIA']),
        'tirado_bombeado' => localizarColumnaProduccion($encabezados, ['TIRADO/ BOMBEADO', 'TIRADO BOMBEADO', 'BOMBEADO']),
        'notas' => localizarColumnaProduccion($encabezados, ['NOTAS ADICIONALES', 'NOTAS']),
        'direccion' => localizarColumnaProduccion($encabezados, ['DIRECCION']),
    ];

    if ($mapa['folio_remision'] === null) {
        throw new RuntimeException('No se encontro la columna REMISION en el encabezado de la hoja.');
    }

    return $mapa;
}

function valorCeldaProduccion(array $fila, ?int $indice): ?string
{
    if ($indice === null || !array_key_exists($indice, $fila)) {
        return null;
    }

    $valor = trim((string) $fila[$indice]);

    return $valor === '' ? null : $valor;
}

function parsearFechaProduccion(?string $texto): ?string
{
    if ($texto === null) {
        return null;
    }

    $fecha = DateTime::createFromFormat('j/n/Y', $texto) ?: DateTime::createFromFormat('j-n-Y', $texto);

    return $fecha ? $fecha->format('Y-m-d') : null;
}

// true si el valor de OPERADOR corresponde a un chofer real (no "BACHA / OLLA"
// ni variantes, que son mezcla en sitio sin camion asignado).
function esOperadorRealProduccion(?string $operador): bool
{
    if ($operador === null || $operador === '') {
        return false;
    }

    $normalizado = normalizarEncabezadoProduccion($operador);

    return !str_contains($normalizado, 'BACHA') && !str_contains($normalizado, 'OLLA');
}

// Trae de Google Sheets solo las filas nuevas desde la ultima sincronizacion y
// las guarda en tb_produccion_planta. Devuelve cuantas filas nuevas se leyeron
// y cuantas traian folio (las demas son huecos/formato sin capturar aun).
function sincronizarProduccionPlanta(PDO $pdo, array $config, string $planta): array
{
    if (!in_array($planta, PRODUCCION_PLANTAS_VALIDAS, true)) {
        throw new InvalidArgumentException("Planta invalida: $planta");
    }

    $hoja = $config['hojas_por_planta'][$planta] ?? null;
    if (!$hoja) {
        throw new RuntimeException("No hay hoja configurada para la planta $planta");
    }

    $encabezados = obtenerFilasHojaGoogle($config, "'$hoja'!A1:AF3");
    $indiceEncabezado = null;
    foreach ($encabezados as $indice => $fila) {
        if (in_array('REMISION', array_map('trim', array_map('strval', $fila)), true)) {
            $indiceEncabezado = $indice;
            break;
        }
    }
    if ($indiceEncabezado === null) {
        throw new RuntimeException("No se encontro la fila de encabezado en la hoja '$hoja'.");
    }
    $mapaColumnas = mapaColumnasProduccion($encabezados[$indiceEncabezado]);
    $filaEncabezadoSheet = $indiceEncabezado + 1; // 1-based

    $stmtUltimaFila = $pdo->prepare('SELECT ultima_fila_sincronizada FROM tb_produccion_planta_sync WHERE planta = ?');
    $stmtUltimaFila->execute([$planta]);
    $ultimaFila = (int) ($stmtUltimaFila->fetchColumn() ?: 0);

    // El punto de reanudacion se calcula desde la ULTIMA FILA CON FOLIO REAL que
    // ya tenemos guardada (no desde el puntero crudo de "hasta donde se leyo"):
    // filas futuras sin capturar igual traen "algo" (formulas de mes, checkboxes),
    // asi que Google las devuelve como no vacias y el puntero crudo puede quedar
    // inflado hasta el limite de la hoja sin datos reales de por medio. Calcularlo
    // asi tambien autocorrige si alguna corrida anterior ya quedo inflada.
    $stmtUltimaConFolio = $pdo->prepare('SELECT MAX(fila_origen) FROM tb_produccion_planta WHERE planta = ?');
    $stmtUltimaConFolio->execute([$planta]);
    $ultimaConFolioGuardada = (int) ($stmtUltimaConFolio->fetchColumn() ?: 0);

    // Se relee un colchon de filas anteriores a esa, por si el dosificador
    // corrigio el chofer o el folio de un dia anterior. Como aqui se actualiza
    // por fila exacta (ON DUPLICATE KEY UPDATE), releer de mas es seguro: no
    // duplica ni borra nada.
    $filaInicio = max($ultimaConFolioGuardada - PRODUCCION_SHEET_COLCHON_FILAS + 1, $filaEncabezadoSheet + 1);

    $filasNuevas = obtenerFilasHojaGoogle($config, "'$hoja'!A{$filaInicio}:AF");

    // Se usa la hora de PHP (ya en la zona horaria de la app) en vez de NOW() de
    // MySQL: NOW() usa el reloj/zona del servidor de base de datos, que puede no
    // coincidir con la zona configurada en config.php y desfasar el reporte.
    $ahora = date('Y-m-d H:i:s');

    $stmtUpsert = $pdo->prepare(
        'INSERT INTO tb_produccion_planta
            (planta, fila_origen, fecha, camion, operador, folio_remision, sello, volumen,
             resistencia, tirado_bombeado, notas, direccion, fecha_sincronizacion)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            fecha = VALUES(fecha), camion = VALUES(camion), operador = VALUES(operador),
            folio_remision = VALUES(folio_remision), sello = VALUES(sello), volumen = VALUES(volumen),
            resistencia = VALUES(resistencia), tirado_bombeado = VALUES(tirado_bombeado),
            notas = VALUES(notas), direccion = VALUES(direccion), fecha_sincronizacion = ?'
    );

    $insertadas = 0;
    $ultimaFilaConFolio = null;
    foreach ($filasNuevas as $desplazamiento => $fila) {
        $numeroFila = $filaInicio + $desplazamiento;
        $folio = valorCeldaProduccion($fila, $mapaColumnas['folio_remision']);
        if ($folio === null) {
            continue;
        }
        $ultimaFilaConFolio = $numeroFila;

        $volumenTexto = valorCeldaProduccion($fila, $mapaColumnas['volumen']);
        $stmtUpsert->execute([
            $planta,
            $numeroFila,
            parsearFechaProduccion(valorCeldaProduccion($fila, $mapaColumnas['fecha'])),
            valorCeldaProduccion($fila, $mapaColumnas['camion']),
            valorCeldaProduccion($fila, $mapaColumnas['operador']),
            strtoupper($folio),
            valorCeldaProduccion($fila, $mapaColumnas['sello']),
            $volumenTexto !== null && is_numeric($volumenTexto) ? (float) $volumenTexto : null,
            valorCeldaProduccion($fila, $mapaColumnas['resistencia']),
            valorCeldaProduccion($fila, $mapaColumnas['tirado_bombeado']),
            valorCeldaProduccion($fila, $mapaColumnas['notas']),
            valorCeldaProduccion($fila, $mapaColumnas['direccion']),
            $ahora,
            $ahora,
        ]);
        $insertadas++;
    }

    $totalFilasLeidas = count($filasNuevas);
    // No avanzar hasta el limite fisico de la hoja: filas futuras sin capturar
    // igual traen "algo" (formulas de mes, checkboxes en 0/false), asi que Google
    // las devuelve como no vacias aunque no tengan folio. Si se avanzara hasta ahi,
    // el colchon de relectura ya no alcanzaria a cubrir los dias reales recientes.
    $nuevaUltimaFila = $ultimaFilaConFolio ?? $ultimaFila;

    $stmtSync = $pdo->prepare(
        'INSERT INTO tb_produccion_planta_sync (planta, ultima_fila_sincronizada, fecha_sincronizacion, filas_insertadas_ultima_vez, ultimo_error)
         VALUES (?, ?, ?, ?, NULL)
         ON DUPLICATE KEY UPDATE
            ultima_fila_sincronizada = VALUES(ultima_fila_sincronizada),
            fecha_sincronizacion = VALUES(fecha_sincronizacion),
            filas_insertadas_ultima_vez = VALUES(filas_insertadas_ultima_vez),
            ultimo_error = NULL'
    );
    $stmtSync->execute([$planta, $nuevaUltimaFila, $ahora, $insertadas]);

    return [
        'planta' => $planta,
        'filas_leidas' => $totalFilasLeidas,
        'filas_con_folio' => $insertadas,
        'ultima_fila' => $nuevaUltimaFila,
    ];
}

function registrarErrorSincronizacionProduccion(PDO $pdo, string $planta, string $mensaje): void
{
    $ahora = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare(
        'INSERT INTO tb_produccion_planta_sync (planta, ultima_fila_sincronizada, fecha_sincronizacion, ultimo_error)
         VALUES (?, 0, ?, ?)
         ON DUPLICATE KEY UPDATE fecha_sincronizacion = VALUES(fecha_sincronizacion), ultimo_error = VALUES(ultimo_error)'
    );
    $stmt->execute([$planta, $ahora, mb_substr($mensaje, 0, 500)]);
}

function filtrosProduccionPlanta(array $entrada): array
{
    $fechaInicio = trim((string) ($entrada['fecha_inicio'] ?? ''));
    $fechaFin = trim((string) ($entrada['fecha_fin'] ?? ''));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
        $fechaInicio = date('Y-m-01');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
        $fechaFin = date('Y-m-d');
    }
    if ($fechaInicio > $fechaFin) {
        [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
    }

    $planta = strtoupper(trim((string) ($entrada['planta'] ?? '')));
    if (!in_array($planta, PRODUCCION_PLANTAS_VALIDAS, true)) {
        $planta = '';
    }

    return ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'planta' => $planta];
}

// Cuantas remisiones deberia haber metido cada operador (segun la hoja de
// produccion del dosificador) contra cuantas metio de verdad en Recompensas.
function obtenerProduccionPorOperador(PDO $pdo, array $filtros): array
{
    $stmt = $pdo->prepare(
        "SELECT
            p.planta,
            p.operador,
            COUNT(*) AS remisiones_esperadas,
            SUM(CASE WHEN r.id_remision IS NOT NULL AND r.estatus <> 'CANCELADO' THEN 1 ELSE 0 END) AS remisiones_registradas
         FROM tb_produccion_planta p
         LEFT JOIN tb_remisiones r ON r.folio_remision = p.folio_remision
         WHERE p.fecha BETWEEN ? AND ?
           AND (? = '' OR p.planta = ?)
           AND p.operador NOT LIKE '%BACHA%'
           AND p.operador NOT LIKE '%OLLA%'
           AND p.operador IS NOT NULL
         GROUP BY p.planta, p.operador
         ORDER BY p.planta, remisiones_esperadas DESC"
    );
    $stmt->execute([$filtros['fecha_inicio'], $filtros['fecha_fin'], $filtros['planta'], $filtros['planta']]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Folios especificos que un operador dejo de meter, para poder darle
// seguimiento puntual.
function obtenerFoliosFaltantesOperador(PDO $pdo, string $operador, array $filtros): array
{
    $stmt = $pdo->prepare(
        "SELECT p.planta, p.fecha, p.folio_remision, p.volumen, p.direccion
         FROM tb_produccion_planta p
         LEFT JOIN tb_remisiones r ON r.folio_remision = p.folio_remision AND r.estatus <> 'CANCELADO'
         WHERE p.operador = ?
           AND p.fecha BETWEEN ? AND ?
           AND (? = '' OR p.planta = ?)
           AND r.id_remision IS NULL
         ORDER BY p.fecha DESC, p.folio_remision"
    );
    $stmt->execute([$operador, $filtros['fecha_inicio'], $filtros['fecha_fin'], $filtros['planta'], $filtros['planta']]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerEstadoSincronizacionProduccion(PDO $pdo): array
{
    return $pdo->query(
        'SELECT planta, ultima_fila_sincronizada, fecha_sincronizacion, filas_insertadas_ultima_vez, ultimo_error
         FROM tb_produccion_planta_sync
         ORDER BY planta'
    )->fetchAll(PDO::FETCH_ASSOC);
}
