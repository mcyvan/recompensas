<?php
//Conexion a la base de datos informatica
define('SERVIDOR', 'www.concretosamericas.com');
define('USUARIO', 'concretosamerica_admin');
define('PASSWORD', 'C0ncr3t0s2024');
define('BD', 'concretosamerica_sistemas');
date_default_timezone_set('America/Mexico_City');

$URL = '/sistemas';
$servidor = 'mysql:dbname=' . BD . ';host=' . SERVIDOR;
// test
try {
    $pdo = new PDO($servidor, username: USUARIO, password: PASSWORD);
    // echo "<script>alert('La Conexion a la Base de Datos fue Exitosa')</script>";
} catch (PDOException $e) {
    // Mostrar un mensaje de error sin revelar detalles sensibles
    echo "Error en la conexion a la base de datos: " . $e->getMessage();
    exit(); // Detener la ejecución si hay un error de conexión
}
?>