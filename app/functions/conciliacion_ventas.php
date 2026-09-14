<?php

function verificarPermisoConciliacionVentas(): void
{
    if (!in_array($_SESSION['rol'] ?? '', ['ADMINISTRADOR', 'ADMINISTRACION'], true)) {
        http_response_code(403);
        exit('No tienes permiso para acceder al modulo de conciliacion de ventas.');
    }
}

function textoNormalizadoVenta(string $texto): string
{
    $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);
    return mb_strtoupper($texto, 'UTF-8');
}

function articuloEsConcreto(string $articulo): bool
{
    return preg_match(
        '/^(AMANUFACTURADA CONCRETO|CONCRETO|MORTERO|TERMOC|THERMO)/u',
        textoNormalizadoVenta($articulo)
    ) === 1;
}

function nombreVendedorVenta(string $vendedor): string
{
    $nombre = textoNormalizadoVenta($vendedor);
    $nombre = preg_replace('/^\d+\s+/u', '', $nombre) ?? $nombre;

    if (str_contains($nombre, 'AMERICAS')) {
        return 'AMERICAS';
    }
    if ($nombre === 'MOSTRADOR' || str_contains($nombre, 'MOSTRADOR')) {
        return 'MOSTRADOR';
    }
    if (str_contains($nombre, 'JAIME RODRIGUEZ')) {
        return 'JAIME RODRIGUEZ';
    }
    if (str_contains($nombre, 'CLARA CRUZ')) {
        return 'CLARA CRUZ';
    }
    if (str_contains($nombre, 'VERONICA REYES')) {
        return 'VERONICA REYES';
    }

    return $nombre;
}

function claveVendedorVenta(string $vendedor): string
{
    $nombre = nombreVendedorVenta($vendedor);
    $partes = preg_split('/\s+/u', $nombre) ?: [];
    if (count($partes) >= 2) {
        return mb_substr($partes[0], 0, 1, 'UTF-8') . $partes[1];
    }
    return preg_replace('/\s+/u', '', $nombre) ?? $nombre;
}

function indiceColumnaExcel(string $referencia): int
{
    $letras = preg_replace('/[^A-Z]/', '', strtoupper($referencia)) ?? '';
    $indice = 0;
    for ($i = 0, $largo = strlen($letras); $i < $largo; $i++) {
        $indice = ($indice * 26) + (ord($letras[$i]) - 64);
    }
    return max(0, $indice - 1);
}

function valorCeldaXlsx(SimpleXMLElement $celda, array $textosCompartidos): string
{
    $tipo = (string) ($celda['t'] ?? '');

    if ($tipo === 'inlineStr') {
        $partes = $celda->xpath('.//*[local-name()="t"]') ?: [];
        return implode('', array_map(static fn($nodo) => (string) $nodo, $partes));
    }

    $valores = $celda->xpath('./*[local-name()="v"]') ?: [];
    $valor = isset($valores[0]) ? (string) $valores[0] : '';

    if ($tipo === 's') {
        return $textosCompartidos[(int) $valor] ?? '';
    }

    return $valor;
}

function fechaExcelAFecha($valor): ?string
{
    if (is_numeric($valor)) {
        $dias = (float) $valor;
        if ($dias <= 0) {
            return null;
        }
        $segundos = (int) round(($dias - 25569) * 86400);
        return gmdate('Y-m-d', $segundos);
    }

    $texto = trim((string) $valor);
    if ($texto === '') {
        return null;
    }

    $meses = [
        'ene' => 'jan', 'feb' => 'feb', 'mar' => 'mar', 'abr' => 'apr',
        'may' => 'may', 'jun' => 'jun', 'jul' => 'jul', 'ago' => 'aug',
        'sep' => 'sep', 'oct' => 'oct', 'nov' => 'nov', 'dic' => 'dec',
    ];
    $texto = mb_strtolower(str_replace('.', '', $texto), 'UTF-8');
    $texto = strtr($texto, $meses);
    $marca = strtotime($texto);
    return $marca === false ? null : date('Y-m-d', $marca);
}

function numeroVenta($valor): float
{
    $texto = trim((string) $valor);
    if ($texto === '') {
        return 0.0;
    }

    $texto = str_replace(['$', ' '], '', $texto);
    if (str_contains($texto, ',') && str_contains($texto, '.')) {
        $texto = str_replace(',', '', $texto);
    } elseif (str_contains($texto, ',')) {
        $texto = str_replace(',', '.', $texto);
    }

    return is_numeric($texto) ? (float) $texto : 0.0;
}

