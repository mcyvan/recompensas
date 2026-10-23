<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
/** @var PDO $pdo */
/** @var string $URL */
verificarSesion();
$rolSesion = $_SESSION['rol'] ?? '';
$puedeElegirVendedorCliente = in_array($rolSesion, ["ADMINISTRADOR", "ADMINISTRACION", "LOGISTICA"], true);

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
$fecha_registro = date('Y-m-d');
$id_vendedor = $_SESSION['id_usuario_login'] ?? null; // Si no viene del formulario, lo tomamos de la sesión
$token_publico = bin2hex(random_bytes(32));

if ($puedeElegirVendedorCliente) {
    $id_vendedor = (int) ($_POST['id_vendedor'] ?? 0);
}


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

if ($puedeElegirVendedorCliente && $id_vendedor <= 0) {
    $_SESSION['mensaje_registro_cliente_existe'] = "Selecciona el vendedor del cliente";
    header('Location: ' . $URL . '/clientes/registrar_cliente.php');
    exit();
}

try {
    // Iniciamos la transacción para "bloquear" el proceso para este usuario
    $pdo->beginTransaction();

    if ($puedeElegirVendedorCliente) {
        $consulta_vendedor = $pdo->prepare(
            "SELECT u.id_usuario
             FROM tb_usuarios u
             INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
             INNER JOIN tb_roles r ON r.id_rol = ud.id_rol
             WHERE u.id_usuario = :id_vendedor
               AND r.id_rol = 2
               AND u.estatus = 1
             LIMIT 1"
        );
        $consulta_vendedor->execute([':id_vendedor' => $id_vendedor]);

        if (!$consulta_vendedor->fetch()) {
            $pdo->rollBack();
            $_SESSION['mensaje_registro_cliente_existe'] = "El vendedor seleccionado no es valido";
            header('Location: ' . $URL . '/clientes/registrar_cliente.php');
            exit();
        }
    }

    // 1. Verificación de existencia
    $consulta_login = $pdo->prepare("SELECT id_cliente FROM tb_clientes WHERE correo = :correo OR telefono = :telefono");
    $consulta_login->execute([':correo' => $correo, ':telefono' => $telefono]);

    if ($consulta_login->fetch()) {
        $pdo->rollBack(); // Cancelamos si ya existe
        $_SESSION['mensaje_registro_cliente_existe'] = "El cliente ya existe";
        if ($rolSesion == "VENDEDOR") {
            header('Location: ' . $URL . '/vendedor/menu_vendedor.php');
        } else {
            header('Location: ' . $URL . '/clientes/registrar_cliente.php');
        }
        exit();
    }

    // 2. Inserción
    $sql = "INSERT INTO tb_clientes (nombres, apellido_p, apellido_m, correo, telefono, fecha_nacimiento, fecha_registro, id_usuario,estatus, token_publico) 
            VALUES (:nombres, :apellido_p, :apellido_m, :correo, :telefono, :fecha_nacimiento, :fecha_registro, :id_usuario,:estatus, :token_publico)";

    $sentencia = $pdo->prepare($sql);
    $sentencia->execute([
        ':nombres' => $nombre,
        ':apellido_p' => $apellido_p,
        ':apellido_m' => $apellido_m,
        ':correo' => $correo,
        ':telefono' => $telefono,
        ':fecha_nacimiento' => $fecha_nacimiento,
        ':fecha_registro' => $fecha_registro,
        ':id_usuario' => $id_vendedor,
        ':token_publico' => $token_publico,
        ':estatus' => 1
    ]);

    // 3. OBTENER EL ID (Si lo necesitas para "apartarlo" en la sesión)
    $id_recien_creado = $pdo->lastInsertId();
    $_SESSION['ultimo_cliente_id'] = $id_recien_creado; // Aquí lo guardas de forma segura

    // Confirmamos los cambios
    $pdo->commit();

    $_SESSION['mensaje_registro_clientes_correcto'] = "Cliente " . $nombre . " " . $apellido_p . " " . $apellido_m . " registrado correctamente con telefono: " . $telefono;
    if ($rolSesion == "VENDEDOR") {
        header('Location: ' . $URL . '/vendedor/menu_vendedor.php');
    } else {
        header('Location: ' . $URL . '/clientes/registrar_cliente.php');
    }
    exit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Si algo falla, deshace todo
    }

    die("Error al registrar: " . $e->getMessage());
}
