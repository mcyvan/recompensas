<?php

function normalizarFolioRemisionOperador(string $folio): string
{
    $folio = strtoupper(trim($folio));

    if (preg_match('/^\d{6}$/', $folio)) {
        return 'RE' . $folio;
    }

    return $folio;
}

function folioRemisionOperadorValido(string $folio): bool
{
    return preg_match('/^RE\d{6}$/', $folio) === 1;
}

function rolCapturaRemisionActual(): string
{
    $rol = strtoupper(trim((string) ($_SESSION['rol'] ?? '')));
    $rolesPermitidos = ['OPERADOR', 'DOSIFICADOR', 'ADMINISTRACION', 'ADMINISTRADOR'];

    return in_array($rol, $rolesPermitidos, true) ? $rol : 'OPERADOR';
}

function verificarPermisoRemisiones(): void
{
    if (!in_array($_SESSION['rol'] ?? '', ['ADMINISTRADOR', 'ADMINISTRACION', 'ADMIN CANJE', 'LOGISTICA'], true)) {
        http_response_code(403);
        exit('No tienes permiso para acceder al modulo de remisiones.');
    }
}

function puedeAdministrarRemisiones(): bool
{
    return in_array($_SESSION['rol'] ?? '', ['ADMINISTRADOR', 'ADMINISTRACION'], true);
}

function verificarPermisoAdministrarRemisiones(): void
{
    if (!puedeAdministrarRemisiones()) {
        http_response_code(403);
        exit('No tienes permiso para administrar remisiones.');
    }
}

