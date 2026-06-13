<?php

require_once __DIR__ . '/../app/config/config.php';

function base64UrlDecode(string $value)
{
    $padding = strlen($value) % 4;

    if ($padding !== 0) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

function rechazarAccesoSso(string $mensaje)
{
    http_response_code(403);
    exit($mensaje);
}

if (!defined('SSO_LOGISTICA_SECRET') || SSO_LOGISTICA_SECRET === '') {
    error_log('SSO de Logistica sin secreto configurado.');
    rechazarAccesoSso('El acceso integrado no esta configurado.');
}

$token = $_GET['token'] ?? '';
$partes = explode('.', $token, 2);

if (count($partes) !== 2) {
    rechazarAccesoSso('Enlace de acceso invalido.');
}

[$payloadCodificado, $firmaRecibida] = $partes;
$firmaEsperada = hash_hmac('sha256', $payloadCodificado, SSO_LOGISTICA_SECRET);

if (!hash_equals($firmaEsperada, $firmaRecibida)) {
    rechazarAccesoSso('La firma del acceso no es valida.');
}

$payloadJson = base64UrlDecode($payloadCodificado);
$payload = $payloadJson !== false ? json_decode($payloadJson, true) : null;

if (!is_array($payload)) {
    rechazarAccesoSso('Los datos del acceso no son validos.');
}

$camposRequeridos = ['sub', 'usuario', 'nombre', 'apellido_p', 'apellido_m', 'telefono', 'rol', 'iat', 'exp', 'nonce'];

foreach ($camposRequeridos as $campo) {
    if (!array_key_exists($campo, $payload)) {
        rechazarAccesoSso('El acceso esta incompleto.');
    }
}

$ahora = time();

if ($payload['rol'] !== 'OPERADOR'
    || !is_numeric($payload['sub'])
    || (int) $payload['sub'] <= 0
    || !is_numeric($payload['iat'])
    || !is_numeric($payload['exp'])
    || (int) $payload['iat'] > $ahora + 10
    || (int) $payload['exp'] < $ahora
    || (int) $payload['exp'] - (int) $payload['iat'] > 60
    || !preg_match('/^[a-f0-9]{32}$/', (string) $payload['nonce'])) {
    rechazarAccesoSso('El acceso expiro o no esta autorizado.');
}

$idUsuarioLogistica = (int) $payload['sub'];
$usuario = strtoupper(trim((string) $payload['usuario']));
$nombre = strtoupper(trim((string) $payload['nombre']));
$apellidoP = strtoupper(trim((string) $payload['apellido_p']));
$apellidoM = strtoupper(trim((string) $payload['apellido_m']));
$telefono = preg_replace('/[^0-9]/', '', (string) $payload['telefono']);

if ($usuario === '' || $nombre === '' || $apellidoP === '') {
    rechazarAccesoSso('El operador no tiene datos suficientes.');
}

try {
    $pdo->beginTransaction();

    $pdo->prepare('DELETE FROM tb_sso_nonces WHERE fecha_expiracion < NOW()')->execute();

    $stmt = $pdo->prepare(
        'INSERT INTO tb_sso_nonces (nonce, id_usuario_logistica, fecha_expiracion)
         VALUES (?, ?, FROM_UNIXTIME(?))'
    );
    $stmt->execute([$payload['nonce'], $idUsuarioLogistica, (int) $payload['exp']]);

    $stmt = $pdo->prepare(
        "SELECT u.id_usuario
         FROM tb_usuarios u
         INNER JOIN tb_usuarios_detalle d ON d.id_usuario = u.id_usuario
         INNER JOIN tb_roles r ON r.id_rol = d.id_rol
         WHERE u.id_usuario_logistica = ? AND u.estatus = 1 AND r.rol = 'OPERADOR'
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->execute([$idUsuarioLogistica]);
    $idUsuario = $stmt->fetchColumn();

    if (!$idUsuario) {
        $stmt = $pdo->prepare("SELECT id_rol FROM tb_roles WHERE rol = 'OPERADOR' AND estatus = 1 LIMIT 1");
        $stmt->execute();
        $idRol = $stmt->fetchColumn();

        if (!$idRol) {
            throw new RuntimeException('No existe el rol OPERADOR en Recompensas.');
        }

        $stmt = $pdo->prepare(
            "SELECT u.id_usuario, r.rol
             FROM tb_usuarios u
             LEFT JOIN tb_usuarios_detalle d ON d.id_usuario = u.id_usuario
             LEFT JOIN tb_roles r ON r.id_rol = d.id_rol
             WHERE u.usuario = ?
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$usuario]);
        $usuarioExistente = $stmt->fetch();
        $idUsuario = $usuarioExistente['id_usuario'] ?? null;

        if ($idUsuario) {
            if (($usuarioExistente['rol'] ?? '') !== 'OPERADOR') {
                throw new RuntimeException('El nombre de usuario ya pertenece a otro rol.');
            }

            $stmt = $pdo->prepare('UPDATE tb_usuarios SET id_usuario_logistica = ?, estatus = 1 WHERE id_usuario = ?');
            $stmt->execute([$idUsuarioLogistica, $idUsuario]);

            $stmt = $pdo->prepare(
                'UPDATE tb_usuarios_detalle
                 SET id_rol = ?, nombres = ?, apellido_p = ?, apellido_m = ?, telefono = ?
                 WHERE id_usuario = ?'
            );
            $stmt->execute([$idRol, $nombre, $apellidoP, $apellidoM, $telefono, $idUsuario]);
        } else {
            $passwordBloqueado = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO tb_usuarios
                    (id_usuario_logistica, usuario, password, estatus, fecha_registro)
                 VALUES (?, ?, ?, 1, NOW())'
            );
            $stmt->execute([$idUsuarioLogistica, $usuario, $passwordBloqueado]);
            $idUsuario = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO tb_usuarios_detalle
                    (id_usuario, id_rol, nombres, apellido_p, apellido_m, telefono, fecha_registro)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$idUsuario, $idRol, $nombre, $apellidoP, $apellidoM, $telefono]);
        }
    }

    $pdo->commit();

    session_start();
    session_regenerate_id(true);
    $_SESSION['rol'] = 'OPERADOR';
    $_SESSION['nombre'] = trim($nombre . ' ' . $apellidoP . ' ' . $apellidoM);
    $_SESSION['id_usuario_login'] = (int) $idUsuario;
    $_SESSION['usuario'] = $usuario;
    $_SESSION['origen_sso'] = 'LOGISTICA';

    header('Location: ' . $URL . '/operador/menu_operador.php');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    if ((string) $e->getCode() === '23000') {
        rechazarAccesoSso('Este enlace ya fue utilizado.');
    }

    rechazarAccesoSso('No fue posible iniciar sesion en Recompensas.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    rechazarAccesoSso('No fue posible iniciar sesion en Recompensas.');
}