function leerVentasXlsx(string $ruta): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('La extension ZIP de PHP no esta habilitada.');
    }

    $zip = new ZipArchive();
    if ($zip->open($ruta) !== true) {
        throw new RuntimeException('No se pudo abrir el archivo XLSX.');
    }

    try {
        $textosCompartidos = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = simplexml_load_string($sharedXml);
            if ($shared !== false) {
                $items = $shared->xpath('//*[local-name()="si"]') ?: [];
                foreach ($items as $item) {
                    $partes = $item->xpath('.//*[local-name()="t"]') ?: [];
                    $textosCompartidos[] = implode('', array_map(static fn($nodo) => (string) $nodo, $partes));
                }
            }
        }

        $hojaXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($hojaXml === false) {
            throw new RuntimeException('El archivo no contiene una primera hoja legible.');
        }

        $hoja = simplexml_load_string($hojaXml);
        if ($hoja === false) {
            throw new RuntimeException('No se pudo interpretar la hoja de ventas.');
        }

        $filasXml = $hoja->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [];
        $filas = [];
        foreach ($filasXml as $filaXml) {
            $fila = [];
            $celdas = $filaXml->xpath('./*[local-name()="c"]') ?: [];
            foreach ($celdas as $celda) {
                $indice = indiceColumnaExcel((string) ($celda['r'] ?? ''));
                $fila[$indice] = valorCeldaXlsx($celda, $textosCompartidos);
            }
            if ($fila) {
                ksort($fila);
                $filas[] = $fila;
            }
        }

        $mapa = null;
        $indiceEncabezado = null;
        $esperados = [
            'FECHA DE REMISION' => 'fecha',
            'FECHA' => 'fecha',
            'REMISION' => 'remision',
            'PEDIDO' => 'pedido',
            'FACTURA' => 'factura',
            'PLANTA' => 'planta',
            'VENDEDOR' => 'vendedor',
            'CLIENTE' => 'cliente',
            'ARTICULO' => 'articulo',
            'UNIDADES' => 'cantidad',
            'CANTIDAD' => 'cantidad',
            'PRECIO UNITARIO' => 'precio_unitario',
            'PRECIO' => 'precio_unitario',
        ];

        foreach ($filas as $numero => $fila) {
            $candidata = [];
            foreach ($fila as $columna => $valor) {
                $encabezado = textoNormalizadoVenta((string) $valor);
                $encabezado = strtr($encabezado, ['Ó' => 'O', 'Í' => 'I', 'Á' => 'A', 'É' => 'E', 'Ú' => 'U']);
                if (isset($esperados[$encabezado])) {
                    $candidata[$esperados[$encabezado]] = $columna;
                }
            }
            if (isset($candidata['fecha'], $candidata['remision'], $candidata['articulo'], $candidata['cantidad'])) {
                $mapa = $candidata;
                $indiceEncabezado = $numero;
                break;
            }
        }

        if ($mapa === null || $indiceEncabezado === null) {
            throw new RuntimeException('No se encontraron los encabezados requeridos: Fecha, Remision, Articulo y Cantidad.');
        }

        $ventas = [];
        foreach (array_slice($filas, $indiceEncabezado + 1) as $fila) {
            $obtener = static fn(string $campo) => isset($mapa[$campo]) ? trim((string) ($fila[$mapa[$campo]] ?? '')) : '';
            $fecha = fechaExcelAFecha($obtener('fecha'));
            $remision = textoNormalizadoVenta($obtener('remision'));
            $articulo = trim($obtener('articulo'));

            if (!$fecha || $remision === '' || $articulo === '') {
                continue;
            }

            $ventas[] = [
                'fecha' => $fecha,
                'remision' => $remision,
                'pedido' => $obtener('pedido'),
                'factura' => $obtener('factura'),
                'planta' => $obtener('planta'),
                'vendedor' => $obtener('vendedor'),
                'cliente' => $obtener('cliente'),
                'articulo' => $articulo,
                'cantidad' => numeroVenta($obtener('cantidad')),
                'precio_unitario' => numeroVenta($obtener('precio_unitario')),
                'es_concreto' => articuloEsConcreto($articulo) ? 1 : 0,
            ];
        }

        if (!$ventas) {
            throw new RuntimeException('El archivo no contiene filas de ventas validas.');
        }

        return $ventas;
    } finally {
        $zip->close();
    }
}

