<?php
// Incluir el encabezado para la conexión a la base de datos
include_once "../encabezado.php";
// Incluir las funciones necesarias
include_once "../funciones.php";

header('Content-Type: application/json');
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener el cuerpo de la solicitud en formato JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verificar si los datos necesarios están presentes
if (!isset($data['mesa_id']) || !isset($data['pedido']) || !is_array($data['pedido'])) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(['error' => 'Faltan datos o el formato es incorrecto']);
    exit;
}

$mesa_id = $data['mesa_id'];
$pedido = $data['pedido'];

// Iniciar una transacción de base de datos
$con->begin_transaction();

try {
    // 1. Registrar la venta principal
    $id_venta = registrarVenta($mesa_id);
    if ($id_venta === false) {
        throw new Exception("Error al registrar la venta principal.");
    }

    // 2. Registrar los detalles de la venta
    foreach ($pedido as $item) {
        $insumo_id = $item['id'];
        $cantidad = $item['cantidad'];
        $precio = $item['precio'];

        $exito_detalle = registrarDetalleVenta($id_venta, $insumo_id, $cantidad, $precio);
        if (!$exito_detalle) {
            throw new Exception("Error al registrar el detalle del pedido para el insumo ID: " . $insumo_id);
        }

        // 3. Descontar del inventario
        $exito_descuento = descontarInventario($insumo_id, $cantidad);
        if (!$exito_descuento) {
            throw new Exception("Error al descontar el inventario para el insumo ID: " . $insumo_id);
        }
    }

    // Si todo fue exitoso, confirmar la transacción
    $con->commit();
    echo json_encode(['success' => true, 'message' => 'Venta registrada con éxito', 'venta_id' => $id_venta]);

} catch (Exception $e) {
    // Si algo falló, revertir todos los cambios
    $con->rollback();
    http_response_code(500); // Error interno del servidor
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>