<?php
include('../app/config/config.php');
include("../app/functions/auth.php");

session_start();
// Destruye la sesión
session_unset(); // Libera las variables
session_destroy();
header('location:' . $URL . '/login');
exit();
