<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/functions/correo.php';

$bloqueo = $pdo->query("SELECT GET_LOCK('recompensas_alertas_descargas', 1)")->fetchColumn();
if ((int) $bloqueo !== 1) {
    exit("Otro proceso de alertas esta activo.\n");
}

try {
    // Usa la misma hora y zona horaria configurada en la aplicación.
    $ahora = date('Y-m-d H:i:s');

    // Auto-cierre de remisiones que llevan más de 45 minutos EN PROCESO sin
    // finalizar. Evita que un operador quede bloqueado (menu_operador.php no
    // permite iniciar una remisión nueva mientras tenga una EN PROCESO) y
    // refleja la misma regla de puntos que controller_finalizar_remision.php
    // (más de 45 minutos = 0 puntos).
    $UMBRAL_AUTO_CIERRE_MINUTOS = 45;

    $stmtCierre = $pdo->prepare(
        "SELECT id_remision, folio_remision, id_cliente,
            TIMESTAMPDIFF(MINUTE, hora_inicio, ?) AS minutos_transcurridos
         FROM tb_remisiones
         WHERE estatus = 'EN PROCESO'
           AND hora_fin IS NULL
           AND TIMESTAMPDIFF(MINUTE, hora_inicio, ?) > ?
         ORDER BY hora_inicio"
    );
    $stmtCierre->execute([$ahora, $ahora, $UMBRAL_AUTO_CIERRE_MINUTOS]);
    $remisionesCierre = $stmtCierre->fetchAll(PDO::FETCH_ASSOC);

    $actualizarCierre = $pdo->prepare(
        "UPDATE tb_remisiones
         SET hora_fin = ?, minutos_colado = ?, puntos = 0, estatus = 'FINALIZADO'
         WHERE id_remision = ? AND estatus = 'EN PROCESO'"
    );
    $insertarMovimientoCierre = $pdo->prepare(
        "INSERT INTO tb_movimientos_puntos
            (id_cliente, id_remision, tipo, puntos, fecha_vencimiento, observaciones)
         VALUES (?, ?, 'ACUMULACION', 0, '2026-12-20', 'Remisión finalizada automáticamente después de 45 minutos')"
    );

    foreach ($remisionesCierre as $remisionCierre) {
        $minutos = (int) $remisionCierre['minutos_transcurridos'];
        $actualizarCierre->execute([$ahora, $minutos, $remisionCierre['id_remision']]);

        if ($actualizarCierre->rowCount() > 0) {
            $insertarMovimientoCierre->execute([
                $remisionCierre['id_cliente'],
                $remisionCierre['id_remision'],
            ]);
            echo "Remision cerrada automaticamente: {$remisionCierre['folio_remision']}\n";
        }
    }

    if (!$remisionesCierre) {
        echo "Sin remisiones para auto-cierre.\n";
    }

    $configuracion = $pdo->query(
        'SELECT habilitado, umbral_minutos, correos
         FROM tb_configuracion_alertas WHERE id_configuracion = 1 LIMIT 1'
    )->fetch(PDO::FETCH_ASSOC);

    if (!$configuracion || (int) $configuracion['habilitado'] !== 1) {
        exit("Alertas deshabilitadas.\n");
    }

    $correos = array_values(array_filter(array_map(
        'trim',
        preg_split('/[\s,;]+/', (string) $configuracion['correos']) ?: []
    )));
    $umbral = max(30, (int) $configuracion['umbral_minutos']);

    $stmt = $pdo->prepare(
        "SELECT r.id_remision, r.folio_remision, r.hora_inicio, r.camion_logistica,
            u.usuario AS operador,
            TIMESTAMPDIFF(MINUTE, r.hora_inicio, ?) AS minutos_transcurridos
     FROM tb_remisiones r
     INNER JOIN tb_usuarios u ON u.id_usuario = r.id_operador
     LEFT JOIN tb_alertas_remisiones a
       ON a.id_remision = r.id_remision AND a.tipo = 'DESCARGA_PROLONGADA'
     WHERE r.estatus = 'EN PROCESO'
       AND r.hora_fin IS NULL
       AND TIMESTAMPDIFF(MINUTE, r.hora_inicio, ?) >= ?
       AND a.fecha_envio IS NULL
     ORDER BY r.hora_inicio"
    );

    $stmt->execute([$ahora, $ahora, $umbral]);
    $remisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $guardarIntento = $pdo->prepare(
        "INSERT INTO tb_alertas_remisiones
            (id_remision, tipo, fecha_envio, intentos, ultimo_error, fecha_ultimo_intento)
         VALUES (?, 'DESCARGA_PROLONGADA', ?, 1, ?, NOW())
         ON DUPLICATE KEY UPDATE
            fecha_envio = VALUES(fecha_envio),
            intentos = intentos + 1,
            ultimo_error = VALUES(ultimo_error),
            fecha_ultimo_intento = NOW()"
    );

    foreach ($remisiones as $remision) {
        $minutos = (int) $remision['minutos_transcurridos'];
        $duracion = intdiv($minutos, 60) . ' h ' . ($minutos % 60) . ' min';
        $folio = htmlspecialchars((string) $remision['folio_remision'], ENT_QUOTES, 'UTF-8');
        $operador = htmlspecialchars((string) $remision['operador'], ENT_QUOTES, 'UTF-8');
        $camion = htmlspecialchars((string) ($remision['camion_logistica'] ?: 'No registrado'), ENT_QUOTES, 'UTF-8');
        $inicio = date('d/m/Y H:i', strtotime((string) $remision['hora_inicio']));
        $html = "<h2>Descarga con tiempo prolongado</h2>"
            . "<p>La remision <strong>$folio</strong> continua en proceso.</p>"
            . "<table cellpadding=\"7\" cellspacing=\"0\" border=\"1\">"
            . "<tr><td><strong>Chofer</strong></td><td>$operador</td></tr>"
            . "<tr><td><strong>Camion</strong></td><td>$camion</td></tr>"
            . "<tr><td><strong>Inicio</strong></td><td>$inicio</td></tr>"
            . "<tr><td><strong>Tiempo</strong></td><td>$duracion</td></tr></table>";

        try {
            enviarCorreoSmtp($MAIL_CONFIG, $correos, "Alerta de descarga: $folio", $html);
            $guardarIntento->execute([(int) $remision['id_remision'], date('Y-m-d H:i:s'), null]);
            echo "Alerta enviada: $folio\n";
        } catch (Throwable $e) {
            $error = mb_substr($e->getMessage(), 0, 500);
            $guardarIntento->execute([(int) $remision['id_remision'], null, $error]);
            error_log("Alerta remision $folio: $error");
            echo "Error al enviar: $folio\n";
        }
    }

    if (!$remisiones) {
        echo "Sin alertas pendientes.\n";
    }
} finally {
    $pdo->query("SELECT RELEASE_LOCK('recompensas_alertas_descargas')");
}
