<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name('RECOMPENSAS_SESSID');
    session_start();
}

function verificarSesion()
{
    if (!isset($_SESSION['usuario'])) {
        // Redirigir a la página de login si no hay sesión activa.
        // OPERADOR, DOSIFICADOR y cualquier usuario que entró vía SSO de
        // LOGISTICA deben volver al login de LOGISTICA, no al local. Esa
        // información vivía solo en $_SESSION, que ya se perdió si la sesión
        // caducó, por eso se apoya en COOKIE_ORIGEN_LOGIN (ver
        // controller_login.php y login/acceso_logistica.php).
        global $URL;
        $destino = (($_COOKIE[COOKIE_ORIGEN_LOGIN] ?? '') === 'LOGISTICA')
            ? LOGISTICA_LOGIN_URL
            : $URL . '/login';
        header('Location: ' . $destino);
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
        $esModuloRemisionesConsulta = $rol === 'ADMIN CANJE' && str_contains($script, '/remisiones/');
        $esControladorCanjes = in_array(basename($script), $controladoresPermitidos, true);

        if (!$esModuloCanjes && !$esModuloRemisionesConsulta && !$esControladorCanjes) {
            header('Location: ../canjes/index.php');
            exit();
        }
    }

    if ($rol === 'SEGUIMIENTO') {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        $esSeguimiento = str_contains($script, '/clientes/seguimiento.php')
            || basename($script) === 'controller_registrar_llamada.php';

        if (!$esSeguimiento) {
            header('Location: ../clientes/seguimiento.php');
            exit();
        }
    }

    if ($rol === 'LOGISTICA') {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $archivo = basename($script);
        $controladoresPermitidos = [
            'controller_consultar_puntos.php',
            'controller_registrar_cliente.php',
        ];
        $esClientesConsulta = str_contains($script, '/clientes/registrar_cliente.php')
            || str_contains($script, '/clientes/consultar_puntos.php')
            || str_contains($script, '/clientes/reporte_clientes_vendedor.php');
        $esRemisionesConsulta = str_contains($script, '/remisiones/index.php')
            || str_contains($script, '/remisiones/exportar_excel.php');
        $esPremiosConsulta = str_contains($script, '/premios/ver_premios.php');
        $esControladorConsulta = in_array($archivo, $controladoresPermitidos, true);

        if (!$esClientesConsulta && !$esRemisionesConsulta && !$esPremiosConsulta && !$esControladorConsulta) {
            header('Location: ../clientes/registrar_cliente.php?tab=dashboard');
            exit();
        }
    }

    if ($rol === 'DOSIFICADOR') {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $archivo = basename($script);
        $esRegistroManual = str_contains($script, '/operador/registrar_remision_manual.php')
            || $archivo === 'controller_registrar_remision_manual.php';

        if (!$esRegistroManual) {
            header('Location: ../operador/registrar_remision_manual.php');
            exit();
        }
    }
}
