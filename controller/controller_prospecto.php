<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/bitacora.php');
require_once('../app/functions/prospectos.php');
verificarSesion();

header('Content-Type: application/json; charset=utf-8');

function respuestaProspecto(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!prospectoRolPermitido()) {
    respuestaProspecto(['ok' => false, 'error' => 'No tienes permiso para esta accion.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuestaProspecto(['ok' => false, 'error' => 'Metodo no permitido.'], 405);
}

$token = $_POST['csrf_token'] ?? '';
$tokenSesion = $_SESSION['csrf_prospectos'] ?? '';
if ($tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
    respuestaProspecto(['ok' => false, 'error' => 'La solicitud expiro. Recarga la pagina.'], 403);
}

$accion = (string) ($_POST['accion'] ?? '');
$idUsuarioSesion = (int) ($_SESSION['id_usuario_login'] ?? 0);

// Obra existente a la que el usuario tiene acceso; si no, corta con el error.
function prospectoDelPedido(PDO $pdo): array
{
    $id = filter_input(INPUT_POST, 'id_prospecto', FILTER_VALIDATE_INT);
    $prospecto = $id ? prospectoObtener($pdo, $id) : null;
    if (!$prospecto || !prospectoPuedeVer($prospecto)) {
        respuestaProspecto(['ok' => false, 'error' => 'La obra no existe o no tienes acceso a ella.'], 404);
    }

    return $prospecto;
}

try {
    if ($accion === 'detalle') {
        $prospecto = prospectoDelPedido($pdo);
        $seguimientos = prospectoSeguimientos($pdo, (int) $prospecto['id_prospecto']);
        foreach ($seguimientos as &$s) {
            $s['etiqueta_anterior'] = $s['estatus_anterior'] ? prospectoEtiquetaEstatus($s['estatus_anterior']) : null;
            $s['etiqueta_nueva'] = prospectoEtiquetaEstatus($s['estatus_nuevo']);
            $s['color_nuevo'] = PROSPECTO_ESTATUS[$s['estatus_nuevo']][1] ?? 'secondary';
            $s['fecha_texto'] = date('d/m/Y H:i', strtotime($s['fecha']));
        }
        unset($s);
        respuestaProspecto(['ok' => true, 'prospecto' => $prospecto, 'seguimientos' => $seguimientos]);
    }

    if ($accion === 'guardar') {
        $esNueva = trim((string) ($_POST['id_prospecto'] ?? '')) === '';
        $actual = $esNueva ? null : prospectoDelPedido($pdo);

        [$datos, $errores] = prospectoValidarDatos($_POST);

        // El vendedor siempre captura a su nombre; administracion elige de quien es la obra.
        $duenio = $idUsuarioSesion;
        if (prospectoEsAdministracion()) {
            $duenio = $esNueva
                ? (int) filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT)
                : (int) $actual['id_usuario'];
            $validos = array_column(prospectoVendedores($pdo), 'id_usuario');
            if (!in_array($duenio, array_map('intval', $validos), true)) {
                $errores[] = 'Elige el vendedor de la obra.';
            }
        } elseif (!$esNueva) {
            $duenio = (int) $actual['id_usuario'];
        }

        $estatus = 'NUEVO';
        if ($esNueva) {
            $estatus = (string) ($_POST['estatus'] ?? 'NUEVO');
            if (!in_array($estatus, PROSPECTO_ESTATUS_ABIERTOS, true)) {
                $estatus = 'NUEVO';
            }
        }

        if ($errores) {
            respuestaProspecto(['ok' => false, 'error' => implode(' ', $errores)], 422);
        }

        $ahora = date('Y-m-d H:i:s');
        if ($esNueva) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO tb_prospectos_obras
                    (id_usuario, cliente, telefono, proyecto, direccion, latitud, longitud, tipo_obra, tipos_concreto, volumen_m3,
                     fecha_inicio, fecha_fin, estatus, competidor, comentarios, activo, fecha_registro, fecha_actualizacion)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)'
            );
            $stmt->execute([
                $duenio, $datos['cliente'], $datos['telefono'] !== '' ? $datos['telefono'] : null, $datos['proyecto'],
                $datos['direccion'] !== '' ? $datos['direccion'] : null, $datos['latitud'], $datos['longitud'],
                $datos['tipo_obra'], $datos['tipos_concreto'],
                $datos['volumen_m3'], $datos['fecha_inicio'], $datos['fecha_fin'], $estatus,
                $datos['competidor'] !== '' ? $datos['competidor'] : null,
                $datos['comentarios'] !== '' ? $datos['comentarios'] : null, $ahora, $ahora,
            ]);
            $idNuevo = (int) $pdo->lastInsertId();
            prospectoRegistrarSeguimiento($pdo, $idNuevo, null, $estatus, 'Obra registrada.');
            $pdo->commit();
            registrarBitacoraCambios($pdo, 'PROSPECTO', $idNuevo, 'CREAR', [], [
                'cliente' => $datos['cliente'], 'proyecto' => $datos['proyecto'], 'volumen_m3' => $datos['volumen_m3'],
            ]);
            respuestaProspecto(['ok' => true, 'id' => $idNuevo]);
        }

        $stmt = $pdo->prepare(
            'UPDATE tb_prospectos_obras
             SET cliente = ?, telefono = ?, proyecto = ?, direccion = ?, latitud = ?, longitud = ?, tipo_obra = ?,
                 tipos_concreto = ?, volumen_m3 = ?, fecha_inicio = ?, fecha_fin = ?, competidor = ?, comentarios = ?,
                 fecha_actualizacion = ?
             WHERE id_prospecto = ?'
        );
        $stmt->execute([
            $datos['cliente'], $datos['telefono'] !== '' ? $datos['telefono'] : null, $datos['proyecto'],
            $datos['direccion'] !== '' ? $datos['direccion'] : null, $datos['latitud'], $datos['longitud'],
            $datos['tipo_obra'], $datos['tipos_concreto'], $datos['volumen_m3'],
            $datos['fecha_inicio'], $datos['fecha_fin'],
            // En una obra ganada el competidor es siempre la empresa; no se toca desde la edicion.
            $actual['estatus'] === 'GANADA' ? $actual['competidor'] : ($datos['competidor'] !== '' ? $datos['competidor'] : null),
            $datos['comentarios'] !== '' ? $datos['comentarios'] : null, $ahora,
            (int) $actual['id_prospecto'],
        ]);
        registrarBitacoraCambios($pdo, 'PROSPECTO', (int) $actual['id_prospecto'], 'EDITAR', $actual, [
            'cliente' => $datos['cliente'], 'telefono' => $datos['telefono'] !== '' ? $datos['telefono'] : null,
            'proyecto' => $datos['proyecto'], 'tipo_obra' => $datos['tipo_obra'],
            'direccion' => $datos['direccion'] !== '' ? $datos['direccion'] : null,
            'latitud' => $datos['latitud'] !== null ? number_format($datos['latitud'], 7, '.', '') : null,
            'longitud' => $datos['longitud'] !== null ? number_format($datos['longitud'], 7, '.', '') : null,
            'tipos_concreto' => $datos['tipos_concreto'], 'volumen_m3' => number_format($datos['volumen_m3'], 2, '.', ''),
            'fecha_inicio' => $datos['fecha_inicio'], 'fecha_fin' => $datos['fecha_fin'],
        ]);
        respuestaProspecto(['ok' => true, 'id' => (int) $actual['id_prospecto']]);
    }

    if ($accion === 'seguimiento') {
        $prospecto = prospectoDelPedido($pdo);
        $nota = trim((string) ($_POST['nota'] ?? ''));
        if (mb_strlen($nota) > 2000) {
            respuestaProspecto(['ok' => false, 'error' => 'La nota es muy larga (maximo 2000 caracteres).'], 422);
        }

        [$estatus, $motivo, $detalleMotivo, $competidor, $errores] = prospectoValidarEstatus(
            (string) ($_POST['estatus'] ?? ''),
            (string) ($_POST['motivo_perdida'] ?? ''),
            (string) ($_POST['motivo_detalle'] ?? ''),
            (string) ($_POST['competidor'] ?? '')
        );
        if (!$errores && mb_strlen((string) $competidor) > 80) {
            $errores[] = 'El competidor es muy largo (maximo 80 caracteres).';
        }
        $cambioEstatus = $estatus !== $prospecto['estatus'];
        // En una obra ya perdida, corregir el motivo tambien cuenta como movimiento.
        $cambioMotivo = $motivo !== $prospecto['motivo_perdida'] || $detalleMotivo !== $prospecto['motivo_detalle'];
        if (!$errores && !$cambioEstatus && !$cambioMotivo && $nota === '') {
            $errores[] = 'Escribe una nota o cambia el estatus.';
        }
        if ($errores) {
            respuestaProspecto(['ok' => false, 'error' => implode(' ', $errores)], 422);
        }

        // Si no se escribio competidor se conserva el que ya tenia, salvo que fuera la propia empresa
        // (obra que estaba ganada y se regresa a otro estatus).
        $competidorFinal = $competidor
            ?? ($prospecto['competidor'] === PROSPECTO_NOMBRE_PROPIO ? null : $prospecto['competidor']);

        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'UPDATE tb_prospectos_obras
             SET estatus = ?, motivo_perdida = ?, motivo_detalle = ?, competidor = ?, fecha_actualizacion = ?
             WHERE id_prospecto = ?'
        );
        $stmt->execute([$estatus, $motivo, $detalleMotivo, $competidorFinal, date('Y-m-d H:i:s'), (int) $prospecto['id_prospecto']]);
        prospectoRegistrarSeguimiento(
            $pdo,
            (int) $prospecto['id_prospecto'],
            $prospecto['estatus'],
            $estatus,
            $nota
        );
        $pdo->commit();

        if ($cambioEstatus || $cambioMotivo) {
            registrarBitacoraCambios($pdo, 'PROSPECTO', (int) $prospecto['id_prospecto'], 'ESTATUS', $prospecto, [
                'estatus' => $estatus, 'motivo_perdida' => $motivo, 'motivo_detalle' => $detalleMotivo,
                'competidor' => $competidorFinal,
            ]);
        }
        respuestaProspecto(['ok' => true]);
    }

    if ($accion === 'eliminar') {
        if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
            respuestaProspecto(['ok' => false, 'error' => 'Solo un administrador puede eliminar obras.'], 403);
        }
        $prospecto = prospectoDelPedido($pdo);
        $stmt = $pdo->prepare('UPDATE tb_prospectos_obras SET activo = 0, fecha_actualizacion = ? WHERE id_prospecto = ?');
        $stmt->execute([date('Y-m-d H:i:s'), (int) $prospecto['id_prospecto']]);
        registrarBitacoraCambios($pdo, 'PROSPECTO', (int) $prospecto['id_prospecto'], 'ELIMINAR', ['activo' => 1], ['activo' => 0]);
        respuestaProspecto(['ok' => true]);
    }

    respuestaProspecto(['ok' => false, 'error' => 'Accion no valida.'], 400);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    respuestaProspecto(['ok' => false, 'error' => 'No fue posible guardar. Intenta de nuevo.'], 500);
}
