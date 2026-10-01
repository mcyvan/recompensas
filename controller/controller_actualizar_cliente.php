<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
include("../app/functions/bitacora.php");
verificarSesion();

// Obtener los datos del formulario
$nombre = strtoupper(trim($_POST['nombre']));
$apellido_p = strtoupper(trim($_POST['apellido_p']));
$apellido_m = strtoupper(trim($_POST['apellido_m']));
$telefono = trim($_POST['telefono']);
$sin_correo = isset($_POST['sin_correo']) && $_POST['sin_correo'] === '1';
$correo = $sin_correo
    ? 'sin-correo-' . preg_replace('/[^0-9]/', '', $telefono) . '@clientes.local'
    : strtolower(trim($_POST['correo'] ?? ''));
$fecha_nacimiento = $_POST['fecha_nacimiento'];
// $fecha_registro = date('Y-m-d');
$usuario = strtoupper(trim($_SESSION['id_usuario_login']));
$id_cliente = $_POST['id_cliente'];
$estatus = $_POST['id_estatus'];
$id_vendedor = $_POST['id_vendedor'];



if (empty($nombre) || empty($apellido_p) || empty($apellido_m) || empty($telefono) || empty($fecha_nacimiento) || (!$sin_correo && empty($correo))) {
    $_SESSION['mensaje_registro_cliente_existe'] = "Todos los campos son obligatorios y no pueden estar vacíos";
    header('Location: ' . $URL . '/clientes/registrar_cliente.php');
    exit(); // ¡Importante! Detiene la ejecución
}

if (!$sin_correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['mensaje_registro_cliente_existe'] = "Ingresa un correo valido o selecciona Sin correo electronico";
    header('Location: ' . $URL . '/clientes/registrar_cliente.php');
    exit();
}

try {
    // Iniciamos la transacción para "bloquear" el proceso para este usuario
    $pdo->beginTransaction();

    $stmtActual = $pdo->prepare(
        "SELECT nombres, apellido_p, apellido_m, correo, telefono, fecha_nacimiento, id_usuario, estatus
         FROM tb_clientes WHERE id_cliente = ? FOR UPDATE"
    );
    $stmtActual->execute([$id_cliente]);
    $clienteActual = $stmtActual->fetch(PDO::FETCH_ASSOC);

    if (!$clienteActual) {
        throw new Exception("El cliente no existe");
    }

    if (($_SESSION['rol'] ?? '') === 'VENDEDOR') {
        // El vendedor solo edita sus propios clientes y solo sus datos personales:
        // no puede cambiar el estatus ni reasignar el cliente a otro vendedor.
        if ((int) $clienteActual['id_usuario'] !== (int) $_SESSION['id_usuario_login']) {
            throw new Exception("No tienes permiso para editar este cliente");
        }
        $estatus = $clienteActual['estatus'];
        $id_vendedor = $clienteActual['id_usuario'];
    }

    // 1. Actualizacion
    $sql = "UPDATE tb_clientes SET nombres = :nombres, apellido_p = :apellido_p, apellido_m = :apellido_m, 
                                   correo = :correo, telefono = :telefono, fecha_nacimiento = :fecha_nacimiento, 
                                   id_usuario = :id_usuario, estatus = :estatus                                   
                                   WHERE id_cliente = :id_cliente";

    $sentencia = $pdo->prepare($sql);
    $sentencia->execute([
        ':nombres' => $nombre,
        ':apellido_p' => $apellido_p,
        ':apellido_m' => $apellido_m,
        ':correo' => $correo,
        ':telefono' => $telefono,
        ':fecha_nacimiento' => $fecha_nacimiento,
        ':id_usuario' => $id_vendedor,
        ':id_cliente' => $id_cliente,
        ':estatus' => $estatus
    ]);

    registrarBitacoraCambios($pdo, 'CLIENTE', (int) $id_cliente, 'ACTUALIZAR', $clienteActual, [
        'nombres' => $nombre,
        'apellido_p' => $apellido_p,
        'apellido_m' => $apellido_m,
        'correo' => $correo,
        'telefono' => $telefono,
        'fecha_nacimiento' => $fecha_nacimiento,
        'id_usuario' => $id_vendedor,
        'estatus' => $estatus,
    ]);

    // 2. OBTENER EL ID (Si lo necesitas para "apartarlo" en la sesión)
    $id_recien_creado = $pdo->lastInsertId();
    $_SESSION['ultimo_cliente_id'] = $id_recien_creado; // Aquí lo guardas de forma segura

    // Confirmamos los cambios
    $pdo->commit();

    $_SESSION['mensaje_registro_clientes_correcto'] = "Cliente " . $nombre . " " . $apellido_p . " " . $apellido_m . " actualizado correctamente con telefono: " . $telefono;
    header('Location: ' . $URL . '/clientes/registrar_cliente.php');
    exit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Si algo falla, deshace todo
    }
    die("Error al registrar: " . $e->getMessage());
}
