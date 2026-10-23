<?php

// Registra en tb_bitacora_cambios quien cambio que. Solo guarda los campos
// cuyo valor realmente cambio; si no hubo diferencias no inserta nada.
// Un fallo al escribir la bitacora no debe impedir la operacion de negocio,
// por eso solo se reporta en el error_log.
function registrarBitacoraCambios(PDO $pdo, string $entidad, int $idRegistro, string $accion, array $antes, array $despues): void
{
    $cambios = [];
    foreach ($despues as $campo => $nuevo) {
        $anterior = $antes[$campo] ?? null;
        if ((string) $anterior !== (string) $nuevo) {
            $cambios[$campo] = ['antes' => $anterior, 'despues' => $nuevo];
        }
    }

    if (!$cambios) {
        return;
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO tb_bitacora_cambios
                (entidad, id_registro, accion, id_usuario_autor, usuario_autor, rol_autor, cambios, ip, fecha_cambio)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $entidad,
            $idRegistro,
            $accion,
            isset($_SESSION['id_usuario_login']) ? (int) $_SESSION['id_usuario_login'] : null,
            $_SESSION['usuario'] ?? null,
            $_SESSION['rol'] ?? null,
            json_encode($cambios, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
            // Hora de PHP (zona de la app), no NOW() de MySQL: NOW() usa el
            // reloj/zona del servidor de base de datos y puede desfasar la hora.
            date('Y-m-d H:i:s'),
        ]);
    } catch (PDOException $e) {
        error_log('Bitacora de cambios: ' . $e->getMessage());
    }
}

function verificarPermisoBitacora(): void
{
    if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
        http_response_code(403);
        exit('No tienes permiso para consultar la bitacora de cambios.');
    }
}

function filtrosBitacoraCambios(array $entrada): array
{
    $inicio = trim((string) ($entrada['fecha_inicio'] ?? ''));
    $fin = trim((string) ($entrada['fecha_fin'] ?? ''));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio)) {
        $inicio = date('Y-m-d', strtotime('-30 days'));
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
        $fin = date('Y-m-d');
    }
    if ($inicio > $fin) {
        [$inicio, $fin] = [$fin, $inicio];
    }

    $entidad = strtoupper(trim((string) ($entrada['entidad'] ?? '')));
    if (!in_array($entidad, ['CLIENTE', 'USUARIO', 'REMISION'], true)) {
        $entidad = '';
    }

    return [
        'fecha_inicio' => $inicio,
        'fecha_fin' => $fin,
        'entidad' => $entidad,
        'autor' => trim((string) ($entrada['autor'] ?? '')),
    ];
}

function obtenerBitacoraCambios(PDO $pdo, array $filtros, int $limite = 500): array
{
    $limite = max(1, min($limite, 2000));

    $stmt = $pdo->prepare(
        "SELECT b.id_bitacora, b.entidad, b.id_registro, b.accion, b.usuario_autor,
                b.rol_autor, b.cambios, b.ip, b.fecha_cambio,
                CASE b.entidad
                    WHEN 'CLIENTE' THEN TRIM(CONCAT(c.nombres, ' ', c.apellido_p, ' ', IFNULL(c.apellido_m, '')))
                    WHEN 'USUARIO' THEN u.usuario
                    WHEN 'REMISION' THEN rm.folio_remision
                END AS registro_nombre
         FROM tb_bitacora_cambios b
         LEFT JOIN tb_clientes c ON b.entidad = 'CLIENTE' AND c.id_cliente = b.id_registro
         LEFT JOIN tb_usuarios u ON b.entidad = 'USUARIO' AND u.id_usuario = b.id_registro
         LEFT JOIN tb_remisiones rm ON b.entidad = 'REMISION' AND rm.id_remision = b.id_registro
         WHERE b.fecha_cambio >= ? AND b.fecha_cambio < DATE_ADD(?, INTERVAL 1 DAY)
           AND (? = '' OR b.entidad = ?)
           AND (? = '' OR b.usuario_autor LIKE ?)
         ORDER BY b.id_bitacora DESC
         LIMIT $limite"
    );
    $stmt->execute([
        $filtros['fecha_inicio'],
        $filtros['fecha_fin'],
        $filtros['entidad'],
        $filtros['entidad'],
        $filtros['autor'],
        '%' . $filtros['autor'] . '%',
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function etiquetaCampoBitacora(string $entidad, string $campo): string
{
    $etiquetas = [
        'nombres' => 'Nombre(s)',
        'apellido_p' => 'Apellido paterno',
        'apellido_m' => 'Apellido materno',
        'correo' => 'Correo',
        'telefono' => 'Telefono',
        'fecha_nacimiento' => 'Fecha de nacimiento',
        'estatus' => 'Estatus',
        'usuario' => 'Usuario',
        'id_rol' => 'Rol',
        'contrasena' => 'Contrasena',
        'id_cliente' => 'Cliente',
        'id_operador' => 'Chofer',
        'folio_remision' => 'Folio',
        'volumen' => 'Volumen',
        'planta_crm' => 'Planta',
        'vendedor_crm' => 'Vendedor (sin cliente)',
        'hora_inicio' => 'Hora inicio',
        'hora_fin' => 'Hora fin',
    ];

    if ($campo === 'id_usuario') {
        return $entidad === 'CLIENTE' ? 'Vendedor asignado' : 'Usuario';
    }

    return $etiquetas[$campo] ?? $campo;
}

// $vendedores: id_usuario => usuario, $roles: id_rol => nombre del rol,
// $clientes: id_cliente => nombre completo.
function valorLegibleBitacora(string $campo, $valor, array $vendedores, array $roles, array $clientes = []): string
{
    if ($valor === null || $valor === '') {
        return '(vacio)';
    }

    return match ($campo) {
        'estatus' => in_array((string) $valor, ['1', '0'], true)
            ? ((string) $valor === '1' ? 'ACTIVO' : 'INACTIVO')
            : (string) $valor,
        'id_usuario', 'id_operador' => $vendedores[(int) $valor] ?? ('#' . $valor),
        'id_cliente' => $clientes[(int) $valor] ?? ('#' . $valor),
        'id_rol' => $roles[(int) $valor] ?? ('#' . $valor),
        default => (string) $valor,
    };
}
