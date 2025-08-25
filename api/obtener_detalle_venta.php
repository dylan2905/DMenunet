<?php
include_once "../funciones.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de venta no proporcionado.']);
    exit;
}

$idVenta = $_GET['id'];

if (!is_numeric($idVenta)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de venta inválido.']);
    exit;
}

try {
    $venta = obtenerVentaPorId($idVenta);

    if ($venta) {
        $venta->insumos = obtenerInsumosVenta($idVenta);
        echo json_encode($venta);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Venta no encontrada.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}

?>