<?php
include('../app/config/config.php');
include('../app/functions/auth.php');
include('../app/functions/correo.php');
verificarSesion();

$redireccion = $URL . '/configuracion/alertas_descargas.php';
if (($_SESSION['rol'] ?? '') !== 'ADMINISTRADOR') {
    http_response_code(403);
    exit('No tienes permiso para modificar esta configuracion.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redireccion);
    exit();
}

$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_alertas_descargas']) || !hash_equals($_SESSION['csrf_alertas_descargas'], $token)) {
    $_SESSION['mensaje_alertas_error'] = 'La solicitud expiro. Intenta nuevamente.';
    header('Location: ' . $redireccion);
    exit();
}

$habilitado = isset($_POST['habilitado']) ? 1 : 0;
$umbral = filter_var($_POST['umbral_minutos'] ?? null, FILTER_VALIDATE_INT);
$correosTexto = trim((string) ($_POST['correos'] ?? ''));
$correos = array_values(array_unique(array_filter(array_map(
    'trim',
    preg_split('/[\s,;]+/', $correosTexto) ?: []
))));
$invalidos = array_filter($correos, static fn ($correo) => !filter_var($correo, FILTER_VALIDATE_EMAIL));

if ($umbral === false || $umbral < 30 || $umbral > 1440) {
    $_SESSION['mensaje_alertas_error'] = 'El tiempo debe estar entre 30 y 1,440 minutos.';
    header('Location: ' . $redireccion);
    exit();
}
if ($invalidos || ($habilitado && !$correos)) {
    $_SESSION['mensaje_alertas_error'] = 'Revisa los correos destinatarios.';
    header('Location: ' . $redireccion);
    exit();
}

try {
    $stmt = $pdo->prepare(
        'UPDATE tb_configuracion_alertas
         SET habilitado = ?, umbral_minutos = ?, correos = ?,
             id_usuario_actualizacion = ?, fecha_actualizacion = NOW()
         WHERE id_configuracion = 1'
    );
    $stmt->execute([
        $habilitado,
        $umbral,
        implode("\n", $correos),
        (int) ($_SESSION['id_usuario_login'] ?? 0),
    ]);

    if (($_POST['accion'] ?? '') === 'probar') {
        if (!$correos) {
            throw new RuntimeException('Agrega al menos un destinatario para enviar la prueba.');
        }
        enviarCorreoSmtp(
            $MAIL_CONFIG,
            $correos,
            'Prueba de alertas - Recompensas',
            '<h2>Alertas Recompensas</h2><p>La configuracion SMTP funciona correctamente.</p>'
        );
        $_SESSION['mensaje_alertas_correcto'] = 'Configuracion guardada y correo de prueba enviado.';
    } else {
        $_SESSION['mensaje_alertas_correcto'] = 'Configuracion de alertas guardada correctamente.';
    }
    $_SESSION['csrf_alertas_descargas'] = bin2hex(random_bytes(32));
} catch (Throwable $e) {
    error_log($e->getMessage());
    $_SESSION['mensaje_alertas_error'] = 'La configuracion se guardo, pero no fue posible enviar el correo de prueba: ' . $e->getMessage();
}

header('Location: ' . $redireccion);
exit();