function remisionesManualesDosificadorHabilitadas(PDO $pdo): bool
{
    try {
        $stmt = $pdo->prepare(
            "SELECT habilitado
             FROM tb_configuracion_modulos
             WHERE clave = 'remisiones_manuales_dosificador'
             LIMIT 1"
        );
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

function puedeRegistrarRemisionManual(PDO $pdo): bool
{
    $rol = $_SESSION['rol'] ?? '';

    if (in_array($rol, ['ADMINISTRADOR', 'ADMINISTRACION'], true)) {
        return true;
    }

    return $rol === 'DOSIFICADOR' && remisionesManualesDosificadorHabilitadas($pdo);
}

function verificarPermisoRegistrarRemisionManual(PDO $pdo): void
{
    if (!puedeRegistrarRemisionManual($pdo)) {
        http_response_code(403);
        $urlBase = rtrim((string) ($GLOBALS['URL'] ?? '/recompensas'), '/');
        $urlLogistica = defined('LOGISTICA_LOGIN_URL') ? LOGISTICA_LOGIN_URL : '/logistica/login';
        $urlLoginAdmin = $urlBase . '/login/cerrar_sesion.php?destino=login';

        exit(
            '<!doctype html><html lang="es"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Acceso deshabilitado</title>'
            . '<style>body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;display:grid;place-items:center;min-height:100vh}'
            . '.card{background:#fff;max-width:520px;margin:20px;padding:32px;border-radius:12px;box-shadow:0 8px 28px rgba(0,0,0,.12);text-align:center}'
            . 'a{display:block;margin-top:12px;padding:12px;border-radius:7px;text-decoration:none;background:#0d6efd;color:#fff}'
            . 'a.secundario{background:#6c757d}</style></head><body><main class="card">'
            . '<h2>Registro manual deshabilitado</h2>'
            . '<p>El administrador todavía no ha habilitado las remisiones manuales para dosificadores.</p>'
            . '<a href="' . htmlspecialchars($urlLoginAdmin, ENT_QUOTES, 'UTF-8') . '">Entrar como administrador</a>'
            . '<a class="secundario" href="' . htmlspecialchars($urlLogistica, ENT_QUOTES, 'UTF-8') . '">Regresar a Logistica</a>'
            . '</main></body></html>'
        );
    }
}

function columnasTabla(PDO $pdo, string $tabla): array
{
    static $cache = [];

    if (!isset($cache[$tabla])) {
        $stmt = $pdo->query("SHOW COLUMNS FROM $tabla");
        $cache[$tabla] = array_flip(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field'));
    }

    return $cache[$tabla];
}

function selectCamposCrmRemision(PDO $pdo): string
{
    $columnas = columnasTabla($pdo, 'tb_remisiones');
    $campos = [
        'qr_origen',
        'factura_crm',
        'pedido_crm',
        'fecha_crm',
        'planta_crm',
        'vendedor_crm',
        'articulo_crm',
        'cantidad_crm',
        'precio_crm',
        'cliente_crm',
    ];

    $selects = [];
    foreach ($campos as $campo) {
        $selects[] = isset($columnas[$campo]) ? "r.$campo" : "NULL AS $campo";
    }

    return implode(",\n            ", $selects);
}

function selectCamposCamionRemision(PDO $pdo): string
{
    $columnas = columnasTabla($pdo, 'tb_remisiones');
    $campos = [
        'id_camion_logistica',
        'camion_logistica',
    ];

    $selects = [];
    foreach ($campos as $campo) {
        $selects[] = isset($columnas[$campo]) ? "r.$campo" : "NULL AS $campo";
    }

    return implode(",\n            ", $selects);
}

function filtrosListadoRemisiones(array $entrada): array
{
    $fechaInicio = trim($entrada['lista_fecha_inicio'] ?? '');
    $fechaFin = trim($entrada['lista_fecha_fin'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
        $fechaInicio = date('Y-m-01');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
        $fechaFin = date('Y-m-d');
    }

    if ($fechaInicio > $fechaFin) {
        [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
    }

    return [
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'fecha_inicio_sql' => $fechaInicio . ' 00:00:00',
        'fecha_fin_sql' => date('Y-m-d H:i:s', strtotime($fechaFin . ' +1 day')),
    ];
}

function obtenerRemisiones(PDO $pdo, ?array $filtros = null, int $limite = 1000): array
{
    $limite = max(50, min($limite, 5000));
    $selectCrm = selectCamposCrmRemision($pdo);
    $selectCamion = selectCamposCamionRemision($pdo);
    $columnasRemisiones = columnasTabla($pdo, 'tb_remisiones');
    $selectRolCaptura = isset($columnasRemisiones['rol_captura'])
        ? 'r.rol_captura'
        : 'NULL AS rol_captura';
    $where = '';
    $parametros = [];

    if ($filtros) {
        $where = 'WHERE r.hora_inicio >= ? AND r.hora_inicio < ?';
        $parametros[] = $filtros['fecha_inicio_sql'];
        $parametros[] = $filtros['fecha_fin_sql'];
    }

    $stmt = $pdo->prepare(
        "SELECT
            r.id_remision,
            r.id_cliente,
            r.telefono,
            r.folio_remision,
            r.volumen,
            r.hora_inicio,
            r.hora_fin,
            r.minutos_colado,
            r.puntos,
            r.estatus,
            $selectRolCaptura,
            $selectCrm,
            $selectCamion,
            c.nombres,
            c.apellido_p,
            c.apellido_m,
            COALESCE(vendedor.usuario, NULLIF(UPPER(TRIM(r.vendedor_crm)), '')) AS vendedor,
            u.usuario AS operador
         FROM tb_remisiones r
         LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
         LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
         INNER JOIN tb_usuarios u ON u.id_usuario = r.id_operador
         $where
         ORDER BY r.hora_inicio DESC, r.id_remision DESC
         LIMIT $limite"
    );
    $stmt->execute($parametros);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerRemision(PDO $pdo, int $idRemision): ?array
{
    $stmt = $pdo->prepare(
        "SELECT
            r.id_remision,
            r.id_cliente,
            r.telefono,
            r.id_operador,
            r.folio_remision,
            r.volumen,
            r.hora_inicio,
            r.hora_fin,
            r.minutos_colado,
            r.puntos,
            r.estatus,
            r.planta_crm,
            r.cliente_crm,
            r.vendedor_crm,
            r.sin_cliente_captura,
            c.nombres,
            c.apellido_p,
            c.apellido_m,
            vendedor.usuario AS vendedor,
            u.usuario AS operador
         FROM tb_remisiones r
         LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
         LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
         INNER JOIN tb_usuarios u ON u.id_usuario = r.id_operador
         WHERE r.id_remision = ?"
    );
    $stmt->execute([$idRemision]);
    $remision = $stmt->fetch(PDO::FETCH_ASSOC);

    return $remision ?: null;
}

function obtenerOperadores(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT u.id_usuario, u.usuario
         FROM tb_usuarios u
         INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
         INNER JOIN tb_roles r ON r.id_rol = ud.id_rol
         WHERE r.rol = 'OPERADOR' AND u.estatus = 1
         ORDER BY u.usuario"
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerVendedoresRemisiones(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT u.id_usuario, u.usuario
         FROM tb_usuarios u
         INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
         INNER JOIN tb_roles r ON r.id_rol = ud.id_rol
         WHERE r.rol = 'VENDEDOR' AND u.estatus = 1
         ORDER BY u.usuario"
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function joinVendedorComercialReporte(): string
{
    return "LEFT JOIN tb_usuarios_detalle vendedor_detalle ON vendedor_detalle.id_usuario = vendedor.id_usuario
            LEFT JOIN (
                SELECT UPPER(TRIM(remision)) AS remision,
                       CASE
                           WHEN UPPER(MAX(TRIM(vendedor))) LIKE '%AMERICAS%' THEN 'AMERICAS'
                           WHEN UPPER(MAX(TRIM(vendedor))) LIKE '%MOSTRADOR%' THEN 'MOSTRADOR'
                           WHEN UPPER(MAX(TRIM(vendedor))) LIKE '%JAIME RODRIGUEZ%' THEN 'JAIME RODRIGUEZ'
                           WHEN UPPER(MAX(TRIM(vendedor))) LIKE '%CLARA CRUZ%' THEN 'CLARA CRUZ'
                           WHEN UPPER(MAX(TRIM(vendedor))) LIKE '%VERONICA REYES%' THEN 'VERONICA REYES'
                           ELSE REGEXP_REPLACE(UPPER(MAX(TRIM(vendedor))), '^[0-9]+[[:space:]]+', '')
                       END AS vendedor_archivo
                FROM tb_ventas_empresa
                WHERE es_concreto = 1
                GROUP BY UPPER(TRIM(remision))
            ) venta_reporte
              ON venta_reporte.remision = UPPER(TRIM(r.folio_remision))";
}

function expresionVendedorComercialReporte(): string
{
    // Prioriza el nombre del vendedor asignado al cliente en Recompensas (siempre
    // el mismo por usuario) sobre el nombre que trae el archivo de ventas, que
    // solo existe para las remisiones que ya se conciliaron y antes partia a un
    // mismo vendedor en dos filas distintas ("MIGUEL RODRIGUEZ" vs "MRODRIGUEZ").
    return "COALESCE(
        NULLIF(TRIM(CONCAT(vendedor_detalle.nombres, ' ', vendedor_detalle.apellido_p)), ''),
        NULLIF(venta_reporte.vendedor_archivo, ''),
        NULLIF(UPPER(TRIM(r.vendedor_crm)), ''),
        NULLIF(TRIM(vendedor.usuario), ''),
        'Sin vendedor'
    )";
}

// Etiquetas de vendedor tal como salen de la consulta (una misma persona puede
// aparecer escrita distinto segun la fuente: usuario, archivo de ventas o QR).
function etiquetasVendedorComercialReporte(PDO $pdo): array
{
    $joinVentas = joinVendedorComercialReporte();
    $expresion = expresionVendedorComercialReporte();
    $stmt = $pdo->query(
        "SELECT DISTINCT $expresion AS vendedor
         FROM tb_remisiones r
         LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
         LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
         $joinVentas
         WHERE r.estatus <> 'CANCELADO'
         ORDER BY vendedor"
    );
    return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
}

function nombreCanonicoVendedor(string $etiqueta, array $nombresPorClave): string
{
    $clave = preg_replace('/\s+/u', '', claveVendedorVenta($etiqueta));
    return $nombresPorClave[$clave] ?? $etiqueta;
}

// Lista para el filtro: un solo nombre por vendedor (sin duplicados por apellido).
function obtenerVendedoresComercialesReporte(PDO $pdo): array
{
    if (!function_exists('claveVendedorVenta')) {
        require_once __DIR__ . '/conciliacion_ventas.php';
    }
    $nombresPorClave = nombresVendedoresPorClave($pdo);

    $nombres = [];
    foreach (etiquetasVendedorComercialReporte($pdo) as $etiqueta) {
        $nombres[nombreCanonicoVendedor($etiqueta, $nombresPorClave)] = true;
    }
    $nombres = array_keys($nombres);
    sort($nombres);

    return $nombres;
}

// Todas las etiquetas que corresponden al vendedor elegido en el filtro.
function etiquetasEquivalentesVendedor(PDO $pdo, string $nombre): array
{
    if (!function_exists('claveVendedorVenta')) {
        require_once __DIR__ . '/conciliacion_ventas.php';
    }
    $nombresPorClave = nombresVendedoresPorClave($pdo);

    $etiquetas = [$nombre];
    foreach (etiquetasVendedorComercialReporte($pdo) as $etiqueta) {
        if (nombreCanonicoVendedor($etiqueta, $nombresPorClave) === $nombre) {
            $etiquetas[] = $etiqueta;
        }
    }

    return array_values(array_unique($etiquetas));
}

function prepararFechaInput(?string $fecha): string
{
    if (!$fecha) {
        return '';
    }

    return date('Y-m-d\TH:i', strtotime($fecha));
}

function etiquetaEstatusRemision(string $estatus): string
{
    return match ($estatus) {
        'FINALIZADO' => 'text-bg-success',
        'CANCELADO' => 'text-bg-danger',
        'EN PROCESO' => 'text-bg-warning',
        default => 'text-bg-secondary',
    };
}

function formatoTiempoRemision($minutos): string
{
    if ($minutos === null || $minutos === '') {
        return '-';
    }

    $totalMinutos = max(0, (int) round((float) $minutos));
    $horas = intdiv($totalMinutos, 60);
    $minutosRestantes = $totalMinutos % 60;

    if ($horas > 0) {
        return $horas . ' h ' . $minutosRestantes . ' min';
    }

    return $minutosRestantes . ' min';
}

function filtrosReporteRemisiones(array $entrada, ?PDO $pdo = null): array
{
    $fechaInicio = trim($entrada['fecha_inicio'] ?? '');
    $fechaFin = trim($entrada['fecha_fin'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
        $fechaInicio = date('Y-m-01');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
        $fechaFin = date('Y-m-d');
    }

    if ($fechaInicio > $fechaFin) {
        [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
    }

    $estatus = strtoupper(trim($entrada['estatus'] ?? ''));
    if (!in_array($estatus, ['', 'EN PROCESO', 'FINALIZADO'], true)) {
        $estatus = '';
    }

    $idOperador = filter_var($entrada['id_operador'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $vendedor = trim((string) ($entrada['vendedor'] ?? ''));
    $cliente = strtoupper(trim($entrada['cliente'] ?? ''));

    return [
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'fecha_inicio_sql' => $fechaInicio . ' 00:00:00',
        'fecha_fin_sql' => date('Y-m-d H:i:s', strtotime($fechaFin . ' +1 day')),
        'cliente' => $cliente,
        'id_operador' => $idOperador,
        'vendedor' => $vendedor,
        'vendedor_etiquetas' => ($vendedor !== '' && $pdo) ? etiquetasEquivalentesVendedor($pdo, $vendedor) : [$vendedor],
        'estatus' => $estatus,
    ];
}

function condicionesReporteRemisiones(array $filtros, array &$parametros): string
{
    $condiciones = [
        'r.hora_inicio >= ?',
        'r.hora_inicio < ?',
        "r.estatus <> 'CANCELADO'",
    ];
    $parametros[] = $filtros['fecha_inicio_sql'];
    $parametros[] = $filtros['fecha_fin_sql'];

    if ($filtros['cliente'] !== '') {
        $condiciones[] = "(r.telefono LIKE ? OR c.nombres LIKE ? OR c.apellido_p LIKE ? OR c.apellido_m LIKE ? OR CONCAT(c.nombres, ' ', c.apellido_p, ' ', c.apellido_m) LIKE ?)";
        $busqueda = '%' . $filtros['cliente'] . '%';
        array_push($parametros, $busqueda, $busqueda, $busqueda, $busqueda, $busqueda);
    }

    if ($filtros['id_operador']) {
        $condiciones[] = 'r.id_operador = ?';
        $parametros[] = $filtros['id_operador'];
    }

    if ($filtros['vendedor'] !== '') {
        $etiquetas = $filtros['vendedor_etiquetas'] ?: [$filtros['vendedor']];
        $condiciones[] = expresionVendedorComercialReporte() . ' IN (' . implode(',', array_fill(0, count($etiquetas), '?')) . ')';
        array_push($parametros, ...$etiquetas);
    }

    if ($filtros['estatus'] !== '') {
        $condiciones[] = 'r.estatus = ?';
        $parametros[] = $filtros['estatus'];
    }

    return 'WHERE ' . implode(' AND ', $condiciones);
}

function obtenerResumenReporteRemisiones(PDO $pdo, array $filtros): array
{
    $parametros = [];
    $where = condicionesReporteRemisiones($filtros, $parametros);
    $joinVentas = joinVendedorComercialReporte();
    $vendedorComercial = expresionVendedorComercialReporte();

    $stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_remisiones,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.volumen ELSE 0 END), 0) AS total_metros,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.puntos ELSE 0 END), 0) AS total_puntos,
            COUNT(DISTINCT c.id_cliente) AS total_clientes,
            COUNT(DISTINCT $vendedorComercial) AS total_vendedores,
            COUNT(DISTINCT r.id_operador) AS total_choferes,
            COALESCE(AVG(CASE WHEN r.estatus <> 'CANCELADO' THEN r.volumen ELSE NULL END), 0) AS promedio_metros,
            COALESCE(AVG(CASE WHEN r.estatus <> 'CANCELADO' AND r.minutos_colado IS NOT NULL THEN r.minutos_colado ELSE NULL END), 0) AS promedio_minutos,
            SUM(CASE WHEN r.estatus = 'FINALIZADO' THEN 1 ELSE 0 END) AS finalizadas,
            SUM(CASE WHEN r.estatus = 'EN PROCESO' THEN 1 ELSE 0 END) AS en_proceso,
            SUM(CASE WHEN r.estatus = 'CANCELADO' THEN 1 ELSE 0 END) AS canceladas
         FROM tb_remisiones r
         LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
         LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
         $joinVentas
         $where"
    );
    $stmt->execute($parametros);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function obtenerReporteRemisionesPorCliente(PDO $pdo, array $filtros, int $limite = 200): array
{
    $limite = max(10, min($limite, 1000));
    $parametros = [];
    $where = condicionesReporteRemisiones($filtros, $parametros);
    $joinVentas = joinVendedorComercialReporte();
    $vendedorComercial = expresionVendedorComercialReporte();

    $stmt = $pdo->prepare(
        "SELECT
            c.id_cliente,
            r.telefono,
            COALESCE(NULLIF(TRIM(CONCAT(c.nombres, ' ', c.apellido_p, ' ', c.apellido_m)), ''), 'SIN CLIENTE REGISTRADO') AS cliente,
            $vendedorComercial AS vendedor,
            COUNT(*) AS total_remisiones,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.volumen ELSE 0 END), 0) AS total_metros,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.puntos ELSE 0 END), 0) AS total_puntos,
            MIN(r.hora_inicio) AS primera_remision,
            MAX(r.hora_inicio) AS ultima_remision
         FROM tb_remisiones r
         LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
         LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
         $joinVentas
         $where
         GROUP BY c.id_cliente, r.telefono, c.nombres, c.apellido_p, c.apellido_m, $vendedorComercial
         ORDER BY total_metros DESC, total_remisiones DESC
         LIMIT $limite"
    );
    $stmt->execute($parametros);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerReporteRemisionesPorVendedor(PDO $pdo, array $filtros): array
{
    $parametros = [];
    $where = condicionesReporteRemisiones($filtros, $parametros);
    $joinVentas = joinVendedorComercialReporte();
    $vendedorComercial = expresionVendedorComercialReporte();

    $stmt = $pdo->prepare(
        "SELECT
            $vendedorComercial AS vendedor,
            COUNT(*) AS total_remisiones,
            GROUP_CONCAT(DISTINCT c.id_cliente) AS ids_clientes,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.volumen ELSE 0 END), 0) AS total_metros,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.puntos ELSE 0 END), 0) AS total_puntos,
            MAX(r.hora_inicio) AS ultima_remision
         FROM tb_remisiones r
         LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
         LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
         $joinVentas
         $where
         GROUP BY $vendedorComercial"
    );
    $stmt->execute($parametros);

    // El nombre puede venir de dos fuentes (usuario del cliente: "SAMUEL TORRES";
    // archivo de ventas: "SAMUEL TORRES CARDONA"). Se unifica por clave de vendedor
    // (inicial + primer apellido) para que una misma persona sea una sola fila.
    if (!function_exists('claveVendedorVenta')) {
        require_once __DIR__ . '/conciliacion_ventas.php';
    }
    $nombresPorClave = nombresVendedoresPorClave($pdo);

    $resultado = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $etiqueta = (string) $fila['vendedor'];
        $clave = preg_replace('/\s+/u', '', claveVendedorVenta($etiqueta));
        $nombre = $nombresPorClave[$clave] ?? $etiqueta;

        if (!isset($resultado[$nombre])) {
            $resultado[$nombre] = [
                'vendedor' => $nombre,
                'total_remisiones' => 0,
                'clientes' => [],
                'total_metros' => 0.0,
                'total_puntos' => 0.0,
                'ultima_remision' => null,
            ];
        }
        $resultado[$nombre]['total_remisiones'] += (int) $fila['total_remisiones'];
        $resultado[$nombre]['total_metros'] += (float) $fila['total_metros'];
        $resultado[$nombre]['total_puntos'] += (float) $fila['total_puntos'];
        foreach (array_filter(explode(',', (string) $fila['ids_clientes'])) as $idCliente) {
            $resultado[$nombre]['clientes'][$idCliente] = true;
        }
        if ($fila['ultima_remision'] !== null && ($resultado[$nombre]['ultima_remision'] === null || $fila['ultima_remision'] > $resultado[$nombre]['ultima_remision'])) {
            $resultado[$nombre]['ultima_remision'] = $fila['ultima_remision'];
        }
    }

    foreach ($resultado as &$fila) {
        $fila['total_clientes'] = count($fila['clientes']);
        unset($fila['clientes']);
    }
    unset($fila);

    $resultado = array_values($resultado);
    usort($resultado, static fn($a, $b) => $b['total_metros'] <=> $a['total_metros'] ?: $b['total_remisiones'] <=> $a['total_remisiones']);

    return $resultado;
}

