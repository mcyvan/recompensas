<?php
session_start();

function verificarSesion()
{
    if (!isset($_SESSION['usuario'])) {
        // Redirigir a la página de login si no hay sesión activa
        header('Location: ' . 'recompensas' . '/login');  // Ajusta la URL según tu ruta
        exit(); // Termina el script para que no continúe ejecutándose
    }
}
