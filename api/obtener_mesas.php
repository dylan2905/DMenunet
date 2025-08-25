<?php

// Incluir el archivo de funciones
require_once 'funciones.php';

// Establecer el encabezado para la respuesta JSON
header('Content-Type: application/json');

// Obtener el cuerpo de la solicitud
$json = file_get_contents('php://input');

// Decodificar el JSON
$mesa = json_decode($json);

// Verificar que los datos son válidos
if (empty($mesa) || !isset($mesa->id)) {
    http_response_code(400); // 400 Bad Request
    echo json_encode(['error' => 'Datos de mesa inválidos.']);
    exit;
}

try {
    // Llamar a tu función `editarMesa`
    $resultado = editarMesa($mesa);

    if ($resultado) {
        echo json_encode(['message' => 'Mesa actualizada correctamente.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo actualizar la mesa.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>