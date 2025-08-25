<?php
// verificar_sesion.php
session_start();

// Si la sesión no está iniciada (no existe el usuario_id), redirigir a la página de login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.html');
    exit;
}
?>
