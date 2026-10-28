<?php

function respuestaSmtp($socket, array $codigosEsperados): string
{
    $respuesta = '';
    while (($linea = fgets($socket, 515)) !== false) {
        $respuesta .= $linea;
        if (strlen($linea) < 4 || $linea[3] === ' ') {
            break;
        }
    }
    $codigo = (int) substr($respuesta, 0, 3);
    if (!in_array($codigo, $codigosEsperados, true)) {
        throw new RuntimeException('Respuesta SMTP inesperada: ' . trim($respuesta));
    }
    return $respuesta;
}

function comandoSmtp($socket, string $comando, array $codigosEsperados): string
{
    if (fwrite($socket, $comando . "\r\n") === false) {
        throw new RuntimeException('No fue posible enviar un comando al servidor SMTP.');
    }
    return respuestaSmtp($socket, $codigosEsperados);
}

function cabeceraCorreo(string $valor): string
{
    return '=?UTF-8?B?' . base64_encode($valor) . '?=';
}

function enviarCorreoSmtp(array $config, array $destinatarios, string $asunto, string $html): void
{
    foreach (['host', 'port', 'username', 'password', 'from_email'] as $campo) {
        if (empty($config[$campo])) {
            throw new RuntimeException('La configuracion SMTP esta incompleta.');
        }
    }

    $destinatarios = array_values(array_unique(array_filter(
        $destinatarios,
        static fn ($correo) => filter_var($correo, FILTER_VALIDATE_EMAIL)
    )));
    if (!$destinatarios) {
        throw new RuntimeException('No hay destinatarios validos para la alerta.');
    }

    $transporte = strtolower((string) ($config['encryption'] ?? 'ssl')) === 'ssl' ? 'ssl://' : '';
    $direccion = $transporte . $config['host'] . ':' . (int) $config['port'];
    $contexto = stream_context_create(['ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
    ]]);
    $socket = @stream_socket_client($direccion, $errno, $error, 20, STREAM_CLIENT_CONNECT, $contexto);
    if (!$socket) {
        throw new RuntimeException("No fue posible conectar al SMTP: $error ($errno).");
    }
    stream_set_timeout($socket, 20);

    try {
        respuestaSmtp($socket, [220]);
        comandoSmtp($socket, 'EHLO ' . (gethostname() ?: 'localhost'), [250]);
        comandoSmtp($socket, 'AUTH LOGIN', [334]);
        comandoSmtp($socket, base64_encode((string) $config['username']), [334]);
        comandoSmtp($socket, base64_encode((string) $config['password']), [235]);
        comandoSmtp($socket, 'MAIL FROM:<' . $config['from_email'] . '>', [250]);
        foreach ($destinatarios as $correo) {
            comandoSmtp($socket, 'RCPT TO:<' . $correo . '>', [250, 251]);
        }
        comandoSmtp($socket, 'DATA', [354]);

        $nombre = (string) ($config['from_name'] ?? 'Alertas Recompensas');
        $cabeceras = [
            'Date: ' . date(DATE_RFC2822),
            'Message-ID: <' . time() . '.' . bin2hex(random_bytes(6)) . '@' . $config['host'] . '>',
            'From: ' . cabeceraCorreo($nombre) . ' <' . $config['from_email'] . '>',
            'To: ' . implode(', ', $destinatarios),
            'Subject: ' . cabeceraCorreo($asunto),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $mensaje = implode("\r\n", $cabeceras) . "\r\n\r\n"
            . chunk_split(base64_encode($html), 76, "\r\n");
        $mensaje = preg_replace('/(?m)^\./', '..', $mensaje);
        if (fwrite($socket, $mensaje . "\r\n.\r\n") === false) {
            throw new RuntimeException('No fue posible transmitir el contenido del correo.');
        }
        respuestaSmtp($socket, [250]);
        comandoSmtp($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }
}