// clave de vendedor (inicial + primer apellido, sin espacios) => nombre completo
// (nombres + apellido paterno) de cada usuario con rol VENDEDOR.
function nombresVendedoresPorClave(PDO $pdo): array
{
    $vendedores = $pdo->query(
        "SELECT u.usuario, TRIM(CONCAT(ud.nombres, ' ', ud.apellido_p)) AS nombre
         FROM tb_usuarios u
         INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
         INNER JOIN tb_roles r ON r.id_rol = ud.id_rol
         WHERE r.rol IN ('VENDEDOR', 'VENDEDORES')"
    )->fetchAll(PDO::FETCH_ASSOC);

    $nombresPorClave = [];
    foreach ($vendedores as $fila) {
        $clave = preg_replace('/\s+/u', '', strtoupper((string) $fila['usuario']));
        $nombresPorClave[$clave] = $fila['nombre'] !== '' ? $fila['nombre'] : $fila['usuario'];
    }

    return $nombresPorClave;
}

function obtenerReporteRemisionesPorChofer(PDO $pdo, array $filtros): array
{
    $parametros = [];
    $where = condicionesReporteRemisiones($filtros, $parametros);
    $joinVentas = joinVendedorComercialReporte();

    $stmt = $pdo->prepare(
        "SELECT
            u.id_usuario,
            u.usuario AS operador,
            COUNT(*) AS total_remisiones,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.volumen ELSE 0 END), 0) AS total_metros,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.puntos ELSE 0 END), 0) AS total_puntos,
            COALESCE(AVG(CASE WHEN r.estatus <> 'CANCELADO' AND r.minutos_colado IS NOT NULL THEN r.minutos_colado ELSE NULL END), 0) AS promedio_minutos,
            SUM(CASE WHEN r.estatus <> 'CANCELADO' AND r.minutos_colado > 45 THEN 1 ELSE 0 END) AS fuera_tiempo
         FROM tb_remisiones r
         LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
         LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
         $joinVentas
         INNER JOIN tb_usuarios u ON u.id_usuario = r.id_operador
         $where
         GROUP BY u.id_usuario, u.usuario
         ORDER BY total_metros DESC, total_remisiones DESC"
    );
    $stmt->execute($parametros);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerReporteRemisionesPorDia(PDO $pdo, array $filtros): array
{
    $parametros = [];
    $where = condicionesReporteRemisiones($filtros, $parametros);
    $joinVentas = joinVendedorComercialReporte();

    $stmt = $pdo->prepare(
        "SELECT
            DATE(r.hora_inicio) AS fecha,
            COUNT(*) AS total_remisiones,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.volumen ELSE 0 END), 0) AS total_metros,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.puntos ELSE 0 END), 0) AS total_puntos
         FROM tb_remisiones r
         LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
         LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
         $joinVentas
         $where
         GROUP BY DATE(r.hora_inicio)
         ORDER BY fecha DESC"
    );
    $stmt->execute($parametros);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Medicion de remisiones capturadas "sin cliente registrado".
