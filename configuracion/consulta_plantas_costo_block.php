<?php
include("../app/config/config.php"); // Asegúrate de que la configuración esté correctamente incluida.

header('Content-Type: application/json'); // Definir que la respuesta será en formato JSON

// Verificar si el parámetro 'id_planta' está presente en la solicitud GET
if (isset($_GET['id_planta']) && is_numeric($_GET['id_planta'])) {
    $id_planta = $_GET['id_planta']; // Obtener el id_planta desde la solicitud GET

    try {
        // Consulta para obtener los productos relacionados con la planta
        $consulta_producto = $pdo->prepare("SELECT id_producto, producto FROM tb_producto WHERE id_planta = :id_planta");
        $consulta_producto->bindParam(':id_planta', $id_planta, PDO::PARAM_INT);
        $consulta_producto->execute();

        // Obtener todos los productos asociados con la planta
        $productos = $consulta_producto->fetchAll(PDO::FETCH_ASSOC);

        // Verificar si hay productos y devolverlas como un JSON
        if (count($productos) > 0) {
            echo json_encode($productos); // Devuelve los productos en formato JSON
        } else {
            // Si no hay productos, se envía un mensaje indicando que no se encontraron productos
            echo json_encode(["message" => "No se encontraron productos para esta planta."]);
        }
    } catch (Exception $e) {
        // En caso de error, se envía un mensaje con el error en formato JSON
        echo json_encode(["error" => "Error al obtener los productos: " . $e->getMessage()]);
    }
} else {
    // Si no se pasa un 'id_planta' válido en la solicitud GET
    echo json_encode(["error" => "El parámetro 'id_planta' no está presente o no es válido."]);
}
?>
