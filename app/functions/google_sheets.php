<?php

function cargarConfigGoogleSheets(): array
{
    $archivo = __DIR__ . '/../config/google_sheets.local.php';
    if (!is_file($archivo)) {
        throw new RuntimeException('Falta app/config/google_sheets.local.php');
    }

    return require $archivo;
}

function base64UrlGoogleSheets(string $valor): string
{
    return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
}

// Firma un JWT de cuenta de servicio y lo cambia por un access token (flujo
// estandar OAuth2 "service account" de Google, sin librerias externas).
// Se cachea en memoria del proceso: una sincronizacion pide varias hojas y no
// tiene sentido tramitar un token nuevo por cada una.
function obtenerTokenGoogleSheets(array $config): string
{
    static $tokenCacheado = null;
    static $expiraEn = 0;

    if ($tokenCacheado !== null && time() < $expiraEn) {
        return $tokenCacheado;
    }

    $ahora = time();
    $encabezado = base64UrlGoogleSheets(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $cuerpo = base64UrlGoogleSheets(json_encode([
        'iss' => $config['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets.readonly',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $ahora,
        'exp' => $ahora + 3600,
    ]));

    $firmaValida = openssl_sign("$encabezado.$cuerpo", $firma, $config['private_key'], OPENSSL_ALGO_SHA256);
    if (!$firmaValida) {
        throw new RuntimeException('No se pudo firmar el JWT de Google (revisa private_key).');
    }

    $jwt = "$encabezado.$cuerpo." . base64UrlGoogleSheets($firma);

    $intentos = 3;
    for ($intento = 1; $intento <= $intentos; $intento++) {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($respuesta !== false) {
            break;
        }
        if ($intento === $intentos) {
            throw new RuntimeException('No se pudo contactar a Google tras ' . $intentos . ' intentos: ' . $error);
        }
        usleep(500000 * $intento);
    }

    $datos = json_decode($respuesta, true);
    if (empty($datos['access_token'])) {
        throw new RuntimeException('Google no entrego token: ' . ($datos['error_description'] ?? $respuesta));
    }

    $tokenCacheado = $datos['access_token'];
    $expiraEn = $ahora + (int) ($datos['expires_in'] ?? 3600) - 60;

    return $tokenCacheado;
}

// true si el mensaje de error de Sheets significa "pediste filas mas alla del
// tamaño actual de la hoja", que para nosotros equivale a "no hay filas nuevas".
function esErrorFueraDeRangoGoogle(string $mensaje): bool
{
    return str_contains($mensaje, 'exceeds grid limits');
}

// Devuelve las filas de una pestaña como arreglo de arreglos (una fila = un
// arreglo de celdas en texto, tal como las ve Sheets). Reintenta ante fallas de
// red transitorias (curl sin respuesta) antes de darse por vencido.
function obtenerFilasHojaGoogle(array $config, string $nombreHoja): array
{
    $token = obtenerTokenGoogleSheets($config);
    $rango = rawurlencode($nombreHoja);
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$config['spreadsheet_id']}/values/{$rango}?valueRenderOption=UNFORMATTED_VALUE&dateTimeRenderOption=FORMATTED_STRING";

    $intentos = 3;
    for ($intento = 1; $intento <= $intentos; $intento++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $respuesta = curl_exec($ch);
        $errorCurl = curl_error($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($respuesta !== false && $codigoHttp > 0) {
            break;
        }

        if ($intento === $intentos) {
            throw new RuntimeException("Error de red leyendo '$nombreHoja' tras $intentos intentos: $errorCurl");
        }

        usleep(500000 * $intento);
    }

    $datos = json_decode((string) $respuesta, true);

    if ($codigoHttp !== 200) {
        $mensaje = $datos['error']['message'] ?? (string) $respuesta;
        if (esErrorFueraDeRangoGoogle($mensaje)) {
            return [];
        }
        throw new RuntimeException("Error leyendo '$nombreHoja' (HTTP $codigoHttp): $mensaje");
    }

    return $datos['values'] ?? [];
}
