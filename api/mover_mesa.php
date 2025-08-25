<?php
include_once "../funciones.php";

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents("php://input"));

if (!isset($input->ventaId) || !isset($input->nuevaMesaId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos (ventaId, nuevaMesaId).']);
    exit;
}

$ventaId = $input->ventaId;
$nuevaMesaId = $input->nuevaMesaId;

try {
    $exito = moverVentaAMesa($ventaId, $nuevaMesaId);

    if ($exito) {
        echo json_encode(['success' => true, 'message' => 'Venta movida de mesa con éxito.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al mover la venta.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error inesperado: ' . $e->getMessage()]);
}
?>