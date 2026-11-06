<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/limpieza_pruebas.php');

verificarSesion();
verificarPermisoLimpiezaPruebas();

$redireccion = $URL . '/administracion/limpieza_pruebas.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redireccion);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
$tokenSesion = $_SESSION['csrf_limpieza_pruebas'] ?? '';

if ($tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
    $_SESSION['mensaje_limpieza_error'] = 'La solicitud expiro. Intenta nuevamente.';
    header('Location: ' . $redireccion);
    exit;
}

$confirmacion = trim($_POST['confirmacion'] ?? '');
$idsClientes = $_POST['ids_clientes'] ?? [];

if ($confirmacion !== 'ELIMINAR PRUEBAS') {
    $_SESSION['mensaje_limpieza_error'] = 'Debes escribir ELIMINAR PRUEBAS para confirmar.';
    header('Location: ' . $redireccion);
    exit;
}

if (!is_array($idsClientes) || !$idsClientes) {
    $_SESSION['mensaje_limpieza_error'] = 'No hay clientes seleccionados para limpiar.';
    header('Location: ' . $redireccion);
    exit;
}

try {
    $pdo->beginTransaction();
    $resumen = ejecutarLimpiezaPruebas($pdo, $idsClientes);
    $pdo->commit();

    $_SESSION['csrf_limpieza_pruebas'] = bin2hex(random_bytes(32));
    $_SESSION['mensaje_limpieza_correcto'] =
        'Limpieza completada: ' .
        (int) $resumen['clientes'] . ' cliente(s), ' .
        (int) $resumen['remisiones'] . ' remision(es), ' .
        (int) $resumen['canjes'] . ' canje(s).';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    $_SESSION['mensaje_limpieza_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'No fue posible completar la limpieza.';
}

header('Location: ' . $redireccion);
exit;
