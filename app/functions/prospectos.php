<?php

// Modulo de prospeccion de obras. Reglas de negocio:
// - Un prospecto es una obra nueva que un vendedor esta cotizando.
// - El vendedor solo ve y edita los suyos; ADMINISTRADOR y ADMINISTRACION ven todos.
// - Todo cambio de estatus o nota queda en tb_prospectos_seguimiento.
// - Al marcar PERDIDA es obligatorio el motivo (y el competidor si se la dio otro).

const PROSPECTO_ESTATUS = [
    'NUEVO' => ['Nuevo', 'secondary'],
    'COTIZACION_ENTREGADA' => ['Cotizacion entregada', 'info'],
    'EN_REVISION_CLIENTE' => ['En revision por el cliente', 'primary'],
    'EN_NEGOCIACION' => ['En negociacion', 'warning'],
    'PAUSADA' => ['Pausada', 'dark'],
    'GANADA' => ['Ganada', 'success'],
    'PERDIDA' => ['Perdida', 'danger'],
];

const PROSPECTO_ESTATUS_ABIERTOS = ['NUEVO', 'COTIZACION_ENTREGADA', 'EN_REVISION_CLIENTE', 'EN_NEGOCIACION', 'PAUSADA'];

const PROSPECTO_TIPOS_OBRA = ['COMERCIAL', 'INDUSTRIAL', 'INFRAESTRUCTURA', 'VIVIENDA EN SERIE', 'PARTICULAR'];

const PROSPECTO_MOTIVO_COMPETIDOR = 'SE LA DIERON A UN COMPETIDOR';

const PROSPECTO_MOTIVOS_PERDIDA = [
    'PRECIO',
    'PLAZO DE PAGO O CREDITO',
    PROSPECTO_MOTIVO_COMPETIDOR,
    'PROYECTO CANCELADO O POSPUESTO',
    'EL CLIENTE LO RESUELVE POR OTRO MEDIO',
    'SIN RESPUESTA DEL CLIENTE',
    'OTRO',
];

const PROSPECTO_COMPETIDORES = [
    'GCC',
    'ACONSA',
    'PERMONT',
    'CODEC',
    'CONCRETO DN',
    'CONCRETOS CHIHUAHUA',
];

const PROSPECTO_NOMBRE_PROPIO = 'CONCRETOS AMERICAS';

// Dias sin ningun movimiento para marcar una obra abierta como olvidada.
const PROSPECTO_DIAS_SIN_MOVIMIENTO = 14;

function prospectoEsAdministracion(): bool
{
    return in_array($_SESSION['rol'] ?? '', ['ADMINISTRADOR', 'ADMINISTRACION'], true);
}

function prospectoEsVendedor(): bool
{
    return in_array($_SESSION['rol'] ?? '', ['VENDEDOR', 'VENDEDORES'], true);
}

function prospectoRolPermitido(): bool
{
    return prospectoEsAdministracion() || prospectoEsVendedor();
}

function prospectoEtiquetaEstatus(string $estatus): string
{
    return PROSPECTO_ESTATUS[$estatus][0] ?? $estatus;
}

function prospectoBadgeEstatus(string $estatus): string
{
    [$etiqueta, $color] = PROSPECTO_ESTATUS[$estatus] ?? [$estatus, 'secondary'];

    return '<span class="badge text-bg-' . $color . '">' . htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') . '</span>';
}

// Texto en mayusculas y sin espacios repetidos, como se manejan los nombres en el resto del sistema.
function prospectoTexto(mixed $valor): string
{
    $valor = preg_replace('/\s+/u', ' ', trim((string) $valor));

    return mb_strtoupper((string) $valor, 'UTF-8');
}

function prospectoFechaValida(mixed $valor): ?string
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        return null;
    }
    $fecha = DateTime::createFromFormat('Y-m-d', $valor);

    return ($fecha && $fecha->format('Y-m-d') === $valor) ? $valor : null;
}

