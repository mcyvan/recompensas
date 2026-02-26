<?php
include ('../app/config/config.php');
include ("../app/functions/auth.php");
verificarSesion();

// Destruye la sesión
session_destroy();

header('location:'.$URL.'/login');
exit();
?>