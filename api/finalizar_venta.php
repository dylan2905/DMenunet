<?php
include_once "../funciones.php";

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents("php://input"));

if (!isset($input->ventaId) || !isset($input->mesaId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos (ventaId, mesaId).']);
    exit;
}

$ventaId = $input->ventaId;
$mesaId = $input->mesaId;

try {
    $exito = finalizarVenta($ventaId, $mesaId);

    if ($exito) {
        echo json_encode(['success' => true, 'message' => 'Venta finalizada y mesa liberada con éxito.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al finalizar la venta.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>