function prospectoVendedores(PDO $pdo): array
{
    return $pdo->query(
        "SELECT u.id_usuario,
                COALESCE(NULLIF(TRIM(CONCAT(ud.nombres, ' ', ud.apellido_p)), ''), u.usuario) AS nombre
         FROM tb_usuarios u
         INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
         INNER JOIN tb_roles r ON r.id_rol = ud.id_rol
         WHERE r.rol IN ('VENDEDOR', 'VENDEDORES') AND u.estatus = 1 AND UPPER(u.usuario) <> 'MOSTRADOR'
         ORDER BY nombre"
    )->fetchAll(PDO::FETCH_ASSOC);
}

// Competidores sugeridos: los del catalogo mas los que los vendedores ya hayan escrito.
function prospectoCompetidoresConocidos(PDO $pdo): array
{
    $usados = $pdo->query(
        "SELECT DISTINCT competidor FROM tb_prospectos_obras
         WHERE activo = 1 AND competidor IS NOT NULL AND competidor <> ''"
    )->fetchAll(PDO::FETCH_COLUMN);
    $todos = array_unique(array_merge(PROSPECTO_COMPETIDORES, $usados));
    sort($todos);

    return array_values(array_diff($todos, [PROSPECTO_NOMBRE_PROPIO]));
}

// Lista de prospectos; $idUsuario limita a un vendedor (null = todos).
function prospectoListar(PDO $pdo, ?int $idUsuario): array
{
    $sql = "SELECT p.*,
                   COALESCE(NULLIF(TRIM(CONCAT(ud.nombres, ' ', ud.apellido_p)), ''), u.usuario) AS vendedor,
                   COALESCE(
                       (SELECT MAX(s.fecha) FROM tb_prospectos_seguimiento s WHERE s.id_prospecto = p.id_prospecto),
                       p.fecha_registro
                   ) AS ultimo_movimiento
            FROM tb_prospectos_obras p
            INNER JOIN tb_usuarios u ON u.id_usuario = p.id_usuario
            LEFT JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
            WHERE p.activo = 1";
    $parametros = [];
    if ($idUsuario !== null) {
        $sql .= ' AND p.id_usuario = ?';
        $parametros[] = $idUsuario;
    }
    $sql .= ' ORDER BY p.fecha_registro DESC, p.id_prospecto DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function prospectoObtener(PDO $pdo, int $idProspecto): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM tb_prospectos_obras WHERE id_prospecto = ? AND activo = 1');
    $stmt->execute([$idProspecto]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function prospectoPuedeVer(array $prospecto): bool
{
    return prospectoEsAdministracion()
        || (prospectoEsVendedor() && (int) $prospecto['id_usuario'] === (int) ($_SESSION['id_usuario_login'] ?? 0));
}

function prospectoSeguimientos(PDO $pdo, int $idProspecto): array
{
    $stmt = $pdo->prepare(
        "SELECT s.id_seguimiento, s.estatus_anterior, s.estatus_nuevo, s.nota, s.fecha,
                COALESCE(NULLIF(TRIM(CONCAT(ud.nombres, ' ', ud.apellido_p)), ''), u.usuario) AS autor
         FROM tb_prospectos_seguimiento s
         LEFT JOIN tb_usuarios u ON u.id_usuario = s.id_usuario
         LEFT JOIN tb_usuarios_detalle ud ON ud.id_usuario = s.id_usuario
         WHERE s.id_prospecto = ?
         ORDER BY s.fecha DESC, s.id_seguimiento DESC"
    );
    $stmt->execute([$idProspecto]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function prospectoRegistrarSeguimiento(PDO $pdo, int $idProspecto, ?string $anterior, string $nuevo, string $nota): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO tb_prospectos_seguimiento (id_prospecto, id_usuario, estatus_anterior, estatus_nuevo, nota, fecha)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $idProspecto,
        (int) ($_SESSION['id_usuario_login'] ?? 0),
        $anterior,
        $nuevo,
        $nota !== '' ? $nota : null,
        date('Y-m-d H:i:s'),
    ]);
}

