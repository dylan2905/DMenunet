<?php
include_once "../funciones.php";

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents("php://input"));

if (!isset($input->ventaId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado.']);
    exit;
}

$ventaId = $input->ventaId;

try {
    $exito = anularVenta($ventaId);

    if ($exito) {
        echo json_encode(['success' => true, 'message' => 'Venta anulada y stock devuelto con éxito.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al anular la venta.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error inesperado: ' . $e->getMessage()]);
}
?>