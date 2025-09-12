<?php
header('Content-Type: application/json');
session_start();

include_once "funciones.php"; // Incluye tu archivo de funciones

try {
    $bd = conectarBaseDatos();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['nombre']) || empty($data['numeroMesas'])) {
        echo json_encode(['success' => false, 'error' => 'El nombre y la cantidad de mesas son obligatorios.']);
        exit;
    }

    // Llama a tu función ya existente
    $datosLocal = (object) [
        'nombre' => $data['nombre'],
        'telefono' => $data['telefono'] ?? null,
        'direccion' => $data['direccion'] ?? null,
        'numeroMesas' => intval($data['numeroMesas']),
        'logo' => $data['logo'] ?? null
    ];
    
    // Asume que la función manejará la inserción/actualización
    $exito = registrarInformacionLocal($datosLocal);
    
    if ($exito) {
        echo json_encode(['success' => true, 'message' => 'Datos del restaurante registrados correctamente.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo registrar la información del local.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>