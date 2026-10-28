<?php

require_once __DIR__ . '/../app/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$origenSso = $_SESSION['origen_sso'] ?? null;
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parametros['path'],
        $parametros['domain'],
        $parametros['secure'],
        $parametros['httponly']
    );
}

session_destroy();

setcookie(COOKIE_ORIGEN_LOGIN, '', time() - 42000, '/', '', !empty($_SERVER['HTTPS']), true);

$solicitaLoginLocal = ($_GET['destino'] ?? '') === 'login';
$destino = $solicitaLoginLocal
    ? $URL . '/login'
    : ($origenSso === 'LOGISTICA' ? LOGISTICA_LOGIN_URL : $URL . '/login');

header('Location: ' . $destino);
exit;
