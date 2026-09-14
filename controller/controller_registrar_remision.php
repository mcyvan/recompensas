<?php
include('../app/config/config.php');
include("../app/functions/auth.php");
include("../app/functions/consultas_puntos.php");
include_once("../app/functions/remisiones.php");
/** @var PDO $pdo */
/** @var string $URL */
verificarSesion();

try {

    $pdo->beginTransaction();

    $telefono = preg_replace('/\D/', '', (string) ($_POST['telefono'] ?? ''));
    $idClienteQr = filter_var($_POST['id_cliente_qr'] ?? null, FILTER_VALIDATE_INT) ?: 0;

    if ($idClienteQr > 0) {
        $clienteQr = obtenerClienteActivoPorId($idClienteQr);
        $id_cliente = (int) $clienteQr['id_cliente'];
        $telefono = $telefono !== '' ? $telefono : (string) $clienteQr['telefono'];
    } else {
        $id_cliente = obtenerIdClienteTelefono($telefono);
    }

    $folio_remision = normalizarFolioRemisionOperador($_POST['remision'] ?? '');
    $volumen = floatval($_POST['volumen']);
    $id_usuario = $_SESSION['id_usuario_login'];
    $rol_captura = rolCapturaRemisionActual();
    $id_camion_logistica = filter_var($_SESSION['id_camion_logistica'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $camion_logistica = strtoupper(trim((string) ($_SESSION['camion_logistica'] ?? ''))) ?: null;
    $estatus = 'EN PROCESO';
    $fecha_crm = trim($_POST['fecha_crm'] ?? '');
    $datos_crm = [
        'qr_origen' => trim($_POST['qr_origen'] ?? '') ?: null,
        'qr_texto' => trim($_POST['qr_texto'] ?? '') ?: null,
        'qr_datos_json' => trim($_POST['qr_datos_json'] ?? '') ?: null,
        'factura_crm' => trim($_POST['factura_crm'] ?? '') ?: null,
        'pedido_crm' => trim($_POST['pedido_crm'] ?? '') ?: null,
        'fecha_crm' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_crm) ? $fecha_crm : null,
        'planta_crm' => trim($_POST['planta_crm'] ?? '') ?: null,
        'vendedor_crm' => trim($_POST['vendedor_crm'] ?? '') ?: null,
        'articulo_crm' => trim($_POST['articulo_crm'] ?? '') ?: null,
        'cantidad_crm' => ($_POST['cantidad_crm'] ?? '') !== '' ? (float) $_POST['cantidad_crm'] : null,
        'precio_crm' => ($_POST['precio_crm'] ?? '') !== '' ? (float) $_POST['precio_crm'] : null,
        'cliente_crm' => trim($_POST['cliente_crm'] ?? '') ?: null,
    ];

    $hora_inicio = date('Y-m-d H:i:s');

    // echo $telefono . " - " . $folio_remision . " - " . $volumen . " - " . $id_usuario . " - " . $hora_inicio . " - " . $estatus;
    // exit();

    if (!$id_cliente) {
        throw new Exception("Teléfono no válido");
    }

    // ✅ VALIDAR FORMATO
    if (!folioRemisionOperadorValido($folio_remision)) {
        throw new Exception("La remisión debe tener el formato RE seguido de 6 dígitos, por ejemplo RE123456");
    }

    // ✅ VALIDAR DUPLICADO
    $stmt = $pdo->prepare("SELECT folio_remision FROM tb_remisiones WHERE folio_remision = ?");
    $stmt->execute([$folio_remision]);

    if ($stmt->fetch()) {
        throw new Exception("La remisión ya existe");
    }

    // ✅ VALIDAR METROS
    if ($volumen < 1 || $volumen > 8 || fmod($volumen, 0.5) != 0.0) {
        throw new Exception("Metros no válidos");
    }

    $columnasRemisiones = array_column($pdo->query("SHOW COLUMNS FROM tb_remisiones")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $columnasDisponibles = array_flip($columnasRemisiones);
    $campos = ['id_cliente', 'telefono', 'folio_remision', 'volumen', 'id_operador', 'hora_inicio', 'estatus'];
    $valores = [
        $id_cliente,
        $telefono,
        $folio_remision,
        $volumen,
        $id_usuario,
        $hora_inicio,
        $estatus
    ];

    foreach ($datos_crm as $campo => $valor) {
        if (isset($columnasDisponibles[$campo])) {
            $campos[] = $campo;
            $valores[] = $valor;
        }
    }

    if (isset($columnasDisponibles['id_camion_logistica'])) {
        $campos[] = 'id_camion_logistica';
        $valores[] = $id_camion_logistica;
    }

    if (isset($columnasDisponibles['camion_logistica'])) {
        $campos[] = 'camion_logistica';
        $valores[] = $camion_logistica;
    }

    if (isset($columnasDisponibles['rol_captura'])) {
        $campos[] = 'rol_captura';
        $valores[] = $rol_captura;
    }


    $placeholders = implode(', ', array_fill(0, count($campos), '?'));
    $sqlInsert = "INSERT INTO tb_remisiones (" . implode(', ', $campos) . ") VALUES ($placeholders)";
    $consulta = $pdo->prepare($sqlInsert);
    $consulta->execute($valores);

    $id_remision = $pdo->lastInsertId();



    $pdo->commit();

    $_SESSION['mensaje_registro_remision_correcto'] = "Remisión registrada correctamente.";

    header('Location: ' . $URL . '/operador/remision_en_proceso.php?id=' . $id_remision);
    exit();
} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensaje_registro_remision_error'] = $e->getMessage();

    header('Location: ' . $URL . '/operador/menu_operador.php');
    exit();
}