// Valida los datos de la obra (alta y edicion). Devuelve [datos limpios, errores].
function prospectoValidarDatos(array $entrada): array
{
    $errores = [];
    $datos = [
        'cliente' => prospectoTexto($entrada['cliente'] ?? ''),
        'proyecto' => prospectoTexto($entrada['proyecto'] ?? ''),
        'tipo_obra' => prospectoTexto($entrada['tipo_obra'] ?? ''),
        'tipos_concreto' => prospectoTexto($entrada['tipos_concreto'] ?? ''),
        'competidor' => prospectoTexto($entrada['competidor'] ?? ''),
        'comentarios' => trim((string) ($entrada['comentarios'] ?? '')),
        'fecha_inicio' => prospectoFechaValida($entrada['fecha_inicio'] ?? ''),
        'fecha_fin' => prospectoFechaValida($entrada['fecha_fin'] ?? ''),
    ];

    if ($datos['cliente'] === '' || mb_strlen($datos['cliente']) > 150) {
        $errores[] = 'Escribe el cliente (maximo 150 caracteres).';
    }
    if ($datos['proyecto'] === '' || mb_strlen($datos['proyecto']) > 200) {
        $errores[] = 'Escribe el nombre del proyecto (maximo 200 caracteres).';
    }
    if (!in_array($datos['tipo_obra'], PROSPECTO_TIPOS_OBRA, true)) {
        $errores[] = 'Elige el tipo de obra.';
    }
    if (mb_strlen($datos['tipos_concreto']) > 200) {
        $errores[] = 'Los tipos de concreto son muy largos (maximo 200 caracteres).';
    }
    if (mb_strlen($datos['competidor']) > 80) {
        $errores[] = 'El competidor es muy largo (maximo 80 caracteres).';
    }
    if (mb_strlen($datos['comentarios']) > 2000) {
        $errores[] = 'Los comentarios son muy largos (maximo 2000 caracteres).';
    }

    $volumen = str_replace(',', '', trim((string) ($entrada['volumen_m3'] ?? '')));
    if (!is_numeric($volumen) || (float) $volumen <= 0 || (float) $volumen > 500000) {
        $errores[] = 'El volumen debe ser mayor a 0 m3.';
    }
    $datos['volumen_m3'] = round((float) $volumen, 2);

    if (trim((string) ($entrada['fecha_inicio'] ?? '')) !== '' && $datos['fecha_inicio'] === null) {
        $errores[] = 'La fecha de inicio no es valida.';
    }
    if (trim((string) ($entrada['fecha_fin'] ?? '')) !== '' && $datos['fecha_fin'] === null) {
        $errores[] = 'La fecha de fin no es valida.';
    }
    if ($datos['fecha_inicio'] && $datos['fecha_fin'] && $datos['fecha_fin'] < $datos['fecha_inicio']) {
        $errores[] = 'La fecha de fin no puede ser anterior a la de inicio.';
    }

    return [$datos, $errores];
}

// Valida un cambio de estatus. Devuelve [estatus, motivo, competidor, errores].
function prospectoValidarEstatus(string $estatus, string $motivo, string $competidor): array
{
    $errores = [];
    $motivo = prospectoTexto($motivo);
    $competidor = prospectoTexto($competidor);

    if (!isset(PROSPECTO_ESTATUS[$estatus])) {
        return [$estatus, null, null, ['El estatus no es valido.']];
    }

    if ($estatus === 'PERDIDA') {
        if (!in_array($motivo, PROSPECTO_MOTIVOS_PERDIDA, true)) {
            $errores[] = 'Elige el motivo por el que se perdio la obra.';
        }
        if ($motivo === PROSPECTO_MOTIVO_COMPETIDOR && $competidor === '') {
            $errores[] = 'Escribe a que competidor se la dieron.';
        }
    } else {
        $motivo = '';
    }

    if ($estatus === 'GANADA') {
        $competidor = PROSPECTO_NOMBRE_PROPIO;
    } elseif ($competidor === PROSPECTO_NOMBRE_PROPIO) {
        $competidor = '';
    }

    return [$estatus, $motivo !== '' ? $motivo : null, $competidor !== '' ? $competidor : null, $errores];
}

function prospectoDiasSinMovimiento(array $fila): int
{
    $ultimo = strtotime((string) $fila['ultimo_movimiento']);

    return $ultimo ? max(0, (int) floor((time() - $ultimo) / 86400)) : 0;
}