function filtrosConciliacionVentas(PDO $pdo, array $entrada): array
{
    $limites = $pdo->query('SELECT MIN(fecha) AS minima, MAX(fecha) AS maxima FROM tb_ventas_empresa')->fetch(PDO::FETCH_ASSOC) ?: [];
    $inicio = trim((string) ($entrada['fecha_inicio'] ?? ''));
    $fin = trim((string) ($entrada['fecha_fin'] ?? ''));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio)) {
        $inicio = !empty($limites['maxima'])
            ? date('Y-m-01', strtotime((string) $limites['maxima']))
            : date('Y-m-01');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
        $fin = (string) ($limites['maxima'] ?? date('Y-m-d'));
    }
    if ($inicio > $fin) {
        [$inicio, $fin] = [$fin, $inicio];
    }

    $planta = textoNormalizadoVenta((string) ($entrada['planta'] ?? ''));

    return ['fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'planta' => $planta];
}

function obtenerPlantasConciliacion(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT DISTINCT UPPER(TRIM(planta)) AS planta
         FROM tb_ventas_empresa
         WHERE es_concreto = 1 AND planta IS NOT NULL AND TRIM(planta) <> ''
         ORDER BY planta"
    );
    return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
}

function obtenerResumenConciliacion(PDO $pdo, array $filtros): array
{
    $sql = "SELECT COUNT(*) AS remisiones_venta,
                   COALESCE(SUM(x.metros), 0) AS metros_venta,
                   SUM(CASE WHEN x.en_recompensas = 1 THEN 1 ELSE 0 END) AS remisiones_registradas,
                   COALESCE(SUM(CASE WHEN x.en_recompensas = 1 THEN x.metros ELSE 0 END), 0) AS metros_registrados
            FROM (
                SELECT v.fecha, v.remision, SUM(v.cantidad) AS metros,
                       CASE WHEN EXISTS (
                           SELECT 1 FROM tb_remisiones r
                           WHERE UPPER(TRIM(r.folio_remision)) = v.remision
                             AND r.estatus <> 'CANCELADO'
                       ) THEN 1 ELSE 0 END AS en_recompensas
                FROM tb_ventas_empresa v
                WHERE v.es_concreto = 1 AND v.fecha BETWEEN ? AND ?
                  AND (? = '' OR UPPER(TRIM(v.planta)) = ?)
                GROUP BY v.fecha, v.remision
            ) x";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$filtros['fecha_inicio'], $filtros['fecha_fin'], $filtros['planta'], $filtros['planta']]);
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $total = (int) ($resumen['remisiones_venta'] ?? 0);
    $registradas = (int) ($resumen['remisiones_registradas'] ?? 0);
    $resumen['faltantes'] = max(0, $total - $registradas);
    $resumen['porcentaje'] = $total > 0 ? ($registradas * 100 / $total) : 0;
    return $resumen;
}

