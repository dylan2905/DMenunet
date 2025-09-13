<?php
// api/pedidos/crear.php
header('Content-Type: application/json; charset=utf-8');
require_once '../funciones.php';

$bd = conectarBaseDatos();

// Se lee el cuerpo de la solicitud JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validar que se hayan recibido los datos necesarios
if (!isset($data['productos']) || empty($data['productos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se han proporcionado productos para el pedido.']);
    exit;
}

// Extracción y validación de datos
$tipo = $data['tipo'] ?? '';
$mesa_id = $data['mesa_id'] ?? null;
$cliente = $data['cliente'] ?? null;
$num_personas = $data['num_personas'] ?? null;

if ($tipo === 'en_restaurante' && empty($mesa_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El ID de la mesa es obligatorio para un pedido en restaurante.']);
    exit;
}

try {
    $bd->beginTransaction();

    // Calcular el total del pedido
    $total = 0;
    foreach ($data['productos'] as $producto) {
        $total += ($producto['precio'] * $producto['cantidad']);
    }

    // Insertar el nuevo pedido en la tabla `pedidos`
    $sentencia_pedido = $bd->prepare("
        INSERT INTO pedidos (mesa_id, cliente_nombre, total, estado, fecha_creacion, num_personas) 
        VALUES (?, ?, ?, 'en_preparacion', NOW(), ?)
    ");
    $sentencia_pedido->execute([$mesa_id, $cliente, $total, $num_personas]);
    $pedido_id = $bd->lastInsertId();

    // Insertar los productos en la tabla `pedido_productos`
    $sentencia_productos = $bd->prepare("
        INSERT INTO pedido_productos (pedido_id, producto_id, cantidad, precio) 
        VALUES (?, ?, ?, ?)
    ");
    foreach ($data['productos'] as $producto) {
        $sentencia_productos->execute([$pedido_id, $producto['id'], $producto['cantidad'], $producto['precio']]);
    }

    // Si es un pedido de mesa, actualizar el estado de la mesa a 'ocupada'
    if ($tipo === 'en_restaurante' && $mesa_id) {
        $sentencia_mesa = $bd->prepare("UPDATE mesas SET estado = 'ocupada' WHERE id = ?");
        $sentencia_mesa->execute([$mesa_id]);
    }

    $bd->commit();
    echo json_encode(['success' => true, 'message' => 'Pedido creado correctamente.', 'pedido_id' => $pedido_id]);

} catch (PDOException $e) {
    $bd->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>