// Indicadores generales. Cierre = ganadas / (ganadas + perdidas), en numero de obras.
function prospectoIndicadores(array $filas): array
{
    $r = [
        'total' => count($filas), 'abiertas' => 0, 'ganadas' => 0, 'perdidas' => 0,
        'm3_abiertos' => 0.0, 'm3_ganados' => 0.0, 'm3_perdidos' => 0.0, 'olvidadas' => 0,
    ];
    foreach ($filas as $fila) {
        $m3 = (float) $fila['volumen_m3'];
        if ($fila['estatus'] === 'GANADA') {
            $r['ganadas']++;
            $r['m3_ganados'] += $m3;
        } elseif ($fila['estatus'] === 'PERDIDA') {
            $r['perdidas']++;
            $r['m3_perdidos'] += $m3;
        } else {
            $r['abiertas']++;
            $r['m3_abiertos'] += $m3;
            if (prospectoDiasSinMovimiento($fila) >= PROSPECTO_DIAS_SIN_MOVIMIENTO) {
                $r['olvidadas']++;
            }
        }
    }
    $cerradas = $r['ganadas'] + $r['perdidas'];
    $r['cierre'] = $cerradas > 0 ? $r['ganadas'] * 100 / $cerradas : null;

    return $r;
}

// Resumen para administracion: por vendedor, por estatus, motivos y competidores.
function prospectoResumen(array $filas): array
{
    $porVendedor = [];
    $porEstatus = array_fill_keys(array_keys(PROSPECTO_ESTATUS), ['obras' => 0, 'm3' => 0.0]);
    $motivos = [];
    $competidores = [];

    foreach ($filas as $fila) {
        $vendedor = (string) $fila['vendedor'];
        $m3 = (float) $fila['volumen_m3'];
        $estatus = (string) $fila['estatus'];

        $porVendedor[$vendedor] ??= [
            'obras' => 0, 'abiertas' => 0, 'ganadas' => 0, 'perdidas' => 0,
            'm3_abiertos' => 0.0, 'm3_ganados' => 0.0, 'm3_perdidos' => 0.0, 'olvidadas' => 0,
        ];
        $porVendedor[$vendedor]['obras']++;
        if ($estatus === 'GANADA') {
            $porVendedor[$vendedor]['ganadas']++;
            $porVendedor[$vendedor]['m3_ganados'] += $m3;
        } elseif ($estatus === 'PERDIDA') {
            $porVendedor[$vendedor]['perdidas']++;
            $porVendedor[$vendedor]['m3_perdidos'] += $m3;
        } else {
            $porVendedor[$vendedor]['abiertas']++;
            $porVendedor[$vendedor]['m3_abiertos'] += $m3;
            if (prospectoDiasSinMovimiento($fila) >= PROSPECTO_DIAS_SIN_MOVIMIENTO) {
                $porVendedor[$vendedor]['olvidadas']++;
            }
        }

        if (isset($porEstatus[$estatus])) {
            $porEstatus[$estatus]['obras']++;
            $porEstatus[$estatus]['m3'] += $m3;
        }

        if ($estatus === 'PERDIDA') {
            $motivo = (string) ($fila['motivo_perdida'] ?: 'SIN MOTIVO');
            $motivos[$motivo] ??= ['obras' => 0, 'm3' => 0.0];
            $motivos[$motivo]['obras']++;
            $motivos[$motivo]['m3'] += $m3;
            if ($fila['motivo_perdida'] === PROSPECTO_MOTIVO_COMPETIDOR && $fila['competidor']) {
                $competidores[$fila['competidor']] ??= ['obras' => 0, 'm3' => 0.0];
                $competidores[$fila['competidor']]['obras']++;
                $competidores[$fila['competidor']]['m3'] += $m3;
            }
        }
    }

    ksort($porVendedor);
    uasort($motivos, static fn($a, $b) => $b['obras'] <=> $a['obras']);
    uasort($competidores, static fn($a, $b) => $b['obras'] <=> $a['obras']);

    return [
        'por_vendedor' => $porVendedor,
        'por_estatus' => $porEstatus,
        'motivos' => $motivos,
        'competidores' => $competidores,
    ];
}
