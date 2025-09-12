<?php
header('Content-Type: application/json');
include_once "funciones.php";

try {
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT nombre, valor FROM impuestos");
    $sentencia->execute();
    $impuestos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($impuestos);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}
?>