function obtenerConciliacionPorDia(PDO $pdo, array $filtros): array
{
    $stmt = $pdo->prepare(
        "SELECT x.fecha, COUNT(*) AS remisiones_venta, SUM(x.metros) AS metros_venta,
                SUM(x.en_recompensas) AS remisiones_registradas,
                SUM(CASE WHEN x.en_recompensas = 1 THEN x.metros ELSE 0 END) AS metros_registrados
         FROM (
             SELECT v.fecha, v.remision, SUM(v.cantidad) AS metros,
                    CASE WHEN EXISTS (
                        SELECT 1 FROM tb_remisiones r
                        WHERE UPPER(TRIM(r.folio_remision)) = v.remision
                          AND r.estatus <> 'CANCELADO'
                    ) THEN 1 ELSE 0 END AS en_recompensas
             FROM tb_ventas_empresa v
             WHERE v.es_concreto = 1 AND v.fecha BETWEEN ? AND ?
               AND (? = '' OR UPPER(TRIM(v.planta)) = ?)
             GROUP BY v.fecha, v.remision
         ) x
         GROUP BY x.fecha
         ORDER BY x.fecha DESC"
    );
    $stmt->execute([$filtros['fecha_inicio'], $filtros['fecha_fin'], $filtros['planta'], $filtros['planta']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerConciliacionPorPlanta(PDO $pdo, array $filtros): array
{
    $stmt = $pdo->prepare(
        "SELECT COALESCE(NULLIF(UPPER(TRIM(x.planta)), ''), 'SIN PLANTA') AS planta,
                COUNT(*) AS remisiones_venta, SUM(x.metros) AS metros_venta,
                SUM(x.en_recompensas) AS remisiones_registradas,
                SUM(CASE WHEN x.en_recompensas = 1 THEN x.metros ELSE 0 END) AS metros_registrados
         FROM (
             SELECT v.fecha, v.remision, MAX(v.planta) AS planta, SUM(v.cantidad) AS metros,
                    CASE WHEN EXISTS (
                        SELECT 1 FROM tb_remisiones r
                        WHERE UPPER(TRIM(r.folio_remision)) = v.remision
                          AND r.estatus <> 'CANCELADO'
                    ) THEN 1 ELSE 0 END AS en_recompensas
             FROM tb_ventas_empresa v
             WHERE v.es_concreto = 1 AND v.fecha BETWEEN ? AND ?
               AND (? = '' OR UPPER(TRIM(v.planta)) = ?)
             GROUP BY v.fecha, v.remision
         ) x
         GROUP BY planta
         ORDER BY remisiones_venta DESC"
    );
    $stmt->execute([$filtros['fecha_inicio'], $filtros['fecha_fin'], $filtros['planta'], $filtros['planta']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerConciliacionPorVendedor(PDO $pdo, array $filtros): array
{
    $stmt = $pdo->prepare(
        "SELECT x.vendedor,
                COUNT(*) AS remisiones_venta,
                COALESCE(SUM(x.metros), 0) AS metros_venta,
                SUM(x.en_recompensas) AS remisiones_registradas,
                COALESCE(SUM(CASE WHEN x.en_recompensas = 1 THEN x.metros ELSE 0 END), 0) AS metros_registrados
         FROM (
             SELECT v.fecha, v.remision,
                    COALESCE(NULLIF(MAX(TRIM(v.vendedor)), ''), 'SIN VENDEDOR') AS vendedor,
                    SUM(v.cantidad) AS metros,
                    CASE WHEN EXISTS (
                        SELECT 1 FROM tb_remisiones r
                        WHERE UPPER(TRIM(r.folio_remision)) = v.remision
                          AND r.estatus <> 'CANCELADO'
                    ) THEN 1 ELSE 0 END AS en_recompensas
             FROM tb_ventas_empresa v
             WHERE v.es_concreto = 1 AND v.fecha BETWEEN ? AND ?
               AND (? = '' OR UPPER(TRIM(v.planta)) = ?)
             GROUP BY v.fecha, v.remision
         ) x
         GROUP BY x.vendedor
         ORDER BY remisiones_venta DESC, metros_venta DESC, x.vendedor"
    );
    $stmt->execute([$filtros['fecha_inicio'], $filtros['fecha_fin'], $filtros['planta'], $filtros['planta']]);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $usuarios = $pdo->query(
        "SELECT DISTINCT UPPER(TRIM(u.usuario)) AS usuario
         FROM tb_usuarios u
         INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
         INNER JOIN tb_roles r ON r.id_rol = ud.id_rol
         WHERE r.rol IN ('VENDEDOR', 'VENDEDORES') AND u.estatus = 1"
    )->fetchAll(PDO::FETCH_COLUMN);

    $permitidos = array_fill_keys(array_map(
        static fn($usuario) => preg_replace('/\s+/u', '', textoNormalizadoVenta((string) $usuario)),
        $usuarios
    ), true);
    $excepciones = array_fill_keys(['MOSTRADOR', 'AMERICAS', 'JAIME RODRIGUEZ', 'CLARA CRUZ'], true);
    $resultado = [];
    $clavesConDatos = [];

    $nombresHistoricos = [];
    $historicos = $pdo->query(
        "SELECT DISTINCT vendedor
         FROM tb_ventas_empresa
         WHERE es_concreto = 1 AND vendedor IS NOT NULL AND TRIM(vendedor) <> ''"
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($historicos as $historico) {
        $nombreHistorico = nombreVendedorVenta((string) $historico);
        $claveHistorica = preg_replace('/\s+/u', '', claveVendedorVenta($nombreHistorico)) ?? '';
        if ($claveHistorica !== '') {
            $nombresHistoricos[$claveHistorica] = $nombreHistorico;
        }
    }

    foreach ($filas as $fila) {
        $nombre = nombreVendedorVenta((string) $fila['vendedor']);
        $clave = preg_replace('/\s+/u', '', claveVendedorVenta($nombre)) ?? '';
        $nombreCompacto = preg_replace('/\s+/u', '', $nombre) ?? '';

        if (!isset($excepciones[$nombre]) && !isset($permitidos[$clave]) && !isset($permitidos[$nombreCompacto])) {
            continue;
        }

        if (!isset($resultado[$nombre])) {
            $resultado[$nombre] = [
                'vendedor' => $nombre,
                'remisiones_venta' => 0,
                'metros_venta' => 0.0,
                'remisiones_registradas' => 0,
                'metros_registrados' => 0.0,
            ];
        }
        $resultado[$nombre]['remisiones_venta'] += (int) $fila['remisiones_venta'];
        $resultado[$nombre]['metros_venta'] += (float) $fila['metros_venta'];
        $resultado[$nombre]['remisiones_registradas'] += (int) $fila['remisiones_registradas'];
        $resultado[$nombre]['metros_registrados'] += (float) $fila['metros_registrados'];
        $clavesConDatos[$clave] = true;
    }

    foreach ($usuarios as $usuario) {
        $claveUsuario = preg_replace('/\s+/u', '', textoNormalizadoVenta((string) $usuario)) ?? '';
        if ($claveUsuario === '' || isset($clavesConDatos[$claveUsuario])) {
            continue;
        }
        $nombre = $nombresHistoricos[$claveUsuario] ?? textoNormalizadoVenta((string) $usuario);
        $resultado[$nombre] = $resultado[$nombre] ?? [
            'vendedor' => $nombre,
            'remisiones_venta' => 0,
            'metros_venta' => 0.0,
            'remisiones_registradas' => 0,
            'metros_registrados' => 0.0,
        ];
    }

    foreach (array_keys($excepciones) as $nombre) {
        $resultado[$nombre] = $resultado[$nombre] ?? [
            'vendedor' => $nombre,
            'remisiones_venta' => 0,
            'metros_venta' => 0.0,
            'remisiones_registradas' => 0,
            'metros_registrados' => 0.0,
        ];
    }

    $resultado = array_values($resultado);
    usort($resultado, static fn($a, $b) => $b['remisiones_venta'] <=> $a['remisiones_venta'] ?: strcmp($a['vendedor'], $b['vendedor']));
    return $resultado;
}

function obtenerDetalleConciliacion(PDO $pdo, array $filtros): array
{
    $stmt = $pdo->prepare(
        "SELECT v.fecha, v.remision,
                MAX(v.pedido) AS pedido, MAX(v.factura) AS factura,
                MAX(v.planta) AS planta, MAX(v.vendedor) AS vendedor,
                MAX(v.cliente) AS cliente,
                GROUP_CONCAT(DISTINCT v.articulo ORDER BY v.articulo SEPARATOR ' / ') AS articulos,
                SUM(v.cantidad) AS metros,
                CASE WHEN EXISTS (
                    SELECT 1 FROM tb_remisiones r
                    WHERE UPPER(TRIM(r.folio_remision)) = v.remision
                      AND r.estatus <> 'CANCELADO'
                ) THEN 1 ELSE 0 END AS en_recompensas
         FROM tb_ventas_empresa v
         WHERE v.es_concreto = 1 AND v.fecha BETWEEN ? AND ?
           AND (? = '' OR UPPER(TRIM(v.planta)) = ?)
         GROUP BY v.fecha, v.remision
         ORDER BY v.fecha DESC, v.remision DESC"
    );
    $stmt->execute([$filtros['fecha_inicio'], $filtros['fecha_fin'], $filtros['planta'], $filtros['planta']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerUltimasCargasVentas(PDO $pdo, int $limite = 10): array
{
    $limite = max(1, min(50, $limite));
    return $pdo->query(
        "SELECT id_carga, archivos, fecha_inicio, fecha_fin, dias_reemplazados,
                filas_importadas, fecha_carga
         FROM tb_cargas_ventas
         ORDER BY id_carga DESC
         LIMIT $limite"
    )->fetchAll(PDO::FETCH_ASSOC);
}
