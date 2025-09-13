<?php
// api/pedidos/cancelar.php
header('Content-Type: application/json; charset=utf-8');
require_once '../funciones.php';

$bd = conectarBaseDatos();

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Falta el ID del pedido para cancelar.']);
    exit;
}

$pedido_id = $data['id'];

try {
    $bd->beginTransaction();

    // Obtener el ID de la mesa asociada al pedido
    $sentencia_pedido = $bd->prepare("SELECT mesa_id FROM pedidos WHERE id = ?");
    $sentencia_pedido->execute([$pedido_id]);
    $pedido = $sentencia_pedido->fetch(PDO::FETCH_ASSOC);

    // Actualizar el estado del pedido a 'cancelado'
    $sentencia_update_pedido = $bd->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?");
    $sentencia_update_pedido->execute([$pedido_id]);

    // Actualizar el estado de la mesa a 'disponible' si es un pedido de mesa
    if ($pedido && $pedido['mesa_id']) {
        $sentencia_update_mesa = $bd->prepare("UPDATE mesas SET estado = 'disponible' WHERE id = ?");
        $sentencia_update_mesa->execute([$pedido['mesa_id']]);
    }

    $bd->commit();
    echo json_encode(['success' => true, 'message' => 'Orden cancelada correctamente.']);

} catch (PDOException $e) {
    $bd->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>