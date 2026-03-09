<?php
//Conexion a la base de datos informatica
define('SERVIDOR', 'localhost');
// define('SERVIDOR', 'www.concretosamericas.com');
define('USUARIO', 'root');
// define('USUARIO', 'concretosamerica_admin');
define('PASSWORD', '');
// define('PASSWORD', 'C0ncr3t0s2024');
define('BD', 'recompensas');
// define('BD', 'concretosamerica_recompensas');
date_default_timezone_set('America/Mexico_City');
$URL = '/recompensas';
$servidor = 'mysql:dbname=' . BD . ';host=' . SERVIDOR;
// test
try {
    $pdo = new PDO($servidor, username: USUARIO, password: PASSWORD);
    // echo "<script>alert('La Conexion a la Base de Datos fue Exitosa')</script>";
} catch (PDOException $e) {
    echo "<script>alert('Error en Conexion a la Base de Datos')</script>";
}
