<?php
// api/pedidos/cerrar.php
header('Content-Type: application/json; charset=utf-8');
require_once '../funciones.php';

$bd = conectarBaseDatos();

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['id']) || !isset($data['medio_pago'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos para cerrar el pedido (ID del pedido o medio de pago).']);
    exit;
}

$pedido_id = $data['id'];
$medio_pago = $data['medio_pago'];

try {
    $bd->beginTransaction();

    // Obtener información del pedido para actualizar la mesa
    $sentencia_pedido = $bd->prepare("SELECT mesa_id FROM pedidos WHERE id = ?");
    $sentencia_pedido->execute([$pedido_id]);
    $pedido = $sentencia_pedido->fetch(PDO::FETCH_ASSOC);

    // Actualizar el estado del pedido a 'entregado' y registrar el medio de pago
    $sentencia_update_pedido = $bd->prepare("UPDATE pedidos SET estado = 'entregado', medio_pago = ? WHERE id = ?");
    $sentencia_update_pedido->execute([$medio_pago, $pedido_id]);

    // Actualizar el estado de la mesa a 'disponible' si es un pedido de mesa
    if ($pedido && $pedido['mesa_id']) {
        $sentencia_update_mesa = $bd->prepare("UPDATE mesas SET estado = 'disponible' WHERE id = ?");
        $sentencia_update_mesa->execute([$pedido['mesa_id']]);
    }

    $bd->commit();
    echo json_encode(['success' => true, 'message' => 'Cuenta cerrada y venta registrada.']);

} catch (PDOException $e) {
    $bd->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>