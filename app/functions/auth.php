<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name('RECOMPENSAS_SESSID');
    session_start();
}

function verificarSesion()
{
    if (!isset($_SESSION['usuario'])) {
        // Redirigir a la página de login si no hay sesión activa
        header('Location: ' . 'recompensas' . '/login');  // Ajusta la URL según tu ruta
        exit(); // Termina el script para que no continúe ejecutándose
    }

    $rolesSoloCanjes = ['CANJE', 'ADMIN CANJE'];
    $rol = $_SESSION['rol'] ?? '';

    if (in_array($rol, $rolesSoloCanjes, true)) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $controladoresPermitidos = [
            'controller_confirmar_canje.php',
            'controller_cancelar_canje.php',
        ];
        $esModuloCanjes = str_contains($script, '/canjes/');
        $esControladorCanjes = in_array(basename($script), $controladoresPermitidos, true);

        if (!$esModuloCanjes && !$esControladorCanjes) {
            header('Location: ../canjes/index.php');
            exit();
        }
    }
}
