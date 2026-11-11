<?php
include('../app/config/config.php');
include('../app/functions/auth.php');
verificarSesion();

$redireccion = $URL . '/configuracion/modulos.php';

if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    exit('No tienes permiso para modificar esta configuracion.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redireccion);
    exit();
}

$tokenRecibido = $_POST['csrf_token'] ?? '';
$tokenSesion = $_SESSION['csrf_configuracion_modulos'] ?? '';

if ($tokenSesion === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
    $_SESSION['mensaje_configuracion_modulos_error'] = 'La solicitud expiro. Intenta nuevamente.';
    header('Location: ' . $redireccion);
    exit();
}

$habilitado = isset($_POST['remisiones_manuales_dosificador']) ? 1 : 0;

try {
    $stmt = $pdo->prepare(
        "INSERT INTO tb_configuracion_modulos
            (clave, habilitado, id_usuario_actualizacion, fecha_actualizacion)
         VALUES ('remisiones_manuales_dosificador', ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
            habilitado = VALUES(habilitado),
            id_usuario_actualizacion = VALUES(id_usuario_actualizacion),
            fecha_actualizacion = NOW()"
    );
    $stmt->execute([$habilitado, (int) ($_SESSION['id_usuario_login'] ?? 0)]);

    $_SESSION['csrf_configuracion_modulos'] = bin2hex(random_bytes(32));
    $_SESSION['mensaje_configuracion_modulos_correcto'] = $habilitado
        ? 'El registro manual quedo habilitado para dosificadores.'
        : 'El registro manual quedo deshabilitado para dosificadores.';
} catch (Throwable $e) {
    error_log($e->getMessage());
    $_SESSION['mensaje_configuracion_modulos_error'] = 'No fue posible actualizar la configuracion.';
}

header('Location: ' . $redireccion);
exit();
