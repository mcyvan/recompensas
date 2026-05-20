<?php

/**
 * Límite de peticiones por IP usando archivos (sin Redis).
 * Pensado para endpoints públicos como consulta de saldos.
 */
function obtenerIpCliente(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function rateLimitPermitir(string $clave, int $maxIntentos = 15, int $ventanaSegundos = 60): bool
{
    $dir = dirname(__DIR__) . '/storage/rate_limit';

    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return true;
    }

    $ip = obtenerIpCliente();
    $archivo = $dir . '/' . hash('sha256', $clave . '|' . $ip) . '.json';
    $ahora = time();

    $datos = ['count' => 0, 'start' => $ahora];
    $fp = @fopen($archivo, 'c+');

    if ($fp === false) {
        return true;
    }

    flock($fp, LOCK_EX);
    rewind($fp);

    $contenido = stream_get_contents($fp);

    if ($contenido !== false && $contenido !== '') {
        $decodificado = json_decode($contenido, true);

        if (is_array($decodificado)) {
            $datos = $decodificado;
        }
    }

    if ($ahora - ($datos['start'] ?? 0) >= $ventanaSegundos) {
        $datos = ['count' => 0, 'start' => $ahora];
    }

    if (($datos['count'] ?? 0) >= $maxIntentos) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return false;
    }

    $datos['count'] = ($datos['count'] ?? 0) + 1;

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($datos));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}

function rateLimitRechazarJson(string $mensaje = 'Demasiadas consultas. Espera un momento e intenta de nuevo.'): void
{
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $mensaje,
    ]);
    exit;
}