// - sin_cliente: cuantas se marcaron asi al capturarlas.
// - pendientes: siguen sin cliente ligado.
// - cliente_existia: ya se ligaron y el cliente ya estaba dado de alta antes del dia de la
//   descarga (el operador debio encontrarlo: omision de captura).
// - alta_posterior: ya se ligaron y el cliente se dio de alta el mismo dia o despues
//   (el vendedor lo registro tarde).
function seleccionMedicionSinCliente(): string
{
    return "COUNT(*) AS total_remisiones,
            SUM(CASE WHEN r.sin_cliente_captura = 1 THEN 1 ELSE 0 END) AS sin_cliente,
            SUM(CASE WHEN r.sin_cliente_captura = 1 AND r.id_cliente IS NULL THEN 1 ELSE 0 END) AS pendientes,
            SUM(CASE WHEN r.sin_cliente_captura = 1 AND r.id_cliente IS NOT NULL AND c.fecha_registro < DATE(r.hora_inicio) THEN 1 ELSE 0 END) AS cliente_existia,
            SUM(CASE WHEN r.sin_cliente_captura = 1 AND r.id_cliente IS NOT NULL AND c.fecha_registro >= DATE(r.hora_inicio) THEN 1 ELSE 0 END) AS alta_posterior";
}

function obtenerMedicionSinClientePorChofer(PDO $pdo, array $filtros): array
{
    $parametros = [];
    $where = condicionesReporteRemisiones($filtros, $parametros);
    $joinVentas = joinVendedorComercialReporte();
    $seleccion = seleccionMedicionSinCliente();

    $stmt = $pdo->prepare(
        "SELECT u.usuario AS chofer, $seleccion
         FROM tb_remisiones r
         LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
         LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
         $joinVentas
         INNER JOIN tb_usuarios u ON u.id_usuario = r.id_operador
         $where
         GROUP BY u.id_usuario, u.usuario
         ORDER BY sin_cliente DESC, total_remisiones DESC"
    );
    $stmt->execute($parametros);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Agrupa por vendedor: el del cliente ligado; si no hay, el del archivo de ventas y por
// ultimo el que trae el QR. Los nombres se unifican contra los usuarios VENDEDOR.
function obtenerMedicionSinClientePorVendedor(PDO $pdo, array $filtros): array
{
    if (!function_exists('claveVendedorVenta')) {
        require_once __DIR__ . '/conciliacion_ventas.php';
    }

    $parametros = [];
    $where = condicionesReporteRemisiones($filtros, $parametros);
    $joinVentas = joinVendedorComercialReporte();
    $seleccion = seleccionMedicionSinCliente();

    $stmt = $pdo->prepare(
        "SELECT COALESCE(
                    NULLIF(TRIM(CONCAT(vendedor_detalle.nombres, ' ', vendedor_detalle.apellido_p)), ''),
                    NULLIF(venta_reporte.vendedor_archivo, ''),
                    NULLIF(UPPER(TRIM(r.vendedor_crm)), ''),
                    NULLIF(TRIM(vendedor.usuario), ''),
                    'Sin vendedor'
                ) AS vendedor, $seleccion
         FROM tb_remisiones r
         LEFT JOIN tb_clientes c ON c.id_cliente = r.id_cliente
         LEFT JOIN tb_usuarios vendedor ON vendedor.id_usuario = c.id_usuario
         $joinVentas
         INNER JOIN tb_usuarios u ON u.id_usuario = r.id_operador
         $where
         GROUP BY vendedor"
    );
    $stmt->execute($parametros);

    $nombresPorClave = nombresVendedoresPorClave($pdo);

    $campos = ['total_remisiones', 'sin_cliente', 'pendientes', 'cliente_existia', 'alta_posterior'];
    $resultado = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $etiqueta = (string) $fila['vendedor'];
        $clave = preg_replace('/\s+/u', '', claveVendedorVenta($etiqueta));
        $nombre = $nombresPorClave[$clave] ?? $etiqueta;

        if (!isset($resultado[$nombre])) {
            $resultado[$nombre] = ['vendedor' => $nombre] + array_fill_keys($campos, 0);
        }
        foreach ($campos as $campo) {
            $resultado[$nombre][$campo] += (int) $fila[$campo];
        }
    }

    $resultado = array_values($resultado);
    usort($resultado, static fn($a, $b) => $b['sin_cliente'] <=> $a['sin_cliente'] ?: $b['total_remisiones'] <=> $a['total_remisiones']);

    return $resultado;
}
