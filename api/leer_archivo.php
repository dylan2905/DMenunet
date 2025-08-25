<?php

// Incluir el archivo de funciones
require_once 'funciones.php';

// Establecer el encabezado para la respuesta JSON
header('Content-Type: application/json');

// Obtener el ID de la mesa del parámetro de la URL
$idMesa = $_GET['mesa'] ?? null;

// Verificar que se recibió un ID
if (empty($idMesa)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID de mesa no proporcionado.']);
    exit;
}

try {
    // Llamar a tu función `leerArchivo`
    $datosMesa = leerArchivo($idMesa);
    
    // Si la mesa está libre, leerArchivo devuelve una estructura sin insumos, lo cual es correcto
    echo json_encode($datosMesa);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al leer el archivo de la mesa: ' . $e->getMessage()]);
}
?>