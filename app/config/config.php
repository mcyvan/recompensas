<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name('RECOMPENSAS_SESSID');
}

$configFile = __DIR__ . '/database.local.php';

if (!is_file($configFile)) {
    error_log('Falta el archivo privado de configuración.');
    http_response_code(500);
    exit('La aplicación no está configurada correctamente.');
}

$config = require $configFile;

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $config['host'],
    $config['database']
);

try {
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No fue posible conectar con la base de datos.');
}

$URL = $config['url'];
define('SSO_LOGISTICA_SECRET', $config['sso_logistica_secret'] ?? '');
define('LOGISTICA_LOGIN_URL', $config['logistica_login_url'] ?? '/logistica/login');
date_default_timezone_set('America/Mexico_City');
