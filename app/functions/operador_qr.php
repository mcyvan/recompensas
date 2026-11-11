<?php

// Piloto de la captura de remisiones "solo QR" del operador.
// La lista de usuarios que ya la usan sale de app/config/operador_qr_piloto.local.php
// (un arreglo con los usuarios; ['*'] = todos). Sin ese archivo nadie la usa y todos
// siguen con la pantalla de siempre.
function operadorUsaFlujoQr(): bool
{
    static $usuarios = null;

    if ($usuarios === null) {
        $archivo = __DIR__ . '/../config/operador_qr_piloto.local.php';
        $lista = is_file($archivo) ? require $archivo : [];
        $usuarios = array_map(
            static fn($usuario) => strtoupper(trim((string) $usuario)),
            is_array($lista) ? $lista : []
        );
    }

    if (in_array('*', $usuarios, true)) {
        return true;
    }

    $usuarioActual = strtoupper(trim((string) ($_SESSION['usuario'] ?? '')));

    return $usuarioActual !== '' && in_array($usuarioActual, $usuarios, true);
}
