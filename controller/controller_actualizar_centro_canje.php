<?php
include('../app/config/config.php');
include('../app/functions/auth.php');
verificarSesion();

$redireccion = $URL . '/configuracion/centro_canje.php';

if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    exit('No tienes permiso para modificar esta configuracion.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redireccion);
    exit();
}

$tokenRecibido = $_POST['csrf_token'] ?? '';
$tokenSesion = $_SESSION['csrf_configuracion_centro_canje'] ?? '';

if ($tokenSesion === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
    $_SESSION['mensaje_configuracion_centro_canje_error'] = 'La solicitud expiro. Intenta nuevamente.';
    header('Location: ' . $redireccion);
    exit();
}

$nombreCentro = trim((string) ($_POST['nombre_centro'] ?? ''));
$direccion = trim((string) ($_POST['direccion'] ?? ''));
$telefono = trim((string) ($_POST['telefono'] ?? ''));

if ($nombreCentro === '' || mb_strlen($nombreCentro) > 150) {
    $_SESSION['mensaje_configuracion_centro_canje_error'] = 'Ingresa un nombre de centro de canje valido.';
    header('Location: ' . $redireccion);
    exit();
}

if ($direccion === '' || mb_strlen($direccion) > 200) {
    $_SESSION['mensaje_configuracion_centro_canje_error'] = 'Ingresa una direccion valida.';
    header('Location: ' . $redireccion);
    exit();
}

if ($telefono === '' || mb_strlen($telefono) > 40) {
    $_SESSION['mensaje_configuracion_centro_canje_error'] = 'Ingresa un telefono valido.';
    header('Location: ' . $redireccion);
    exit();
}

try {
    $stmt = $pdo->prepare(
        'UPDATE tb_configuracion_centro_canje
         SET nombre_centro = ?, direccion = ?, telefono = ?, id_usuario_actualizacion = ?, fecha_actualizacion = ?
         WHERE id_configuracion = 1'
    );
    $stmt->execute([
        $nombreCentro,
        $direccion,
        $telefono,
        (int) ($_SESSION['id_usuario_login'] ?? 0),
        // Hora de PHP (zona de la app), no NOW() de MySQL: NOW() usa el
        // reloj/zona del servidor de base de datos y puede desfasar la hora.
        date('Y-m-d H:i:s'),
    ]);

    if ($stmt->rowCount() === 0) {
        $existe = $pdo->query('SELECT 1 FROM tb_configuracion_centro_canje WHERE id_configuracion = 1')->fetchColumn();
        if (!$existe) {
            throw new RuntimeException('No existe la configuracion del centro de canje.');
        }
    }

    $_SESSION['csrf_configuracion_centro_canje'] = bin2hex(random_bytes(32));
    $_SESSION['mensaje_configuracion_centro_canje_correcto'] = 'Datos del centro de canje actualizados correctamente.';
} catch (Throwable $e) {
    error_log($e->getMessage());
    $_SESSION['mensaje_configuracion_centro_canje_error'] = 'No fue posible actualizar la configuracion.';
}

header('Location: ' . $redireccion);
exit();
