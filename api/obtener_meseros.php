<?php
include_once "../funciones.php";

header('Content-Type: application/json; charset=utf-8');

$bd = conectarBaseDatos();

try {
    $sentencia = $bd->query("SELECT id, nombre FROM meseros ORDER BY nombre ASC");
    $meseros = $sentencia->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($meseros);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener los meseros: ' . $e->getMessage()]);
}
?>