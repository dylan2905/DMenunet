<?php
// Configura el nivel de reporte de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluye el archivo que contiene la función de verificación de tablas
// Asegúrate de que la ruta sea correcta
require_once 'api/verificar_tablas.php';

// Ejecuta la función para verificar si las tablas existen
$tablasExisten = verificarTablas();

if ($tablasExisten) {
    // Las tablas existen, redirige al usuario a la página de mesas
    header("Location: mesas.html");
    exit(); // Es importante usar exit() después de una redirección
} else {
    // Las tablas no existen, redirige al usuario a la página de registro inicial
    header("Location: registro_inicial.html");
    exit();
}
?>