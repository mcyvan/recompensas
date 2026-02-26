<?php
include("../app/config/config.php"); // Asegúrate de que la configuración esté correctamente incluida.

header('Content-Type: application/json'); // Definir que la respuesta será en formato JSON

if (isset($_GET['id_producto'])) {
    $id_producto = $_GET['id_producto']; // Obtener el id_producto desde la solicitud

    try {
        // Consulta para obtener las fechas de producción
        $consulta_fechas = $pdo->prepare("SELECT id_plan_produccion, fecha_plan
                                            FROM tb_plan_produccion
                                            WHERE id_producto = :id_producto
                                            AND MONTH(fecha_plan) = MONTH(CURDATE())                                            
                                            ORDER BY fecha_plan ASC");
        $consulta_fechas->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $consulta_fechas->execute();

        // Obtener todas las fechas de producción
        $fechas = $consulta_fechas->fetchAll(PDO::FETCH_ASSOC);

        // Verificar si hay fechas y devolverlas como un JSON
        if (count($fechas) > 0) {
            echo json_encode($fechas); // Devuelve el JSON con las fechas
        } else {
            // Si no hay fechas, se puede enviar un mensaje vacío
            echo json_encode(["message" => "No se encontraron fechas para este producto."]);
        }
    } catch (Exception $e) {
        // En caso de error, se envía un mensaje con el error en formato JSON
        echo json_encode(["error" => "Error al obtener las fechas: " . $e->getMessage()]);
    }
} else {
    // Si no se pasa un id_producto en la solicitud
    echo json_encode(["error" => "El parámetro 'id_producto' no está presente."]);
}
?>
