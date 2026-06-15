<?php
include('../app/config/config.php');
include('../app/functions/auth.php');
verificarSesion();

$redireccion = $URL . '/configuracion/puntos_por_metro.php';

if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    exit('No tienes permiso para modificar esta configuracion.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redireccion);
    exit();
}

$tokenRecibido = $_POST['csrf_token'] ?? '';
$tokenSesion = $_SESSION['csrf_configuracion_puntos'] ?? '';

if ($tokenSesion === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
    $_SESSION['mensaje_configuracion_puntos_error'] = 'La solicitud expiro. Intenta nuevamente.';
    header('Location: ' . $redireccion);
    exit();
}

$puntosPorMetro = filter_input(FILTER_POST, 'puntos_por_m3', FILTER_VALIDATE_FLOAT);

if ($puntosPorMetro === false || $puntosPorMetro === null || $puntosPorMetro < 0.01 || $puntosPorMetro > 10000) {
    $_SESSION['mensaje_configuracion_puntos_error'] = 'Ingresa una cantidad valida entre 0.01 y 10,000 puntos.';
    header('Location: ' . $redireccion);
    exit();
}

try {
    $stmt = $pdo->prepare(
        'UPDATE tb_configuracion_puntos
         SET puntos_por_m3 = ?, id_usuario_actualizacion = ?, fecha_actualizacion = NOW()
         WHERE id_configuracion = 1'
    );
    $stmt->execute([
        round((float) $puntosPorMetro, 2),
        (int) ($_SESSION['id_usuario_login'] ?? 0),
    ]);

    if ($stmt->rowCount() === 0) {
        $existe = $pdo->query('SELECT 1 FROM tb_configuracion_puntos WHERE id_configuracion = 1')->fetchColumn();
        if (!$existe) {
            throw new RuntimeException('No existe la configuracion de puntos.');
        }
    }

    $_SESSION['csrf_configuracion_puntos'] = bin2hex(random_bytes(32));
    $_SESSION['mensaje_configuracion_puntos_correcto'] = 'Puntos por metro cubico actualizados correctamente.';
} catch (Throwable $e) {
    error_log($e->getMessage());
    $_SESSION['mensaje_configuracion_puntos_error'] = 'No fue posible actualizar la configuracion.';
}

header('Location: ' . $redireccion);
exit();
